<?php
/**
 * 店仓相关 API
 */

namespace Kuga\Api\Console;

use Kuga\Core\Api\Exception as ApiException;
use Kuga\Core\RegionModel;
use Kuga\Core\Shop\StoreModel;

class Store extends BaseApi
{

    /**
     * 创建店仓
     * @return bool
     * @throws ApiException
     */
    public function createStore()
    {
        $data = $this->_toParamObject($this->getParams());
        $model = new StoreModel();
        $model->initData($data->toArray(), ['id','isDeleted','updateTime','createTime']);
        $result = $model->create();
        if (!$result) {
            throw new ApiException($model->getMessages()[0]->getMessage());
        }
        return true;
    }

    /**
     * 更新店仓
     * @return bool
     * @throws ApiException
     */
    public function updateStore()
    {
        $data = $this->_toParamObject($this->getParams());
        $obj = StoreModel::findFirstById($data['id']);
        if (!$obj) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $obj->initData($data->toArray(), ['id','isDeleted','updateTime','createTime']);
        $result = $obj->update();
        if (!$result) {
            throw new ApiException($obj->getMessages()[0]->getMessage());
        }
        return true;
    }

    /**
     * 移除属性，逻辑删除
     */
    public function removeStore()
    {
        $data = $this->_toParamObject($this->getParams());
        $obj  = StoreModel::findFirstById($data['id']);
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
    public function getStore(){
        $data = $this->_toParamObject($this->getParams());
        $obj  = StoreModel::findFirst([
            'id=:id:',
            'bind'=>['id'=>$data['id']]
        ]);
        if(!$obj){
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $returnData = $obj->toArray();
        return $returnData;

    }

    /**
     * 列出店仓
     * @return array
     * @throws ApiException
     */
    public function listStores()
    {
        $data = $this->_toParamObject($this->getParams());
        $data['page'] || $data['page'] = 1;
        $data['limit'] || $data['limit'] = GlobalVar::DATA_DEFAULT_LIMIT;

        $total = StoreModel::count([
            'isDeleted = 0'
        ]);
        $searcher = StoreModel::query();
        $searcher->join(RegionModel::class,'c.id='.StoreModel::class.'.countryId','c','left');
        $searcher->join(RegionModel::class,'p.id='.StoreModel::class.'.provinceId','p','left');
        $searcher->join(RegionModel::class,'ct.id='.StoreModel::class.'.cityId','ct','left');
        $searcher->where(StoreModel::class .'.isDeleted=0');
        $searcher->limit($data['limit'], ($data['page'] - 1) * $data['limit']);
        $searcher->orderBy(StoreModel::class.'.id desc');
        $searcher->columns([
            StoreModel::class.'.id',
            StoreModel::class.'.name',
            'summary',
            'disabled',
            'isRetail',
            'countryId',
            'provinceId',
            'cityId',
            'address',
            'if(countryId=0,"中国",c.name) as countryName',
            'if(provinceId=0,"",p.name) as provinceName',
            'if(cityId=0,"",ct.name) as cityName'
        ]);
        $result = $searcher->execute();
        $list = $result ? $result->toArray() : [];
        return [
            'list' => $list,
            'total' => $total,
            'page' => $data['page'],
            'limit' => $data['limit']
        ];
    }

}