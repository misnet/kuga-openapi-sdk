<?php
namespace Kuga\Core\Product;
use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\DataExtendTrait;

/**
 * 属性集名称
 * Class PropSetModel
 * @package Kuga\Core\Product
 */
class PropSetModel extends AbstractModel {
    use DataExtendTrait;

    /**
     *
     * @var integer
     */
    public $id;
    /**
     * 属性模板名称名称
     * @var string
     */
    public $name;


    public function getSource() {
        return 't_mall_propset';
    }
    public function initialize(){
        parent::initialize();
        $this->hasMany('id', 'PropSetItemModel', 'propsetId');
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        $data =  array (
            'id' => 'id',
            'name'=>'name'
        );
        return array_merge($data,$this->extendColumnMapping());
    }
}
