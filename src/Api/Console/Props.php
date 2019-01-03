<?php
/**
 * 类目属性API
 */

namespace Kuga\Api\Console;

use Kuga\Core\Api\Exception as ApiException;
use Kuga\Core\Api\Exception;
use Kuga\Core\GlobalVar;
use Kuga\Core\Product\ItemCatalogModel;
use Kuga\Core\Product\PropKeyModel;
use Kuga\Core\Product\PropSetItemModel;
use Kuga\Core\Product\PropSetModel;
use Kuga\Core\Product\PropValueModel;

class Props extends BaseApi
{
    /**
     * @param array $values
     * @param $propkeyId
     * @param $transaction
     */
    private function _updatePropValues($valueList,$propkeyId,$transaction){
        if(!empty($valueList) && is_array($valueList)){
            $i = sizeof($valueList);
            $validValueIdList = [];
            foreach($valueList as $propValue){
                if(intval($propValue['id'])==0||!preg_match('/^(\d+)$/',$propValue['id'])){
                    $propValue['id'] = null;
                    $valueRow = null;
                }else{
                    $valueRow = PropValueModel::findFirstById($propValue['id']);
                }
                if(!$valueRow){
                    $valueRow = new PropValueModel();
                }
                if($transaction){
                    $valueRow->setTransaction($transaction);
                }
                $valueRow->propvalue = $propValue['propvalue'];
                $valueRow->propkeyId = $propkeyId;
                $valueRow->code      = $propValue['code'];
                $valueRow->colorHexValue = $propValue['colorHexValue'];
                $valueRow->sortWeight    = $i;
                $result = $valueRow->save();
                $i--;
                if(!$result){
                    $transaction->rollback($valueRow->getMessages()[0]->getMessage());
                }
                $validValueIdList[] = $valueRow->id;
            }
            $tobeDeletedList = PropValueModel::find([
                'id not in ({ids:array}) and propkeyId=:pid:',
                'bind'=>['ids'=>$validValueIdList,'pid'=>$propkeyId]
            ]);
            if($tobeDeletedList){
                foreach($tobeDeletedList as $delItem){
                    $delItem->setTransaction($transaction);
                    $d = $delItem->delete();
                    if(!$d){
                        $transaction->rollback($this->translator->_('属性值移除失败'));
                    }
                }
            }
        }
    }

    /**
     * @param array $propKeyList
     * @param integer $propsetId 集合id
     * @param $transaction 事务
     */
    private function _updatePropKeyInPropSet($propKeyList,$propsetId,$transaction){
        if(!empty($propKeyList) && is_array($propKeyList)){
            $i = sizeof($propKeyList);
            $validPropKeyIdList = [];
            foreach($propKeyList as $propkey){
                if(intval($propkey['id'])==0||!preg_match('/^(\d+)$/',$propkey['id'])){
                    $propkey['id'] = null;
                    $objRow = null;
                }else{
                    $objRow = PropSetItemModel::findFirst([
                       'propsetId=:psid: and propkeyId=:pkid:',
                       'bind'=>['psid'=>$propsetId, 'pkid' => $propkey['propkeyId']]
                    ]);
                }
                if(!$objRow){
                    $objRow = new PropSetItemModel();
                }
                if($transaction){
                    $objRow->setTransaction($transaction);
                }
                $objRow->propsetId = $propsetId;
                $objRow->propkeyId = $propkey['propkeyId'];
                $objRow->isApplyCode     = intval($propkey['isApplyCode']);
                $objRow->usedForSearch   = intval($propkey['usedForSearch']);
                $objRow->disabled        = intval($propkey['disabled']);
                $objRow->isSaleProp      = intval($propkey['isSaleProp']);
                $objRow->isRequired      = intval($propkey['isRequired']);
                $objRow->sortWeight    = $i;
                $result = $objRow->save();
                $i--;
                if(!$result){
                    $transaction->rollback($objRow->getMessages()[0]->getMessage());
                }
                $validPropKeyIdList[] = $objRow->id;
            }
            $tobeDeletedList = PropSetItemModel::find([
                'id not in ({ids:array}) and propsetId=:pid:',
                'bind'=>['ids'=>$validPropKeyIdList,'pid'=>$propsetId]
            ]);
            if($tobeDeletedList){
                foreach($tobeDeletedList as $delItem){
                    $delItem->setTransaction($transaction);
                    $d = $delItem->delete();
                    if(!$d){
                        $transaction->rollback($this->translator->_('属性集合中的属性移除失败'));
                    }
                }
            }
        }
    }
    /**
     * 创建属性
     * 属性值以JSON字串 形式存在valueList子对象中
     * @return bool
     * @throws Exception
     */
    public function createProp()
    {
        $tx = $this->_di->getShared('transactions');
        $transaction = $tx->get();


        $data = $this->_toParamObject($this->getParams());
        $model = new PropKeyModel();
        $model->initData($data->toArray(), ['id','isDeleted','updateTime','createTime']);
        $model->setTransaction($transaction);
        $result = $model->create();
        if (!$result) {
            $transaction->rollback($model->getMessages()[0]->getMessage());
        }else{
            $valueList = json_decode($data['valueList'],true);
            $this->_updatePropValues($valueList,$model->id,$transaction);
        }
        $transaction->commit();
        return true;
    }

    /**
     * 更新属性
     * @return mixed
     * @throws Exception
     */
    public function updateProp()
    {
        $tx = $this->_di->getShared('transactions');
        $transaction = $tx->get();
        $data = $this->_toParamObject($this->getParams());
        $obj = PropKeyModel::findFirstById($data['id']);
        if (!$obj) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $obj->setTransaction($transaction);
        $obj->initData($data->toArray(), ['id','isDeleted','updateTime','createTime']);
        $result = $obj->update();
        if (!$result) {
            $transaction->rollback($obj->getMessages()[0]->getMessage());
        }else{
            $valueList = json_decode($data['valueList'],true);
            $this->_updatePropValues($valueList,$obj->id,$transaction);
        }
        $transaction->commit();
        return true;
    }

    /**
     * 移除属性，逻辑删除
     */
    public function removeProp()
    {
        $data = $this->_toParamObject($this->getParams());
        $obj  = PropKeyModel::findFirstById($data['id']);
        if (!$obj) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $obj->isDeleted = 1;
        $result = $obj->update();
        if (!$result) {
            throw new ApiException($obj->getMessages()[0]->getMessage());
        }
        return $result;
    }

    /**
     * 获得指定的属性及期属性值列表
     */
    public function getProp(){
        $data = $this->_toParamObject($this->getParams());
        $obj  = PropKeyModel::findFirst([
            'id=:id:',
            'bind'=>['id'=>$data['id']],
            'columns'=>'id,name,summary,isColor,formType'
        ]);
        if(!$obj){
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $valueList = PropValueModel::find([

            'propkeyId=:pkid: and isDeleted=0',
            'bind'=>[ 'pkid' => $obj->id],
            'columns'=>'id,code,colorHexValue,propvalue',
            'order'=>'sortWeight desc'
        ]);
        $returnData = $obj->toArray();
        $returnData['valueList'] = $valueList->toArray();
        return $returnData;

    }
    /**
     * 列出属性
     * @return array
     * @throws Exception
     */
    public function listProps()
    {
        $data = $this->_toParamObject($this->getParams());
        $data['page'] || $data['page'] = 1;
        $data['limit'] || $data['limit'] = GlobalVar::DATA_DEFAULT_LIMIT;

        $total = PropKeyModel::count();
        $searcher = PropKeyModel::query();
        $searcher->where(PropKeyModel::class .'.isDeleted=0');
        $searcher->columns([
            PropKeyModel::class . '.id',
            PropKeyModel::class . '.name',
            PropKeyModel::class . '.isColor',
            PropKeyModel::class . '.formType',
            PropKeyModel::class . '.summary',
            '(select count(0) from '.PropValueModel::class.' where propkeyId='.PropKeyModel::class.'.id and '.PropKeyModel::class.'.isDeleted=0) as cntValues'
        ]);
        $searcher->limit($data['limit'], ($data['page'] - 1) * $data['limit']);
        $searcher->orderBy(PropKeyModel::class.'.id desc');
        $result = $searcher->execute();
        $list = $result ? $result->toArray() : [];
        return [
            'list' => $list,
            'total' => $total,
            'page' => $data['page'],
            'limit' => $data['limit']
        ];
    }

    /**
     * 创建属性集
     * @return bool
     * @throws Exception
     */
    public function createPropSet()
    {
        $tx = $this->_di->getShared('transactions');
        $transaction = $tx->get();
        $data = $this->_toParamObject($this->getParams());
        $model = new PropSetModel();
        $model->setTransaction($transaction);
        $model->initData($data->toArray(), ['id']);
        $result = $model->create();
        if (!$result) {
            throw new ApiException($model->getMessages()[0]->getMessage());
        }else{
            $list = json_decode($data['propkeyList'],true);
            $this->_updatePropKeyInPropSet($list,$model->id,$transaction);
        }
        $transaction->commit();
        return true;
    }

    /**
     * 更新属性集
     * @return mixed
     * @throws Exception
     */
    public function updatePropSet()
    {
        $tx = $this->_di->getShared('transactions');
        $transaction = $tx->get();
        $data = $this->_toParamObject($this->getParams());
        $model = PropSetModel::findFirstById($data['id']);
        if (!$model) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }else{
            $valueList = json_decode($data['valueList'],true);
            $this->_updatePropValues($valueList,$model->id,$transaction);
        }
        $model->setTransaction($transaction);
        $model->initData($data->toArray(), ['id']);
        $result = $model->update();
        if (!$result) {
            throw new ApiException($model->getMessages()[0]->getMessage());
        }else{
            $list = json_decode($data['propkeyList'],true);
            $this->_updatePropKeyInPropSet($list,$model->id,$transaction);
        }
        $transaction->commit();
        return true;
    }

    /**
     * 移除属性值
     */
    public function removePropSet()
    {
        $data = $this->_toParamObject($this->getParams());
        $obj  = PropSetModel::findFirstById($data['id']);
        if (!$obj) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $obj->isDeleted = 1;
        $result = $obj->update();
        if (!$result) {
            throw new ApiException($obj->getMessages()[0]->getMessage());
        }
        return $result;
    }

    /**
     * 列出属性集
     * @return array
     * @throws Exception
     */
    public function listPropSets()
    {
        $data = $this->_toParamObject($this->getParams());
        $data['page'] || $data['page'] = 1;
        $data['limit'] || $data['limit'] = GlobalVar::DATA_DEFAULT_LIMIT;
        $total = PropSetModel::count([
            'condition' => 'isDeleted=0'
        ]);
        $searcher = PropSetModel::query();
        $searcher->columns([
            PropSetModel::class . '.id',
            PropSetModel::class . '.name',
            '(select count(0) from '.PropSetItemModel::class.' where propsetId='.PropSetModel::class.'.id) as cntPropKey'
        ]);
        $searcher->where(PropSetModel::class.'.isDeleted=0');
        $searcher->limit($data['limit'], ($data['page'] - 1) * $data['limit']);
        $searcher->orderBy(PropSetModel::class .'.id desc');
        $result = $searcher->execute();
        $list = $result ? $result->toArray() : [];
        return [
            'list' => $list,
            'total' => $total,
            'page' => $data['page'],
            'limit' => $data['limit']
        ];
    }

    /**
     * 获得指定的属性集合及期属性列表
     */
    public function getPropSet(){
        $data = $this->_toParamObject($this->getParams());
        $obj  = PropSetModel::findFirst([
            'id=:id:',
            'bind'=>['id'=>$data['id']],
            'columns'=>'id,name'
        ]);
        if(!$obj){
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $searcher = PropSetItemModel::query();
        $searcher->join(PropKeyModel::class,'propkeyId=pk.id and pk.isDeleted=0','pk','left');
        $searcher->where('propsetId=:psid:');
        $searcher->bind(['psid'=>$obj->id]);
        $searcher->columns([
            'pk.name as propkeyName',
            'pk.formType',
            'pk.isColor',
            PropSetItemModel::class.'.id',
            PropSetItemModel::class.'.propkeyId',
            PropSetItemModel::class.'.isRequired',
            PropSetItemModel::class.'.isApplyCode',
            PropSetItemModel::class.'.disabled',
            PropSetItemModel::class.'.usedForSearch',
            PropSetItemModel::class.'.isSaleProp'
        ]);
        $searcher->orderBy(PropSetItemModel::class.'.sortWeight desc');
        $result = $searcher->execute();
        $returnData = $obj->toArray();
        $returnData['propkeyList'] = $result?$result->toArray():[];

        //是否载入属性值列表
        if($data['loadPropvalue'] && $returnData['propkeyList']){
            foreach($returnData['propkeyList'] as &$propkey){
                $propkey['valueList'] = PropValueModel::find([
                    'propkeyId=:pkid: and isDeleted=0',
                    'bind'=>[ 'pkid' => $propkey['propkeyId']],
                    'columns'=>'id,code,colorHexValue,propvalue',
                    'order'=>'sortWeight desc'
                ]);
            }
        }
        return $returnData;

    }
}