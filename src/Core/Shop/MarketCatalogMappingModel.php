<?php
/**
 * Product Item Catalog for Marketing
 * @author Donny
 */

namespace Kuga\Core\Shop;

use Kuga\Core\Base\AbstractModel;

class MarketCatalogModel extends AbstractModel
{
    /**
     * id
     * @var integer
     */
    public $id;
    /**
     * 后端类目ID
     * @var integer
     */
    public $itemCatalogId;
    /**
     * 前端类目ID
     * @var integer
     */
    public $marketCatalogId;
    public function columnMap()
    {
        $returnData['id'] = 'id';
        $returnData['item_catalog_id']   = 'itemCatalogId';
        $returnData['market_catalog_id'] = 'marketCatalogId';
        return $returnData;
    }
    public function getSource() {
        return 't_mall_marketcatalog_mapping';
    }

}