<?php
namespace Kuga\Core\Product;
use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\ModelException;

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
    /**
     * SKU编码
     * @var String
     */
    public $skuSn;


    public function getSource() {
        return 't_mall_product_skus';
    }
    public function initialize(){
        parent::initialize();
        $this->belongsTo('productId', 'ProductModel', 'id');
    }
    public function beforeSave(){
        $cnt = 0;
        if($this->id){
            $cnt = self::count([
                'productId=:pid: and originalSkuId=:osid: and id!=:id:',
                'bind'=>['pid'=>$this->productId,'osid'=>$this->originalSkuId,'id'=>$this->id]
            ]);
        }else{
            $cnt = self::count([
                'productId=:pid: and originalSkuId=:osid:',
                'bind'=>['pid'=>$this->productId,'osid'=>$this->originalSkuId]
            ]);
        }
        if($cnt>0){
            throw new ModelException($this->translator->_('同款产品的SKU中原厂编码不可重复'));
        }
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
            'original_sku_id'=>'originalSkuId',
            'sku_sn'=>'skuSn'
        ];
    }
}
