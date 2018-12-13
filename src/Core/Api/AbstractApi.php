<?php
/**
 * API抽象类, 所有的API类要继承本方法
 *
 * @author    Donny
 * @copyright 2017
 */

namespace Kuga\Core\Api;

use Kuga\Core\Base\AbstractService;
use Kuga\Core\SysParams\SysParamsModel;
use Kuga\Core\Api\Exception as ApiException;

abstract class AbstractApi extends AbstractService
{

    /**
     * API参数
     *
     * @var array
     */
    protected $_params;

    /**
     * API方法
     *
     * @var
     */
    protected $_method;

    /**
     * 当前用户ID，可能是前台，也可能是后台
     *
     * @var
     */
    protected $_userMemberId;

    protected $_testModel;

    /**
     * APP KEY，可用于处理不同调用通道
     * IOS APP, Android APP, Mobile Web, PC Web等要有不同的KEY
     *
     * @var string
     */
    protected $_appKey;

    /**
     * 参数白名单
     *
     * @var array
     */
    protected $_whiteProps = [];

    protected $_blackProps = [];

    protected $_accessToken;
    protected $_version;
    protected $_locale;

    /**
     * 对accessToken的等级要求
     * 0 不需要，有传也会被过滤掉
     * 1 强制需要，必须有
     * 2 可要，可不要，有传就验证，不传就当没有
     *
     * @var int
     */
    protected $_accessTokenRequiredLevel = 0;

    private $_logger;

    protected $_accessTokenUserIdKey = 'uid';

    /**
     * 设置accessToken的需求等级
     *
     * @param $level
     *
     * @return mixed
     */
    public function setAccessTokenRequiredLevel($level)
    {
        return $this->_accessTokenRequiredLevel = $level;
    }

    public function setAppKey($s)
    {
        $this->_appKey = $s;
    }

    public function getAppKey()
    {
        return $this->_appKey;
    }
    public function getVersion(){
        return $this->_version;
    }
    public function setVersion($v){
        $this->_version = $v;
    }
    public function getLocale(){
        return $this->_locale;
    }
    public function setLocale($v){
        $this->_locale = $v;
    }

    /**
     * 设置AccessToken
     *
     * @param $token
     */
    public function setAccessToken($token)
    {
        $this->_accessToken = $token;
    }


    /**
     * 是否是IOS
     *
     * @return bool
     */
    public function isIOS()
    {
        return $this->getAppKey() == '1001';
    }

    /**
     * 初始化API传参
     *
     * @param  Array  $params
     * @param null $di
     * @param null $method
     */
    public function initParams($params, $method = null)
    {
        //$this->_params = new Parameter($params);
        $this->_params = $params;
        $this->_method = $method;
        if ($this->_accessToken && $this->_accessTokenRequiredLevel > 0) {
            $this->_userMemberId = $this->_getInfoFromAccessToken($this->_accessToken, $this->_accessTokenUserIdKey);

        }
        $this->_testModel = $this->_di->get('config')->get('testmodel');
    }

    /**
     * 取得当前用户的ID
     *
     * @return Integer
     */
    public function getUserMemberId()
    {
        return $this->_userMemberId;
    }

    /**
     * 取得传进来的参数数组
     *
     * @return array
     */
    protected function getParams()
    {
        return $this->_params;
    }

    /**
     * 将API数组参数转为Parameter对象
     *
     * @param Array $data
     * @param unknown $whiteProps
     * @param string  $restrict
     *
     * @throws Exception
     * @return \Kuga\Core\Api\Parameter
     */
    protected function _toParamObject($data, $whiteProps = [], $restrict = false)
    {
        $returnData = [];
        if (empty($whiteProps)) {
            $whiteProps = $this->_whiteProps;
        }
        if ( ! empty($whiteProps) && $data) {
            foreach ($data as $key => $value) {
                //根据值来判断
                if (in_array($key, $whiteProps) && ! in_array($key, $this->_blackProps)) {
                    $returnData[$key] = $value;
                }
            }
        } else {
            $returnData = $data;
        }
        if ($restrict && sizeof($returnData) != sizeof($whiteProps) && sizeof($whiteProps) > 0) {
            throw new ApiException(ApiException::$EXCODE_PARAMMISS);
        }

        return new Parameter($returnData);
    }

    /**
     * 从加密的accessToken解出想要的信息
     *
     * @param        $accessToken
     * @param string $key
     *
     * @return \Kuga\Service\unknown|NULL
     * @throws Exception
     */
    protected function _getInfoFromAccessToken($accessToken, $key = '')
    {
        $at = ApiService::decryptData($accessToken);
        if ( ! $at) {
            throw new ApiException(ApiException::$EXCODE_INVALID_ACCESSTOKEN);
        } else {
            if ($key) {
                return ! isset($at[$key]) ? null : $at[$key];
            } else {
                return $at;
            }
        }
    }

    /**
     * 生成Token
     *
     * @param mixed   $data     用户ID
     * @param integer $lifetime Token的有效时间
     *
     * @return \Phalcon\string
     */
    protected function _createAccessToken($data, $lifetime = 0)
    {
        $lifetime = intval($lifetime);
        $lifetime || $lifetime = 864000;
        ApiService::setLifetime($lifetime);

        return ApiService::cryptData($data);
    }

    /**
     * API日志
     *
     * @param $message
     */
    protected function _log($message)
    {
        if ( ! $this->_logger) {
            $this->_logger = $this->_di->getShared('logger');
        }
        if (is_object($message) || is_array($message)) {
            $message = print_r($message, true);
        }
        $this->_logger->log($message);
    }

    /**
     * 只返回白名单中的key
     *
     * @param array $data
     * @param array $whiteProps 白名单key
     *
     * @return array
     */
    protected function _filter($data, $whiteProps = [])
    {
        $returnData = [];
        if ( ! empty($whiteProps) && $data) {
            foreach ($data as $key => $value) {
                //根据值来判断
                if (in_array($key, $whiteProps)) {
                    $returnData[$key] = $value;
                }
            }
        } else {
            $returnData = $data;
        }

        return $returnData;
    }

    /**
     * @return \Phalcon\Events\ManagerInterface
     */
    protected function getEventsManager()
    {
        return $this->_di->getShared('eventsManager');
    }

    /**
     * 验证手机有效性
     *
     * @param $countryCode
     * @param $mobile
     */
    protected function validMobile($countryCode, $mobile)
    {
        $t = $this->_translator;
        if ( ! preg_match('/^(\d+)$/i', $countryCode)) {
            throw new ApiException($t->_('国家区号不正确'));
        }
        if ( ! preg_match('/^(\d+)$/i', $mobile)) {
            throw new ApiException($t->_('手机号不正确'));
        }
    }

    /**
     * 当前账号是否是开发人员账号，用于特权处理
     *
     * @return bool
     */
    protected function isDevMember()
    {
        $devMembers  = SysParamsModel::getInstance()->get('app.devMembers');
        $devMidArray = explode(',', $devMembers);
        if ($devMembers && sizeof($devMidArray) > 0) {
            if ($this->_userMemberId && in_array($this->_userMemberId, $devMidArray)) {
                return true;
            }
        }

        return false;
    }
}
