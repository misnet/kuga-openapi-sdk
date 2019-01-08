<?php
namespace Kuga\Core\Shop;
use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractModel;

/**
 * 商品属性Model
 * Class ProductPropModel
 * @package Kuga\Core\Product
 */
class ProductPropModel extends AbstractModel {
    /**
     *
     * @var integer
     */
    public $id;
    /**
     * 商品ID
     * @var integer
     */
    public $productId;
    /**
     * 属性ID
     * @var integer
     */
    public $propkeyId;
    /**
     * 属性值
     * @var string
     */
    public $propvalue;
    /**
     * 是否是销售属性
     * @var integer
     */
    public $isSaleProp = 0;


    public function getSource() {
        return 't_mall_product_props';
    }
    public function initialize(){
        parent::initialize();
        $this->belongsTo('productId', 'ProductModel', 'id');
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        return [
            'id' => 'id',
            'product_id'=>'productId',
            'is_sale_prop' =>'isSaleProp',
            'propkey_id' =>'propkeyId',
            'propvalue' =>'propvalue'
        ];
    }
}
