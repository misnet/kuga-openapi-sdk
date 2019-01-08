<?php
/**
 * 产品管理API
 * @author Donny
 */

namespace Kuga\Api\Console;

use Kuga\Core\Api\Exception as ApiException;
use Kuga\Core\Shop\ItemCatalogModel;
use Kuga\Core\Shop\ProductDescModel;
use Kuga\Core\Shop\ProductImgModel;
use Kuga\Core\Shop\ProductModel;
use Kuga\Core\Shop\ProductPropModel;
use Kuga\Core\Shop\ProductSkuModel;
use Kuga\Core\Shop\PropKeyModel;
use Kuga\Core\Shop\PropSetItemModel;
use Kuga\Core\Shop\PropValueModel;

class Product extends BaseApi{
    /**
     * 生成SKU编码
     * @param string 商品款号 $productBarcode
     * @param array $skuJson [{prop:?,value:?}...]
     * @param integer $propsetId 属性集合ID
     * @return string
     */
    private function createSkuSn($productBarcode,$skuJson,$propsetId){
        $searcher = PropSetItemModel::query();
        $searcher->join(PropKeyModel::class,'propkeyId=pk.id and pk.isDeleted=0','pk','left');
        $searcher->where('propsetId=:psid:');
        $searcher->bind(['psid'=>$propsetId]);
        $searcher->orderBy(PropSetItemModel::class.'.sortWeight desc');
        $searcher->columns(['propkeyId']);
        $result = $searcher->execute();
        $orderedPropkeyList  = $result->toArray();
        $snList = [];
        foreach($orderedPropkeyList as $propkey){
            $valueId = 0;
            foreach ($skuJson as $item) {
                if($item['prop'] == $propkey['propkeyId']){
                    $valueId = $item['value'];
                    break;
                }
            }
            $valueRow = PropValueModel::findFirst([
                'id=:id:',
                'bind'=>['id'=>$valueId],
                'columns'=>['code']
            ]);
            if($valueRow){
                $snList[] = $valueRow->code;
            }
        }
        return $productBarcode.join('',$snList);
    }
    /**
     * 创建产品
     */
    public function create(){
        $tx = $this->_di->getShared('transactions');
        $transaction = $tx->get();

        $data = $this->_toParamObject($this->getParams());
        $productModel = new ProductModel();
        $productModel->initData($data->toArray(),['id','createTime','updateTime']);
        $productModel->setTransaction($transaction);
        $result = $productModel->create();
        if(!$result){
            $transaction->rollback($this->translator->_('商品保存失败'));
        }
        //保存内容描述
        $contentObject = new ProductDescModel();
        $contentObject->content       = $data['content'];
        $contentObject->mobileContent = $data['mobileContent'];
        $contentObject->productId     = $productModel->id;
        $result = $contentObject->create();
        if(!$result){
            $transaction->rollback($this->translator->_('商品保存失败'));
        }
        //TODO:保存效果图
        $imgList  = json_encode($data['imgList'],true);
        if(is_array($imgList) && !empty($imgList)){
            $i = 0;
            foreach($imgList as $img){
                $imgModel = new ProductImgModel();
                $imgModel->setTransaction($transaction);
                $imgModel->productId = $productModel->id;
                $imgModel->isFirst   = $i==0?1:0;
                $imgModel->imgUrl    = $img['url'];
                $result = $imgModel->create();
                if(!$result){
                    $transaction->rollback($this->translator->_('商品效果图保存失败'));
                }
                $i++;
            }
        }
        //保存相关属性
        $propList = json_decode($data['propList'],true);
        if(is_array($propList) && !empty($propList)){
            foreach($propList as $prop){
                //一对属性
                if(isset($prop['propkeyId']) && isset($prop['propvalue'])){
                    $propModel = new ProductPropModel();
                    $propModel->productId = $productModel->id;
                    $propModel->propkeyId = intval($prop['propkeyId']);
                    $propModel->propvalue = is_array($prop['propvalue'])?join(',',$prop['propvalue']):trim($prop['propvalue']);
                    $propModel->isSaleProp= 0;
                    $propModel->setTransaction($transaction);
                    $result = $propModel->create();
                    if(!$result){
                        $transaction->rollback($this->translator->_('商品属性保存失败'));
                    }
                }
            }
        }

        //保存SKU
        $skuList  = json_decode($data['skuList'],true);
        if(is_array($skuList) && !empty($skuList)){
            foreach($skuList as $sku){
                $skuModel = new ProductSkuModel();
                $skuModel->productId = $productModel->id;
                $skuModel->setTransaction($transaction);
                $skuModel->price  = floatval($sku['price']);
                $skuModel->cost   = floatval($sku['cost']);
                $skuModel->originalSkuId = trim($sku['originalSkuId']);
                //JSON串拼接
                $skuJson = [];
                foreach($sku as $key=>$value){
                    $matches = null;
                    preg_match('/^prop(\d+)$/',$key,$matches);
                    if($matches){
                        $skuJson[] = ['prop'=>$matches[1],'value'=>$value];
                    }
                }
                $skuModel->skuJson = json_encode($skuJson);
                $skuModel->skuSn   = $this->createSkuSn($productModel->barcode,$skuJson,$productModel->propsetId);
                $result = $skuModel->create();
                if(!$result){
                    $transaction->rollback($this->translator->_('商品SKU保存失败'));
                }
            }
        }

        $transaction->commit();
        return true;
    }

    /**
     * 修改商品
     */
    public function update(){
        $tx = $this->_di->getShared('transactions');
        $transaction = $tx->get();

        $data = $this->_toParamObject($this->getParams());
        $productModel = ProductModel::findFirst([
            'id=:id: and isDeleted=0',
            'bind'=>['id' => $data['id']]
        ]);
        if(!$productModel){
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $productModel->initData($data->toArray(),['id','createTime','updateTime','isDeleted','catalogId']);
        $productModel->setTransaction($transaction);
        $result = $productModel->update();
        if(!$result){
            $transaction->rollback($this->translator->_('商品修改失败'));
        }
        //保存内容描述
        $contentObject = ProductDescModel::findFirst([
            'productId=:id:',
            'bind'=>['id' => $data['id']]
        ]);
        if(!$contentObject){
            $contentObject = new ProductDescModel();
            $contentObject->productId     = $productModel->id;
        }
        $contentObject->content       = $data['content'];
        $contentObject->mobileContent = $data['mobileContent'];
        $result = $contentObject->save();
        if(!$result){
            $transaction->rollback($this->translator->_('商品内容修改失败'));
        }
        //TODO:保存效果图
        $imgList  = json_decode($data['imgList'],true);
        if(is_array($imgList) && !empty($imgList)){
            $i = 0;
            $firstNode = ProductImgModel::findFirst([
                'productId=:id: and isFirst=1',
                'bind'=>['id'=>$productModel->id]
            ]);
            if($firstNode){
                $firstNode->setTransaction($transaction);
                $firstNode->isFirst = 0;
                $firstNode->update();
            }
            $validImgId = [];
            foreach($imgList as $img){
                $img['id'] = intval($img['id']);
                $imgModel = null;
                if($img['id']){
                    $imgModel = ProductImgModel::findFirstById($img['id']);
                }
                if(!$imgModel){
                    $imgModel = new ProductImgModel();
                    $imgModel->productId = $productModel->id;
                }
                $imgModel->setTransaction($transaction);
                $imgModel->isFirst   = $i==0?1:0;
                $imgModel->imgUrl    = $img['url'];
                $result = $imgModel->save();
                if(!$result){
                    $transaction->rollback($this->translator->_('商品效果图保存失败'));
                }
                $validImgId[] = $imgModel->id;
                $i++;
            }
            //remove the invalid img
            if(!empty($validImgId)){
                //清掉其他
                $invalidImgList = ProductImgModel::find([
                    'productId=:id: and id not in ({ids:array})',
                    'bind'=>['id' => $data['id'], 'ids'=>$validImgId]
                ]);
                if($invalidImgList){
                    foreach($invalidImgList as $img){
                        $img->setTransaction($transaction);
                        $result = $img->delete();
                        if(!$result){
                            $transaction->rollback($this->translator->_('商品图片清理失败'));
                        }
                    }
                }
            }
        }
        //保存相关属性
        $propList = json_decode($data['propList'],true);
        if(is_array($propList) && !empty($propList)){
            foreach($propList as $prop){
                //一对属性
                if(isset($prop['propkeyId']) && isset($prop['propvalue'])){
                    $propModel = ProductPropModel::findFirst([
                        'productId=:id: and propkeyId=:kid:',
                        'bind'=>['id' => $data['id'], 'kid'=>intval($prop['propkeyId'])]
                    ]);
                    if(!$propModel){
                        $propModel = new ProductPropModel();
                        $propModel->productId = $productModel->id;
                        $propModel->propkeyId = intval($prop['propkeyId']);
                    }
                    $propModel->propvalue = is_array($prop['propvalue'])?join(',',$prop['propvalue']):trim($prop['propvalue']);
                    $propModel->isSaleProp= 0;
                    $propModel->setTransaction($transaction);
                    $result = $propModel->save();
                    if(!$result){
                        $transaction->rollback($this->translator->_('商品属性保存失败'));
                    }
                }
            }
        }

        //保存SKU
        $skuList  = json_decode($data['skuList'],true);
        if(is_array($skuList) && !empty($skuList)){
            $validSkuId = [];
            foreach($skuList as $sku){
                $sku['id'] = intval($sku['id']);
                if($sku['id']){
                    $skuModel = ProductSkuModel::findFirst([
                        'productId=:id: and id=:kid:',
                        'bind'=>['id' => $data['id'], 'kid'=>intval($sku['id'])]
                    ]);
                }else{
                    $skuModel = new ProductSkuModel();
                    $skuModel->productId = $productModel->id;
                }

                $skuModel->setTransaction($transaction);
                $skuModel->price  = floatval($sku['price']);
                $skuModel->cost   = floatval($sku['cost']);
                $skuModel->originalSkuId = trim($sku['originalSkuId']);
                //JSON串拼接
                $skuJson = [];
                foreach($sku as $key=>$value){
                    $matches = null;
                    preg_match('/^prop(\d+)$/',$key,$matches);
                    if($matches){
                        $skuJson[] = ['prop'=>$matches[1],'value'=>$value];
                    }
                }
                $skuModel->skuJson = json_encode($skuJson);
                $skuModel->skuSn   = $this->createSkuSn($productModel->barcode,$skuJson,$productModel->propsetId);
                $result = $skuModel->save();
                if(!$result){
                    $transaction->rollback($this->translator->_('商品SKU保存失败'));
                }else{
                    $validSkuId[] = $skuModel->id;
                }
            }
            if(!empty($validSkuId)){
                //清掉其他
                $invalidSkuList = ProductSkuModel::find([
                    'productId=:id: and id not in ({ids:array})',
                    'bind'=>['id' => $data['id'], 'ids'=>$validSkuId]
                ]);
                if($invalidSkuList){
                    foreach($invalidSkuList as $sku){
                        $sku->setTransaction($transaction);
                        $result = $sku->delete();
                        if(!$result){
                            $transaction->rollback($this->translator->_('商品SKU清理失败'));
                        }
                    }
                }
            }
        }

        $transaction->commit();
        return true;
    }
    /**
     * 获取指定商品
     */
    public function getProduct(){
        $data  = $this->_toParamObject($this->getParams());
        $model = new ProductModel();
        $columns  = array_values($model->columnMap());
        unset($columns['isDeleted']);
        $columns[] = '(select group_concat_orderby(name,leftPosition,:sp:) from '.ItemCatalogModel::class.' where leftPosition<=(select leftPosition from '.ItemCatalogModel::class.' where '.ItemCatalogModel::class.'.id='.ProductModel::class.'.catalogId)  and rightPosition>=(select rightPosition from '.ItemCatalogModel::class.' where '.ItemCatalogModel::class.'.id='.ProductModel::class.'.catalogId) order by leftPosition asc) as catalogNamePath';

        $searcher  = $model::query();
        $searcher->limit(1,0);
        $searcher->columns($columns);
        $searcher->bind(['id'=>$data['id'],'sp'=>"/"]);
        $searcher->where(ProductModel::class.'.id=:id: and '.ProductModel::class.'.isDeleted=0');
        $result= $searcher->execute();
        $rows  = $result?$result->toArray():[];
//        $row   = $model::findFirst([
//            'id=:id: and isDeleted=0',
//            'bind'=>[
//                'id'=>$data['id']
//            ],
//            'columns'=>$columns
//        ]);
        if(!$rows){
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $row = $rows[0];
        $returnData = $rows[0];
        //效果图
        $imgList    = ProductImgModel::find([
            'productId=:pid:',
            'columns'=>[
                'id','imgUrl','isFirst','videoUrl'
            ],
            'order'=>'isFirst desc',
            'bind'=>['pid'=>$row['id']]
        ]);
        $returnData['imgList'] = $imgList?$imgList->toArray():[];
        //内容描述
        $descRow = ProductDescModel::findFirst([
            'productId=:pid:',
            'columns'=>[
                'content','mobileContent'
            ],
            'bind'=>['pid'=>$row['id']]
        ]);
        $returnData['content']       = $descRow?$descRow->content:'';
        $returnData['mobileContent'] = $descRow?$descRow->mobileContent:'';
        //属性
        $propsList = ProductPropModel::find([
            'productId=:pid: and isSaleProp=0',
            'columns'=>[
                'propkeyId','propvalue'
            ],
            'bind'=>['pid'=>$row['id']]
        ]);
        $returnData['propList'] = $propsList? $propsList->toArray(): [];

        $returnData['skuList']   = ProductSkuModel::find([
            'productId=:pid:',
            'columns'=>[
                'price','cost','originalSkuId','skuJson','id','skuSn'
            ],
            'bind'=>['pid'=>$row['id']]
        ]);


        //API：调用属性集服务
//        $response = $this->apiRequest('product.propset.get',['id'=>$returnData['propsetId'],'loadPropvalue'=>1]);
//        $response = json_decode($response,true);
//        if($response['status']==0 && $response['data']){
//            $propkeyList = $response['data']['propkeyList'];
//        }

        return $returnData;

    }

    /**
     * 列出商品列表
     */
    public function listProducts(){
        $data = $this->_toParamObject($this->getParams());
        $data['limit'] = $data['limit']>0?$data['limit']:10;
        $data['page']  = $data['page']>0?$data['page']:1;

        $model    = new ProductModel();
        $searcher = $model::query();
        $searcher->where(ProductModel::class.'.isDeleted=0');
        $columns  = array_values($model->columnMap());
        unset($columns['isDeleted']);
        $columns  = array_map(function($v){
            return ProductModel::class.'.'.$v;
        },$columns);
        $searcher->columns(['count(0) as total']);
        $result = $searcher->execute();
        $returnData['total']  = $result->getFirst()->total;

        $columns[] = '(select imgUrl from '.ProductImgModel::class.' where productId='.ProductModel::class.'.id and isFirst=1) as firstImgUrl';
        //$columns[] = '(select group_concat(name order by leftPosition asc separator :sp:) from '.ItemCatalogModel::class.' where leftPosition<=(select leftPosition from '.ItemCatalogModel::class.' where id='.ProductModel::class.'.id)  and rightPosition>=(select rightPosition from '.ItemCatalogModel::class.' where id='.ProductModel::class.'.id) order by leftPosition asc) as catalogNamePath';
        $columns[] = '(select group_concat_orderby(name,leftPosition,:sp:) from '.ItemCatalogModel::class.' where leftPosition<=(select leftPosition from '.ItemCatalogModel::class.' where '.ItemCatalogModel::class.'.id='.ProductModel::class.'.catalogId)  and rightPosition>=(select rightPosition from '.ItemCatalogModel::class.' where '.ItemCatalogModel::class.'.id='.ProductModel::class.'.catalogId) order by leftPosition asc) as catalogNamePath';
        $searcher->bind(['sp'=>"/"]);
        $searcher->columns($columns);
        $searcher->limit($data['limit'],($data['page'] - 1) * $data['limit']);
        $searcher->orderBy(ProductModel::class.'.sortWeight desc,'.ProductModel::class.'.id desc');

        $result = $searcher->execute();
        $returnData['list'] = $result->toArray();
        $returnData['page'] = $data['page'];
        $returnData['limit'] = $data['limit'];
        return $returnData;
    }

    /**
     * 删除商品，只是做假删除
     */
    public function remove(){
        $data  = $this->_toParamObject($this->getParams());
        $model = ProductModel::findFirst([
            'id=:id: and isDeleted=0',
            'bind'=>['id'=>$data['id']]
        ]);
        if(!$model){
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $result = false;
        if($data['isPhysical']){
            $result = $model->delete();
        }else{
            $model->isDeleted = 1;
            $result = $model->update();
        }
        return $result;
    }

    /**
     * 商品上架或下架
     */
    public function online(){
        $data  = $this->_toParamObject($this->getParams());
        $model = ProductModel::findFirst([
            'id=:id: and isDeleted=0',
            'bind'=>['id'=>$data['id']]
        ]);
        if(!$model){
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $model->isOnline = intval($data['isOnline'])>0?1:0;
        $result = $model->update();
        return $result;
    }
}