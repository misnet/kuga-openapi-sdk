<?php
namespace Kuga\Core\Sms;
use Kuga\Core\Base\ServiceException;

class  SmsFactory{
    /**
     *
     * @param $configFile
     * @param string $adapterName
     * @param null $di
     * @return \Kuga\Core\Sms\SmsInterface
     * @throws ServiceException
     */
    public static function getAdapter($configFile,$adapterName='aliyun',$di=null){
//        $loader = new \Phalcon\Loader();
//        $loader->registerNamespaces(array(
//            'Kuga\Service\Sms'=>'./Sms'
//        ))->register();
        $adapterName = ucfirst(strval($adapterName));
        $className = '\\Kuga\\Core\\Sms\\Adapter\\'.$adapterName;
        if('Aliyun'==$adapterName||'Tencent'==$adapterName){
            return new $className($configFile,$di);
        }else{
            throw new ServiceException('sms adapter '.$adapterName.' not exists');
        }
    }
}