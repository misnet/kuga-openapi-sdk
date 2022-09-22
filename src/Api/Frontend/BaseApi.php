<?php
/**
 * 前台API接口处理类
 * @author Donny
 */
namespace Kuga\Api\Frontend;
use Kuga\Core\Api\AbstractApi;
abstract class BaseApi extends AbstractApi{
    protected $smsPrefix = 'sms:';
    protected $_accessTokenUserIdKey = 'front.uid';
}