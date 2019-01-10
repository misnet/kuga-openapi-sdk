<?php
namespace Kuga\Core\Shop;
use Kuga\Core\Base\AbstractModel;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Validation;

/**
 * 出入库单
 * Class InventorySheetModel
 * @package Kuga\Core\Shop
 */
class InventorySheetModel extends AbstractModel {

    const TYPE_IN  = 1;
    const TYPE_OUT = 2;
    /**
     *
     * @var integer
     */
    public $id;

    /**
     * 创建时间
     * @var integer
     */
    public $createTime;

    /**
     * 出入库类型
     * @var integer 1出库，2入库
     */

    public $sheetType;
    /**
     * 描述
     * @var string
     */
    public $sheetDesc;
    /**
     * 出入单号
     * @var string
     */
    public $sheetCode;
    /**
     * 操作人
     * @var Integer
     */
    public $userId;
    /**
     * 出入库时间
     * @var integer
     */
    public $sheetTime;
    /**
     * 店仓ID
     * @var integer
     */
    public $storeId;
    /**
     * 是否审通过
     * @var int 1是，0不是
     */
    public $isChecked = 0;


    public function getSource() {
        return 't_mall_inventory_sheet';
    }
    public function initialize(){
        parent::initialize();
        $this->hasMany('id','InventorySheetItemModel','sheetId',[
            'foreignKey' => [
                'action' => Relation::ACTION_CASCADE
            ],
            'namespace'=>'\Kuga\Core\Shop'
        ]);
    }
    public function validate(\Phalcon\ValidationInterface $validator)
    {
        $validator->add('storeId',new Validation\Validator\PresenceOf([
            'model' => $this,
            'message' => $this->translator->_('请确定出入库所在店仓')
        ]));
        $validator->add('sheetCode',new Validation\Validator\Db\Uniqueness([
            'model'=>$this,
            'message' => $this->translator->_('单据编号已存在，请更换')
        ]));
        return $this->validate($validator);
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        return array (
            'id' => 'id',
            'store_id' => 'storeId',
            'create_time'=>'createTime',
            'sheet_type'=>'sheetType',
            'sheet_desc'=>'sheetDesc',
            'sheet_time'=>'sheetTime',
            'user_id'=>'userId',
            'sheet_code'=>'sheetCode',
            'is_checked'=>'isChecked'
        );
    }

    public function beforeCreate(){
        $this->createTime||$this->createTime = time();
        return true;
    }
    public function beforeSave(){
        if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/',$this->sheetTime)){
            $this->sheetTime = strtotime($this->sheetTime);
        }
        return true;
    }
}
