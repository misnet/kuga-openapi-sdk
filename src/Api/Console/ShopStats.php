<?php
namespace Kuga\Api\Console;
use Kuga\Core\Shop\InventoryModel;
use Kuga\Core\Shop\ProductModel;

/**
 * 统计相关API
 * Class Stats
 */
class ShopStats extends BaseApi{
    /**
     * 概览统计
     * @return array
     */
    public function overview(){
        $productNum = ProductModel::count(['isDeleted=0']);
        $itemModel  = InventoryModel::query();
        $itemModel->join(ProductModel::class,'productId=p.id and p.isDeleted=0','p');
        $itemModel->columns('sum(stockQty) as skuNum');
        $result = $itemModel->execute();
        $skuNum     = $result->getFirst()->skuNum;

        return [
            'productNum'=>$productNum,
            'skuNum' => $skuNum
        ];
    }
}