<?php
/**
 * 前台API接口处理类
 * @author Donny
 */
namespace Kuga\Api\Frontend;
use Kuga\Service\ApiV3\AbstractApi;
use Kuga\Service\ApiService3;
use Kuga\Service\ApiV3\Exception as ApiException;
abstract class BaseApi extends AbstractApi{
    protected $_accessTokenUserIdKey = 'front.uid';
}