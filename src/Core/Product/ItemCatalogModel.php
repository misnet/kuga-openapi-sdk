<?php
/**
 * Product Item Catalog Model
 * @author Donny
 */

namespace Kuga\Core\Product;

use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractCatalogModel;
use Kuga\Core\Base\DataExtendTrait;
use Phalcon\Mvc\Model\Message;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf as PresenceOfValidator;
use Phalcon\Validation\Validator\Uniqueness as UniquenessValidator;

class ItemCatalogModel extends AbstractCatalogModel
{
    use DataExtendTrait;
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
    public function beforeCreate(){
        parent::beforeCreate();
        $this->leftPosition = $this->rightPosition = 0;
        return true;
    }

}