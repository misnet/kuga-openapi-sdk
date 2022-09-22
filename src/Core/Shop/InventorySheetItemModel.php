<?php
namespace Kuga\Core\Shop;
use Kuga\Core\Base\AbstractModel;
use Phalcon\Validation;

/**
 * 出入库单明细
 * Class InventorySheetItemModel
 * @package Kuga\Core\Shop
 */
class InventorySheetItemModel extends AbstractModel {

    /**
     *
     * @var integer
     */
    public $id;

    /**
     * 出入库单号
     * @var integer
     */
    public $sheetId;

    /**
     * 出入库数量
     * @var integer
     */

    public $qty;
    /**
     * SKU ID
     * @var Integer
     */
    public $skuId;


    public function getSource() {
        return 't_mall_inventory_sheet_item';
    }
    public function initialize(){
        parent::initialize();
        $this->belongsTo('sheetId','InventorySheetModel','id');
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap() {
        return array (
            'id' => 'id',
            'sheet_id' => 'sheetId',
            'qty'=>'qty',
            'sku_id'=>'skuId'
        );
    }
}
