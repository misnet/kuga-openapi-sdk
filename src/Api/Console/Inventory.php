<?php
/**
 * 店仓相关 API
 */

namespace Kuga\Api\Console;

use Kuga\Core\Api\Exception as ApiException;
use Kuga\Core\GlobalVar;
use Kuga\Core\Shop\InventoryModel;
use Kuga\Core\Shop\InventorySheetItemModel;
use Kuga\Core\Shop\InventorySheetModel;
use Kuga\Core\Shop\ProductImgModel;
use Kuga\Core\Shop\ProductModel;
use Kuga\Core\Shop\ProductSkuModel;
use Kuga\Core\Shop\StoreModel;

class Inventory extends ShopBaseApi
{

    /**
     * 创建出入库单
     * @return integer 入库单ID
     * @throws ApiException
     */
    public function createSheet()
    {
        $tx = $this->_di->getShared('transactions');
        $transaction = $tx->get();
        $data = $this->_toParamObject($this->getParams());
        $model = new InventorySheetModel();
        $model->initData($data->toArray(), ['id','createTime']);
        $model->userId = $this->getUserMemberId();
        $model->isChecked = 0;
        $model->setTransaction($transaction);
        $result = $model->create();
        if (!$result) {
            $transaction->rollback($this->translator->_('单据创建失败'));
        }else{
            if($data['itemList']){
                $itemList = json_decode($data['itemList'],true);
                if(!empty($itemList)){
                    foreach($itemList as $item){
                        $item['qty']   = intval($item['qty']);
                        $item['skuId'] = intval($item['skuId']);
                        if(!$item['qty'] || !$item['skuId']){
                            continue;
                        }
                        $itemModel = new InventorySheetItemModel();
                        $itemModel->setTransaction($transaction);
                        $itemModel->skuId = $item['skuId'];
                        $itemModel->qty   = $item['qty'];
                        $itemModel->sheetId = $model->id;
                        $result = $itemModel->create();
                        if(!$result){
                            $transaction->rollback($this->translator->_('单据明细创建失败'));
                        }
                    }
                }
            }
        }
        $transaction->commit();
        return $model->id;
    }

    /**
     * 更新出入库单
     * @return bool
     * @throws ApiException
     */
    public function updateSheet()
    {
        $tx = $this->_di->getShared('transactions');
        $transaction = $tx->get();
        $data = $this->_toParamObject($this->getParams());
        $obj = InventorySheetModel::findFirstById($data['id']);
        if (!$obj) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        if($obj->isChecked){
            throw new ApiException($this->translator->_('已审核通过的出入库单不可再修改或删除'));
        }
        $obj->setTransaction($transaction);
        $obj->initData($data->toArray(), ['id','createTime']);
        $result = $obj->update();
        if (!$result) {
            $transaction->rollback($this->translator->_('单据修改失败'));
        }else{
            if($data['itemList']){
                $itemList = json_decode($data['itemList'],true);
                if(!empty($itemList)){
                    $skuIdList = [];
                    foreach($itemList as $item) {
                        $item['qty'] = intval($item['qty']);
                        $item['skuId'] = intval($item['skuId']);
                        if (!$item['qty'] || !$item['skuId']) {
                            continue;
                        }
                        $skuRow = InventorySheetItemModel::findFirst([
                            'sheetId=:sid: and skuId=:sku:',
                            'bind' => ['sid' => $obj->id, 'sku' => $item['skuId']]
                        ]);
                        if (!$skuRow){
                            $skuRow = new InventorySheetItemModel();
                            $skuRow->sheetId = $obj->id;
                            $skuRow->skuId = $item['skuId'];
                        }
                        $skuRow->setTransaction($transaction);
                        $skuRow->qty   = $item['qty'];
                        $result = $skuRow->save();
                        if(!$result){
                            $transaction->rollback($this->translator->_('单据明细创建失败'));
                        }
                        $skuIdList[] = $item['skuId'];
                    }
                    //删除不在单中的记录
                    if(!empty($skuIdList)){
                        $invalidSkuList = InventorySheetItemModel::find([
                            'sheetId=:sid: and skuId not in ({ids:array})',
                            'bind' => ['sid' => $obj->id, 'ids' => $skuIdList]
                        ]);
                        foreach($invalidSkuList as $sku){
                            $sku->setTransaction($transaction);
                            $result = $sku->delete();
                            if(!$result){
                                $transaction->rollback($this->translator->_('清理旧的单据明细失败'));
                            }
                        }
                    }
                }
            }
        }
        $transaction->commit();
        return true;
    }

    /**
     * 单据审核
     * @return mixed
     * @throws ApiException
     */
    public function checkedSheet(){
        $data = $this->_toParamObject($this->getParams());
        $obj = InventorySheetModel::findFirstById($data['id']);
        if (!$obj) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $tx = $this->_di->getShared('transactions');
        $transaction = $tx->get();
        $obj->isChecked = 1;
        $obj->setTransaction($transaction);
        $result = $obj->update();
        if($result){
            //明细入库
            $searcher  = InventorySheetItemModel::query();
            $searcher->join(ProductSkuModel::class,'sku.id=skuId','sku');
            $searcher->columns([
                'sku.productId',
                'skuId',
                'qty'
            ]);
            $searcher->where('sheetId=:id:');
            $searcher->bind(['id'=>$obj->id]);
            $result   = $searcher->execute();
            $itemList = $result->toArray();
            if($itemList){
                foreach($itemList as $item){
                    $stock = InventoryModel::findFirst([
                        'storeId=:stid: and skuId=:sid:',
                        'bind'=>['stid'=>$obj->storeId,'sid'=>$item['skuId']]
                    ]);
                    if(!$stock){
                        $stock = new InventoryModel();
                        $stock->productId = $item['productId'];
                        $stock->skuId     = $item['skuId'];
                        $stock->storeId   = $obj->storeId;
                        $stock->stockQty  = 0;
                    }
                    $stock->setTransaction($transaction);
                    $stock->stockQty += $obj->sheetType == InventorySheetModel::TYPE_OUT ? (-1 * $item['qty']):$item['qty'];
                    $result = $stock->save();
                    if(!$result){
                        $transaction->rollback('单据明细入库失败');
                    }
                }
            }
        }else{
            $transaction->rollback('单据审核失败');
        }
        $transaction->commit();
        return true;
    }
    /**
     * 删除出入库单
     * @return mixed
     * @throws ApiException
     */
    public function removeSheet(){
        $data = $this->_toParamObject($this->getParams());
        $obj = InventorySheetModel::findFirstById($data['id']);
        if (!$obj) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        if($obj->isChecked){
            throw new ApiException($this->translator->_('已审核通过的出入库单不可删除'));
        }
        return $obj->delete();
    }

    /**
     * 列出所有出入库单
     */
    public function listSheets(){
        $data = $this->_toParamObject($this->getParams());
        $data['limit']||$data['limit'] = GlobalVar::DATA_DEFAULT_LIMIT;
        $data['page']>0?$data['page']:$data['page']=1;
        $searcher = InventorySheetModel::query();
        $searcher->join(StoreModel::class,'storeId=store.id','store','left');
        $searcher->orderBy(InventorySheetModel::class.'.id desc');
        $searcher->columns('count(0) as total');
        $result = $searcher->execute();
        $total  = $result->getFirst()->total;
        $searcher->columns([
            InventorySheetModel::class.'.id',
            'sheetCode',
            'sheetTime',
            'sheetType',
            'sheetDesc',
            'userId',
            'storeId',
            'isChecked',
            'store.name as storeName',
            '(select count(0) from '.InventorySheetItemModel::class.' where sheetId='.InventorySheetModel::class.'.id) as skuNum',
            '(select sum(qty) from '.InventorySheetItemModel::class.' where sheetId='.InventorySheetModel::class.'.id) as qty'

        ]);
        $searcher->limit($data['limit'],($data['page'] - 1) * $data['limit']);
        $result = $searcher->execute();
        $list   = $result->toArray();
        return [
            'list'=>$list,
            'total'=>$total,
            'page'=>$data['page'],
            'limit'=>$data['limit']
        ];
    }

    /**
     * 取得某个单据
     * @return mixed
     * @throws ApiException
     */
    public function getSheet(){
        $data = $this->_toParamObject($this->getParams());
        $row  = InventorySheetModel::findFirstById($data['id']);
        if(!$row){
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $storeRow = StoreModel::findFirst([
            'id=:id:',
            'bind'=>['id'=>$row->storeId],
            'column'=>['name']
        ]);


        $itemRows = InventorySheetItemModel::find([
            'sheetId=:sid:',
            'bind'=>['sid'=>$data['id']]
        ]);
        $itemList = [];
        $ids = [];
        $skuInfoList = [];
        if($itemRows){
            foreach($itemRows as $item){
                $ids[] = $item->skuId;
                $itemList['sku'.$item->skuId] = $item->qty;
            }
        }

        //跨服务调用
        $response = $this->apiRequest('product.sku.getlist',['ids'=>join(',',$ids)]);
        $response = json_decode($response,true);
        if($response['status']==0 && $response['data']){
            $skuInfoList = $response['data'];
            if($skuInfoList){
                foreach($skuInfoList as &$sku){
                    if(in_array($sku['id'],$ids)){
                        $sku['qty'] = $itemList['sku'.$sku['id']];
                    }
                }
            }
        }
        $returnData = $row->toArray();
        if($storeRow){
            $returnData['storeName'] = $storeRow->name;
        }
        $returnData['itemList'] = $skuInfoList;
        return $returnData;
    }

    /**
     * 库存清单
     */
    public function items(){
        $data = $this->_toParamObject($this->getParams());
        $data['limit']||$data['limit'] = GlobalVar::DATA_DEFAULT_LIMIT;
        $data['page']>0?$data['page']:$data['page']=1;

        $searcher = InventoryModel::query();
        $searcher->join(ProductModel::class,InventoryModel::class.'.productId=p.id','p');
        $searcher->join(ProductSkuModel::class,InventoryModel::class.'.skuId=sku.id','sku');
        $searcher->join(StoreModel::class,InventoryModel::class.'.storeId=store.id','store');
        $searcher->where('1=1');
        $bind = [];
        if($data['storeId']){
            $searcher->andWhere('storeId=:sid:');
            $bind['sid'] = $data['storeId'];
        }
        if($data['keyword']){
            $searcher->andWhere('p.title like :q:');
            $bind['q'] = '%'.$data['keyword'].'%';
        }
        $searcher->columns('count(0) as total');
        !empty($bind)?$searcher->bind($bind):'';
        $result = $searcher->execute();
        $total  = $result->getFirst()->total;

        $searcher->limit($data['limit'],($data['page'] - 1) * $data['limit']);
        $searcher->orderBy(InventoryModel::class.'.id desc');
        $searcher->columns([
            InventoryModel::class.'.id',
            'p.title',
            'p.barcode',
            InventoryModel::class.'.productId',
            InventoryModel::class.'.skuId',
            InventoryModel::class.'.storeId',
            'store.name as storeName',
            InventoryModel::class.'.stockQty',
            InventoryModel::class.'.preoutQty',
            InventoryModel::class.'.preinQty',
            'sku.skuJson',
            'sku.skuSn',
            'sku.price',
            'sku.cost',
            'sku.originalSkuId',
            '(select imgUrl from '.ProductImgModel::class.' where '.ProductImgModel::class.'.productId=p.id and isFirst=1) as firstImgUrl'
        ]);
        $result = $searcher->execute();
        $list   = $result->toArray();
        if($list){
            foreach($list as &$sku){
                $sku['skuString'] = $this->_fetchSkuString($sku['skuJson']);
            }
        }
        return [
            'total'=>$total,
            'list' =>$list,
            'page' => $data['page'],
            'limit'=> $data['limit']
        ];


    }


}