<?php
namespace Kuga\Core\Shop;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\DataExtendTrait;
use Kuga\Core\Base\RegionTrait;
use Phalcon\Mvc\Model\Message;
use Phalcon\Validation;

/**
 * 店仓
 * Class StoreModel
 * @package Kuga\Core\Shop
 */
class StoreModel extends AbstractModel {
    use DataExtendTrait;
    use RegionTrait;

    /**
     *
     * @var integer
     */
    public $id;

    /**
     * 名称
     * @var string
     */
    public $name;

    /**
     * 具体地址
     * @var
     */

    public $address;
    /**
     * 启用或禁用
     * @var int 1禁用，0启用
     */
    public $disabled = 0;
    /**
     * @var int
     */
    public $isRetail = 0;
    /**
     * 摘要
     * @var String
     */
    public $summary;


    public function getSource() {
        return 't_mall_stores';
    }
    public function initialize(){
        parent::initialize();
    }
    public function validate(\Phalcon\ValidationInterface $validator)
    {
        $validator->add('name',new Validation\Validator\PresenceOf([
            'model' => $this,
            'message' => $this->translator->_('请输入店仓名称')
        ]));
        $validator->add('regionCode',new Validation\Validator\PresenceOf([
            'model' => $this,
            'message' => $this->translator->_('店仓所在地区未设定')
        ]));
        return $this->validate($validator);
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        $data = array (
            'id' => 'id',
            'name' => 'name',
            'disabled'=>'disabled',
            'is_retail'=>'isRetail',
            'address'=>'address',
            'summary'=>'summary'
        );
        return array_merge($data,$this->extendColumnMapping(),$this->regionColumnMapping());
    }
}
