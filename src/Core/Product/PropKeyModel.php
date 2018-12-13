<?php
namespace Kuga\Core\Product;
use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractModel;

/**
 * 属性名称
 * Class PropKeyModel
 * @package Kuga\Core\Product
 */
class PropKeyModel extends AbstractModel {
    /**
     * 输入框
     * @var integer
     */
    const FORM_TYPE_TEXT = 1;
    /**
     * 单选
     * @var integer
     */
    const FORM_TYPE_SINGLE_CHOISE = 2;
    /**
     * 多选
     * @var integer
     */
    const FORM_TYPE_MUTI_CHOISE = 3;
    /**
     * 多行输入
     * @var integer
     */
    const FORM_TYPE_TEXTAREA  = 4;

    /**
     *
     * @var integer
     */
    public $id;

    /**
     * 类目id
     * @var integer
     */
    public $catalogId;

    /**
     * 是否是颜色
     * @var integer
     */
    public $isColor;

    /**
     * 是否是销售属性
     * @var integer
     */
    public $isSaleProp;

    /**
     * 表单控件形式
     * @var string
     */
    public $formType;
    /**
     * 是否可以应用于编码规则
     * @var integer
     */
    public $isApplyCode;

    /**
     * 显示权重
     * @var int
     */
    public $sortWeight;
    /**
     * 应用于搜索
     * @var integer
     */
    public $usedForSearch;
    /**
     * 属性名称
     * @var string
     */
    public $name;


    public function getSource() {
        return 't_mall_propkey';
    }
    public function initialize(){
        parent::initialize();
        $this->belongsTo('catalogId', 'ItemCatalogModel', 'id');
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        return array (
            'id' => 'id',
            'catalog_id' => 'catalogId',
            'is_color' => 'isColor',
            'is_sale_prop' => 'isSaleProp',
            'form_type' => 'formType',
            'is_apply_code'=>'isApplyCode',
            'sort_weight'=>'sortWeight',
            'used_for_search'=>'usedForSearch',
            'name'=>'name'
        );
    }
    /**
     * 取得类目属性的输入形式
     * @return string[]
     */
    public static function getFormTypeList(){
        return array(
            self::FORM_TYPE_SINGLE_CHOISE =>'单选',
            self::FORM_TYPE_TEXT=>'文本输入',
            self::FORM_TYPE_MUTI_CHOISE=>'多选',
            self::FORM_TYPE_TEXTAREA=>'多行输入',
        );
    }


    public function beforeSave(){
        if($this->formType!= self::FORM_TYPE_SINGLE_CHOISE && $this->isApplyCode){
            throw new Exception($this->translator->_('只有输入形式为单选的属性才能应用于编码'));
        }
        return true;
    }
}
