<?php
namespace Kuga\Api\Console;
use Kuga\Core\Shop\PropKeyModel;
use Kuga\Core\Shop\PropValueModel;

abstract class ShopBaseApi extends BaseApi {
    /**
     * 取得SKU字串
     * @param $skuJson
     * @return string
     */
    protected function _fetchSkuString($skuJson){
        $skuJson = json_decode($skuJson,true);
        if(!empty($skuJson)){
            $keyIds = [];
            $valueIds = [];
            $mapping  = [];
            $keyNameList = [];
            $valueNameList = [];
            $json = [];
            foreach($skuJson as $skuProp){
                $keyIds[]   = $skuProp['prop'];
                $valueIds[] = $skuProp['value'];
                $mapping['p'.$skuProp['prop']] = $skuProp['value'];
            }
            $keyList = PropKeyModel::find([
                'id in ({ids:array})',
                'bind'=>['ids'=>$keyIds],
                'columns'=>['id','name']
            ]);
            $valueList = PropValueModel::find([
                'id in ({ids:array})',
                'bind'=>['ids'=>$valueIds],
                'columns'=>['id','propvalue']
            ]);

            if($keyList){
                foreach($keyList as $item){
                    $keyNameList['p'.$item->id] = $item->name;
                }
            }
            if($valueList){
                foreach($valueList as $item){
                    $valueNameList['p'.$item->id] = $item->propvalue;
                }
            }
            foreach($mapping as $k=>$v){
                if(isset($keyNameList[$k]) && isset($valueNameList['p'.$v])){
                    $json[] = $keyNameList[$k].':'.$valueNameList['p'.$v];
                }
            }
            $skuString= !empty($json)?join(';',$json):$this->translator->_('未知');
        }else{
            $skuString = $this->translator->_('未知');
        }
        return $skuString;
    }
}