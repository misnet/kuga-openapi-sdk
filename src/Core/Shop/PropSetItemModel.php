<?php
namespace Kuga\Core\Shop;
use Kuga\Core\Api\Exception;
use Kuga\Core\Base\AbstractModel;

/**
 * 属性名称
 * Class PropSetItemModel
 * @package Kuga\Core\Product
 */
class PropSetItemModel extends AbstractModel {

    /**
     * @var integer
     */
    public $id;
    /**
     * 模板ID
     * @var integer
     */
    public $propsetId;
    /**
     * 属性ID
     * @var integer
     */
    public $propkeyId;


    /**
     * 是否必填
     * @var integer 1必填，0非必填
     */
    public $isRequired = 0;

    /**
     * 是否是销售属性
     * @var integer
     */
    public $isSaleProp = 0;


    /**
     * 是否可以应用于编码规则
     * @var integer
     */
    public $isApplyCode = 0;

    /**
     * 显示权重
     * @var int
     */
    public $sortWeight = 0;
    /**
     * 应用于搜索
     * @var integer
     */
    public $usedForSearch = 0;
    /**
     * 是否禁用
     * @var integer 1是，0启用
     */
    public $disabled = 0;

    public function getSource() {
        return 't_mall_propset_keys';
    }
    public function initialize(){
        parent::initialize();
        $this->belongsTo('propsetId', 'PropSetModel', 'id');
        $this->belongsTo('propkeyId','PropKeyModel','id');
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        return array (
            'id' => 'id',
            'propset_id' => 'propsetId',
            'propkey_id' => 'propkeyId',
            'is_required' => 'isRequired',
            'is_sale_prop' => 'isSaleProp',
            'is_apply_code'=>'isApplyCode',
            'sort_weight'=>'sortWeight',
            'used_for_search'=>'usedForSearch',
            'disabled'=>'disabled'
        );
    }


    public function beforeSave(){
        if($this->isApplyCode){
            $propKeyRow = PropKeyModel::findFirstById($this->propkeyId);
            if($propKeyRow && $propKeyRow->formType != PropKeyModel::FORM_TYPE_SINGLE_CHOISE){
                throw new Exception($this->translator->_('只有输入形式为单选的属性才能应用于编码'));
            }
        }
        return true;
    }
}
