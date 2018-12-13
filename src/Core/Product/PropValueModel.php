<?php
namespace Kuga\Core\Product;
use Kuga\Core\Base\AbstractModel;

/**
 * 属性值
 * Class PropValueModel
 * @package Kuga\Core\Product
 */
class PropValueModel extends AbstractModel {


    /**
     *
     * @var integer
     */
    public $id;

    /**
     * 编码
     * @var string
     */
    public $code;

    /**
     * 描述
     * @var string
     */
    public $summary;

    /**
     * 属性KEY id
     * @var string
     */
    public $propkeyId;

    /**
     * 属性值
     * @var integer
     */
    public $propvalue;

    /**
     * 显示权重
     * @var int
     */
    public $sortWeight;
    /**
     * 多组颜色hex值，如"000000,ffffff,ffcc00"，或"fffa32"
     * @var string
     */
    public $colorHexValue;


    public function getSource() {
        return 't_mall_propvalue';
    }
    public function initialize(){
        parent::initialize();
        $this->belongsTo('propkeyId', 'PropKeyModel', 'id');
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        return array (
            'id' => 'id',
            'code' => 'code',
            'summary' => 'summary',
            'propkey_id' => 'propkeyId',
            'propvalue' => 'propvalue',
            'sort_weight'=>'sortWeight',
            'color_hex_value'=>'colorHexValue'
        );
    }
}
