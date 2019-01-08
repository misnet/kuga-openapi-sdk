<?php
namespace Kuga\Core\Shop;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\DataExtendTrait;
use Phalcon\Mvc\Model\Message;
use Phalcon\Validation;

/**
 * 属性值
 * Class PropValueModel
 * @package Kuga\Core\Product
 */
class PropValueModel extends AbstractModel {
    use DataExtendTrait;

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
    public function validation()
    {
        $validator = new Validation();
        if(!$this->id)
            $sameNameNum = self::count([
                'code=:n: and propkeyId=:p:',
                'bind' => ['p' => $this->propkeyId, 'n' => $this->code]
            ]);
        else{
            $sameNameNum = self::count([
                'code=:n: and propkeyId=:p: and id!=:id:',
                'bind' => ['p' => $this->propkeyId, 'n' => $this->code,'id'=>$this->id]
            ]);
        }
        if ($sameNameNum) {
            $this->appendMessage(new Message($this->translator->_('存在同名编码'.$this->code)));
            return false;
        }
        return $this->validate($validator);
    }
    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        $data = array (
            'id' => 'id',
            'code' => 'code',
            'propkey_id' => 'propkeyId',
            'propvalue' => 'propvalue',
            'sort_weight'=>'sortWeight',
            'color_hex_value'=>'colorHexValue'
        );
        return array_merge($data,$this->extendColumnMapping());
    }
}
