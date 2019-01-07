<?php
/**
 * Product Item Catalog Model
 * @author Donny
 */

namespace Kuga\Core\Product;

use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractCatalogModel;
use Kuga\Core\Base\DataExtendTrait;
use Kuga\Core\Base\ModelException;
use Phalcon\Mvc\Model\Message;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf as PresenceOfValidator;
use Phalcon\Validation\Validator\Uniqueness as UniquenessValidator;

class ItemCatalogModel extends AbstractCatalogModel
{
    use DataExtendTrait;
    const MAX_DEPTH = 3;
    /**
     * 类目使用的属性集ID
     * @var integer
     */
    public $propsetId;
    public function columnMap()
    {
        $returnData = parent::columnMap();
        $returnData['code'] = 'code';
        $returnData['propset_id'] = 'propsetId';
        return array_merge($returnData,$this->extendColumnMapping());
    }
    public function getSource() {
        return 't_mall_itemcatalogs';
    }
    public function validation()
    {
        $validator = new Validation();
        $validator->add('name', new PresenceOfValidator([
            'model' => $this,
            'message' => $this->translator->_('类目名称必须填写')
        ]));

        if(!$this->id)
            $sameNameNum = self::count([
                'name=:n: and parentId=:p:',
                'bind' => ['p' => $this->parentId, 'n' => $this->name]
            ]);
        else{
            $sameNameNum = self::count([
                'name=:n: and parentId=:p: and id!=:id:',
                'bind' => ['p' => $this->parentId, 'n' => $this->name,'id'=>$this->id]
            ]);
        }
        if ($sameNameNum) {
            $this->appendMessage(new Message($this->translator->_('存在同名类目名称')));
            return false;
        }
        return $this->validate($validator);
    }
    public function beforeDelete(){
        //找出最根的结点
        $sql = 'select node.id from '.self::class.' parent,'.self::class.' node';
        $sql.= ' where parent.id=:id: and node.rightPosition = node.leftPosition + 1 ';
        $sql.=' and node.leftPosition>parent.leftPosition';
        $sql.=' and node.rightPosition<parent.rightPosition';
        $query = $this->getModelsManager()->createQuery($sql);
        $result = $query->execute(['id'=>$this->id]);
        $resultRow = $result->toArray();
        $ids = [];
        foreach($resultRow as $row){
            $ids[] = $row['id'];
        }
        if(!$ids){
            $ids[] = $this->id;
        }
        $productNum = ProductModel::count([
            'catalogId in ({ids:array})',
            'bind'=>['ids'=>$ids]
        ]);
        if($productNum > 0 ){
            throw new ModelException($this->translator->_('本类目或子类目被%num%个商品引用，不可删除',['num'=>$productNum]));
        }
        return true;
    }
    public function beforeCreate(){
        parent::beforeCreate();
        $this->leftPosition = $this->rightPosition = 0;
        return true;
    }
    public function beforeSave(){
        parent::beforeSave();
        //计算深度不要超过3
        $sql = 'select count(0) as depth from '.self::class.' parent,'.self::class.' node';
        $sql.= ' where node.id=:id:';
        $sql.=' and node.leftPosition>parent.leftPosition';
        $sql.=' and node.rightPosition<parent.rightPosition group by node.id';
        $query = $this->getModelsManager()->createQuery($sql);
        $result = $query->execute(['id'=>$this->parentId]);
        $resultRow = $result->toArray();
        if(!empty($resultRow) && $resultRow[0]['depth'] + 1 >=self::MAX_DEPTH){
            throw new ModelException($this->translator->_('类目层级深度不可超过3级'));
        }
        return true;
    }

}