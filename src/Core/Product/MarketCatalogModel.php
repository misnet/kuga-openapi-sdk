<?php
/**
 * Product Item Catalog for Marketing
 * @author Donny
 */

namespace Kuga\Core\Product;

use Kuga\Core\Base\AbstractCatalogModel;
use Phalcon\Mvc\Model\Message;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf as PresenceOfValidator;

class MarketCatalogModel extends AbstractCatalogModel
{
    /**
     * 创建时间
     * @var integer
     */
    public $createTime;
    public function columnMap()
    {
        $returnData = parent::columnMap();
        $returnData['create_time'] = 'createTime';
        return $returnData;
    }
    public function getSource() {
        return 't_mall_marketcatalogs';
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
        $this->createTime||$this->createTime = time();
        $this->leftPosition = $this->rightPosition = 0;
        return true;
    }

}