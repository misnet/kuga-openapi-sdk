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
use Kuga\Core\Product\PropValueModel;

class Props extends BaseApi
{
    /**
     * 创建属性
     * @return bool
     * @throws Exception
     */
    public function createProp()
    {
        $data = $this->_toParamObject($this->getParams());
        $model = new PropKeyModel();
        $model->initData($data->toArray(), ['id']);
        $result = $model->create();
        if (!$result) {
            throw new ApiException($model->getMessages()[0]->getMessage());
        }
        return $result;
    }

    /**
     * 更新属性
     * @return mixed
     * @throws Exception
     */
    public function updateProp()
    {
        $data = $this->_toParamObject($this->getParams());
        $catalog = PropKeyModel::findFirstById($data['id']);
        if (!$catalog) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $catalog->initData($data->toArray(), ['id']);
        $result = $catalog->update();
        if (!$result) {
            throw new ApiException($catalog->getMessages()[0]->getMessage());
        }
        return $result;
    }

    /**
     * 移除属性
     */
    public function removeProp()
    {
        $data = $this->_toParamObject($this->getParams());
        $catalog = PropKeyModel::findFirstById($data['id']);
        if (!$catalog) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $result = $catalog->delete();
        if (!$result) {
            throw new ApiException($catalog->getMessages()[0]->getMessage());
        }
        return $result;
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
        if ($data['catalogId']) {
            $condition = 'catalogId=:cid:';
            $bind = ['cid' => $data['catalogId']];
        } else {
            $bind = null;
            $condition = '1=1';
        }
        $total = PropKeyModel::count([
            'bind' => $bind,
            'condition' => $condition
        ]);
        $searcher = PropKeyModel::query();
        $searcher->join(ItemCatalogModel::class, 'catalogId=cata.id', 'cata');
        $searcher->columns([
            PropKeyModel::class . '.id',
            PropKeyModel::class . '.name',
            PropKeyModel::class . '.isColor',
            PropKeyModel::class . '.catalogId',
            PropKeyModel::class . '.formType',
            PropKeyModel::class . '.isApplyCode',
            PropKeyModel::class . '.isSaleProp',
            PropKeyModel::class . '.sortWeight',
            PropKeyModel::class . '.usedForSearch',
            'cata.name as catalogName'
        ]);
        $searcher->where($condition);
        if ($bind) {
            $searcher->bind($bind);
        }
        $searcher->limit($data['limit'], ($data['page'] - 1) * $data['limit']);
        $searcher->orderBy(PropKeyModel::class.'.sortWeight desc');
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
     * 创建属性值
     * @return bool
     * @throws Exception
     */
    public function createPropValue()
    {
        $data = $this->_toParamObject($this->getParams());
        $model = new PropValueModel();
        $model->initData($data->toArray(), ['id']);
        $result = $model->create();
        if (!$result) {
            throw new ApiException($model->getMessages()[0]->getMessage());
        }
        return $result;
    }

    /**
     * 更新属性值
     * @return mixed
     * @throws Exception
     */
    public function updatePropValue()
    {
        $data = $this->_toParamObject($this->getParams());
        $catalog = PropValueModel::findFirstById($data['id']);
        if (!$catalog) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $catalog->initData($data->toArray(), ['id']);
        $result = $catalog->update();
        if (!$result) {
            throw new ApiException($catalog->getMessages()[0]->getMessage());
        }
        return $result;
    }

    /**
     * 移除属性值
     */
    public function removePropValue()
    {
        $data = $this->_toParamObject($this->getParams());
        $catalog = PropValueModel::findFirstById($data['id']);
        if (!$catalog) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $result = $catalog->delete();
        if (!$result) {
            throw new ApiException($catalog->getMessages()[0]->getMessage());
        }
        return $result;
    }

    /**
     * 列出属性值
     * @return array
     * @throws Exception
     */
    public function listPropValues()
    {
        $data = $this->_toParamObject($this->getParams());
        $data['page'] || $data['page'] = 1;
        $data['limit'] || $data['limit'] = GlobalVar::DATA_DEFAULT_LIMIT;
        if ($data['propkeyId']) {
            $condition = 'propkeyId=:cid:';
            $bind = ['cid' => $data['propkeyId']];
        } else {
            $bind = null;
            $condition = '1=1';
        }
        $total = PropValueModel::count([
            'bind' => $bind,
            'condition' => $condition
        ]);
        $searcher = PropValueModel::query();
        $searcher->join(PropKeyModel::class, 'propkeyId=pk.id', 'pk');
        $searcher->columns([
            PropValueModel::class . '.id',
            PropValueModel::class . '.propvalue',
            PropValueModel::class . '.colorHexValue',
            PropValueModel::class . '.sortWeight',
            PropValueModel::class . '.summary',
            PropValueModel::class . '.code',
            PropValueModel::class . '.propkeyId',
            'pk.isColor',
            'pk.name as propkeyName'
        ]);
        $searcher->where($condition);
        if ($bind) {
            $searcher->bind($bind);
        }
        $searcher->limit($data['limit'], ($data['page'] - 1) * $data['limit']);
        $searcher->orderBy(PropValueModel::class .'.sortWeight desc');
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