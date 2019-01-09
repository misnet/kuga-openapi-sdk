<?php
/**
 * 店仓相关 API
 */

namespace Kuga\Api\Console;

use Kuga\Core\Api\Exception as ApiException;
use Kuga\Core\GlobalVar;
use Kuga\Core\Shop\InventorySheetItemModel;
use Kuga\Core\Shop\InventorySheetModel;

class Inventory extends BaseApi
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
     * 列出所有出入库单
     */
    public function listSheets(){
        $data = $this->_toParamObject($this->getParams());
        $data['limit']||$data['limit'] = GlobalVar::DATA_DEFAULT_LIMIT;
        $data['page']>0?$data['page']:$data['page']=1;
        $searcher = InventorySheetModel::query();
        $searcher->orderBy('id desc');
        $searcher->columns('count(0) as total');
        $result = $searcher->execute();
        $total  = $result->getFirst()->total;
        $searcher->columns([
            'id',
            'sheetCode',
            'sheetTime',
            'sheetType',
            'sheetDesc',
            'userId'
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
        $returnData['itemList'] = $skuInfoList;
        return $returnData;
    }


}