<?php
/**
 * Created by PhpStorm.
 * User: donny
 * Date: 2018/12/7
 * Time: 2:07 PM
 */
namespace Kuga\Api\Console;
use Kuga\Core\Api\Exception as ApiException;
use Kuga\Core\Api\Exception;
use Kuga\Core\Product\ItemCatalogModel;

class ItemCatalog extends BaseApi {
    /**
     * 创建类目
     * @return bool
     * @throws Exception
     */
    public function create(){
        $data                 = $this->_toParamObject($this->getParams());
        $model                = new ItemCatalogModel();
        $model->initData($data->toArray(),['id','createTime','updateTime']);
        $result               = $model->create();
        if ( ! $result) {
            throw new ApiException($model->getMessages()[0]->getMessage());
        }
        return $result;
    }

    /**
     * 更新类目
     * @return mixed
     * @throws Exception
     */
    public function update(){
        $data                 = $this->_toParamObject($this->getParams());
        $catalog = ItemCatalogModel::findFirstById($data['id']);
        if(!$catalog){
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $catalog->initData($data->toArray(),['id','createTime','updateTime']);
        $result               = $catalog->update();
        if ( ! $result) {
            throw new ApiException($catalog->getMessages()[0]->getMessage());
        }
        return $result;
    }

    /**
     * 移除类目
     */
    public function remove(){
        $data                 = $this->_toParamObject($this->getParams());
        $catalog = ItemCatalogModel::findFirstById($data['id']);
        if(!$catalog){
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $result = $catalog->delete();
        if ( ! $result) {
            throw new ApiException($catalog->getMessages()[0]->getMessage());
        }
        return $result;
    }

    private function _recurisiveCatalogTree($parentId=0){
        $list   = ItemCatalogModel::find([
            'parentId=:id:',
            'bind'=>['id'=>$parentId],
            'order'=>'sortWeight desc'
        ]);
        $list?$list=$list->toArray():$list=[];
        if(!empty($list)){
            foreach($list as &$item){
                $childNodes = ItemCatalogModel::find([
                    'parentId=:pid:',
                    'bind'=>['pid'=>$item['id']],
                    'order'=>'sortWeight desc'
                ]);
                $item['isLeaf']   = !!$childNodes;
                $item['children'] = $this->_recurisiveCatalogTree($item['id']);
            }
        }
        return $list;
    }
    public function listCatalogs(){
        $data   = $this->_toParamObject($this->getParams());
        if($data['loadTree'])
            $list   = $this->_recurisiveCatalogTree($data['parentId']);
        else{
            $list   = ItemCatalogModel::find([
                'parentId=:id:',
                'bind'=>['id'=>$data['parentId']],
                'order'=>'sortWeight desc'
            ]);
            $total  = ItemCatalogModel::count([
                'parentId=:id:',
                'bind'=>['id'=>$data['parentId']]
            ]);
            $list?$list=$list->toArray():$list=[];
            if($list){
                foreach($list as &$item){
                    $childNum = ItemCatalogModel::count([
                        'parentId=:pid:',
                        'bind'=>['pid'=>$item['id']]
                    ]);
                    $item['isLeaf'] = $childNum==0;
                }
            }

        }
        return $list;


    }
}