<?php
namespace Kuga\Core\Product;
use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\DataExtendTrait;
use Phalcon\Mvc\Model\Relation;

/**
 * 属性名称
 * Class PropKeyModel
 * @package Kuga\Core\Product
 */
class PropKeyModel extends AbstractModel {
    use DataExtendTrait;
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
     * 媒体文件上传
     */
    const FORM_TYPE_MEDIAUPLOAD = 5;
    /**
     * 日期
     */
    const FORM_TYPE_DATE   = 6;
    /**
     * 价格
     */
    const FROM_TYPE_PRICE  = 7;
    /**
     * 是否
     */
    const FROM_TYPE_YESNO  = 8;


    /**
     * 描述
     * @var string
     */
    public $summary;
    /**
     *
     * @var integer
     */
    public $id;

    /**
     * 是否是颜色
     * @var integer
     */
    public $isColor;

    /**
     * 表单控件形式
     * @var string
     */
    public $formType;
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
        $this->hasMany('id','PropValueModel','propkeyId',[
            'foreignKey'=>[
                'action'=>Relation::ACTION_CASCADE
            ]
        ]);
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        $data = array (
            'id' => 'id',
            'is_color' => 'isColor',
            'form_type' => 'formType',
            'summary'=>'summary',
            'name'=>'name'
        );
        return array_merge($data,$this->extendColumnMapping());
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


}
