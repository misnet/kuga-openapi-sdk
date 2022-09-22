<?php
namespace Kuga\Core\Shop;
use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\DataExtendTrait;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Uniqueness;

/**
 * 商品Model
 * Class ProductModel
 * @package Kuga\Core\Product
 */
class ProductModel extends AbstractModel {
    use DataExtendTrait;

    /**
     *
     * @var integer
     */
    public $id;
    /**
     * 商品标题名
     * @var string
     */
    public $title;
    /**
     * 商品卖点
     * @var
     */
    public $sellerPoint;
    /**
     * 是否上架
     * @var int
     */
    public $isOnline = 1;
    /**
     * 列表页零售价
     * @var float
     */
    public $listingPrice = 0;
    /**
     * 排序权重
     * @var int
     */
    public $sortWeight = 0;
    /**
     * 条码
     * @var string
     */
    public $barcode;
    /**
     * 出厂编码
     * @var string
     */
    public $originBarcode;
    /**
     * 对应后台类目的ID
     * @var integer
     */
    public $catalogId;
    /**
     * 使用的属性集合ID
     * @var integer
     */
    public $propsetId;
    /**
     * 引用产品ID
     * @var int
     */
    public $refProductId = 0;

    /**
     * @var \Kuga\Core\Shop\ProductImgModel
     */
    public $imgObject;
    /**
     * @var \Kuga\Core\Shop\ProductDescModel
     */
    public $contentObject;



    /**
     * 保存之前，验证类目的有效性，并指定了类目使用的属性集ID
     * @return bool
     * @throws \Exception
     */
    public function beforeSave(){
        if(!$this->catalogId){
            throw new \Exception($this->translator->_('未指定类目'));
        }
        $currentCatalog = ItemCatalogModel::findFirst([
            'id=:id: and isDeleted = 0',
            'bind'=>[ 'id' => $this->catalogId]
        ]);
        if(!$currentCatalog){
            throw new \Exception($this->translator->_('指定的类目不存在'));
        }
        $childNodeNum = ItemCatalogModel::count([
            'parentId=:pid: and isDeleted = 0',
            'bind'=>['pid' => $this->catalogId]
        ]);
        if($childNodeNum >0){
            throw new \Exception($this->translator->_('类目 %name%下面还有子类目，必须保证您所选的类目是最下面一级的类目',['name'=>$currentCatalog->name]));
        }
        if(!$this->propsetId){
            $this->propsetId = $currentCatalog->propsetId;
        }
        if($this->propsetId != $currentCatalog->propsetId){
            throw new \Exception($this->translator->_('类目 %name% 对应的属性集和您指定的不一致'));
        }
        return true;
    }
    public function getSource() {
        return 't_mall_products';
    }
    public function initialize(){
        parent::initialize();
        $this->belongsTo('prosetid', 'PropSetModel', 'id');
        $this->hasMany('id','ProductImgModel','productId',[
            ['foreignKey' => ['action' => Relation::ACTION_CASCADE], 'namespace' => 'Kuga\\Core\\Product']
        ]);
        $this->hasMany('id','ProductDescModel','productId',[
            ['foreignKey' => ['action' => Relation::ACTION_CASCADE], 'namespace' => 'Kuga\\Core\\Product']
        ]);
        $this->hasMany('id','ProductPropModel','productId',[
            ['foreignKey' => ['action' => Relation::ACTION_CASCADE], 'namespace' => 'Kuga\\Core\\Product']
        ]);
        $this->hasMany('id','ProductSkuModel','productId',[
            ['foreignKey' => ['action' => Relation::ACTION_CASCADE], 'namespace' => 'Kuga\\Core\\Product']
        ]);
    }
    public function validate(\Phalcon\ValidationInterface $validator)
    {

        $validator->add('title',new PresenceOf([
            'model'=>$this,
            'message'=>$this->translator->_('商品名称必须填写')
        ]));

        $validator->add('barcode',new PresenceOf([
            'model'=>$this,
            'message'=>$this->translator->_('商品款号必须填写')
        ]));


        $validator->add('listingPrice',new PresenceOf([
            'model'=>$this,
            'message'=>$this->translator->_('商品零售价必须填写')
        ]));

        $validator->add('barcode', new Uniqueness([
            'model' => $this,
            'message' => $this->translator->_('商品款号已存在')
        ]));
        $this->validate($validator);
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        $data =  array (
            'id' => 'id',
            'title'=>'title',
            'seller_point' =>'sellerPoint',
            'sort_weight' =>'sortWeight',
            'propset_id' =>'propsetId',
            'barcode' =>'barcode',
            'origin_barcode' =>'originBarcode',
            'listing_price' =>'listingPrice',
            'is_online' =>'isOnline',
            'catalog_id'=>'catalogId',
            'ref_product_id'=>'refProductId'
        );
        return array_merge($data,$this->extendColumnMapping());
    }
}
