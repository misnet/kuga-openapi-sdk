<?php
namespace Kuga\Core\Product;
use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractModel;

/**
 * 商品SKU Model
 * Class ProductSkuModel
 * @package Kuga\Core\Product
 */
class ProductSkuModel extends AbstractModel {
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
     * SKU规格JSON
     * @var string
     */
    public $skuJson;
    /**
     * 售价
     * @var float
     */
    public $price;
    /**
     * 成本
     * @var float
     */
    public $cost = 0;
    /**
     * 原厂SKU ID
     * @var String
     */
    public $originalSkuId;


    public function getSource() {
        return 't_mall_product_skus';
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
            'sku_json' =>'skuJson',
            'price' =>'price',
            'cost' =>'cost',
            'original_sku_id'=>'originalSkuId'
        ];
    }
}
