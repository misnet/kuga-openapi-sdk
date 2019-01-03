<?php
/**
 * API Service V3.0
 *
 * @author Donny
 */

namespace Kuga\Core\Api;

use Kuga\Core\Api\Exception as ApiException;
use Kuga\Core\Api\Request;
use Kuga\Core\ApiAccessLog\Service as ApiAccessLogService;

class ApiService
{


    /**
     * APP KEY
     *
     * @var string
     */
    private static $_appKey;

    /**
     * APP 密串
     *
     * @var string
     */
    private static $_appSecret;

    private static $_lifetime = 864000;

    static public function setLifetime($t)
    {
        self::$_lifetime = $t;
    }

    private static $di;

    /**
     * 每次调用的唯一ID
     *
     * @var unknown
     */
    private static $invokeId;

    private static $apiLoggerService;

    /**
     * API方法列表
     *
     * @var array
     */
    static private $methodList = [];

    static private $apiJsonConfigFile = '';

    /**
     * 初始化API JSON配置文件
     *
     * @param $configFile
     */
    static public function initApiJsonConfigFile($configFile)
    {
        self::$apiJsonConfigFile = $configFile;
    }

    /**
     * 取得Api Key列表
     *
     * @return array
     */
    static private function getApiKeys()
    {
        $config     = self::$di->get('config');
        $apiKeyList = [];
        if (file_exists($config->apiKeys)) {
            $tmp        = file_get_contents($config->apiKeys);
            $apiKeyList = json_decode($tmp, true);
        }
        $result = [];
        foreach ($apiKeyList as $keyItem) {
            $result[$keyItem['key']]['secret'] = $keyItem['secret'];
        }

        return $result;
    }

    static public function setDi($di)
    {
        self::$di = $di;
    }

    /**
     *
     * @param Request $req
     *
     * @return multitype:\Kuga\Service\multitype:unknown
     */
    static public function response(Request $req)
    {
        // 验证app key
        $config = self::$di->get('config');
        try {
            self::$_appKey = $req->getAppKey();

            self::$_appSecret = '';

            self::beforeInvoke($req->getMethod(), $req->getData());

            if ( ! self::$_appKey) {
                return self::_responseError(
                    ApiException::$EXCODE_INVALID_CLIENT
                );
            }
            // 从配置中取得对应的appSecret

            $apiKeys = self::getApiKeys();
            self::$di->getShared('translator')->setLocale(LC_MESSAGES, $req->getLocale());

            if ( ! array_key_exists(self::$_appKey, $apiKeys)) {
                return self::_responseError(
                    ApiException::$EXCODE_INVALID_CLIENT
                );
            } else {
                // appSecret找到了
                self::$_appSecret = $apiKeys[self::$_appKey]['secret'];
                if ( ! $req->validate(self::$_appSecret)) {
                    // 无效的加密传参

                    $requestSign = $req->getSign();
                    $data        = $req->getData();
                    unset($data['sign']);
                    $sign = $req::createSign(self::$_appSecret, $data);


                    $signLong = self::$_appSecret;
                    ksort($data);
                    foreach ($data as $k => $v) {
                        if ( ! is_array($v) && ! is_object($v)) {
                            $signLong .= $k.$v;
                        }
                    }
                    $signLong        .= self::$_appSecret;
                    $data['LONG']    = $signLong;
                    $data['newSign'] = $sign;

                    return self::_responseError(
                        ApiException::$EXCODE_ERROR_SIGN, '', print_r($data,true)
                    );
                } else {
                    // 参数正确

                    $result = self::invoke($req);

                    return $result;
                }
            }
        } catch (\Exception $e) {
            self::$di->getShared('eventsManager')->fire('qing:errorHappen', $e);
            $debugMsg = $config->debug ? $debugMsg = $e->getTraceAsString() : '';

            return self::_responseError(
                $e->getCode(), self::$di->getShared('translator')->_('服务器开小差了，稍候片刻'), $debugMsg
            );
        }
    }

    /**
     * 验证api接口是否字面有效(字母、数字、中划线、下划线组成)
     *
     * @param String $name
     *
     * @throws ApiException
     */
    static private function _validName($name)
    {
        if ( ! preg_match('/^[A-Za-z0-9\-_]*$/', $name)) {
            throw new ApiException(ApiException::$EXCODE_INVALID_METHOD);
        }
    }

    /**
     * 调用接口之前
     *
     * @param string $method
     * @param array  $params
     */
    static public function beforeInvoke($method, $params = null)
    {
        self::$apiLoggerService = new ApiAccessLogService(self::$di);
        self::$invokeId         = self::$apiLoggerService->init(
            $method, $params
        );
    }

    /**
     * 调用接口显示响应之前
     *
     * @param      $result
     */
    static public function beforeResponse($result)
    {
        self::$invokeId
        && self::$apiLoggerService->setResult(
            self::$invokeId, $result
        );
    }


    /**
     * 读取可用的API列表
     *
     * @todo 可以考虑缓存至Redis，减少应用服务器的内存的占用
     */
    static private function _fetchValidMethod()
    {
        if (empty(self::$methodList) && self::$apiJsonConfigFile) {
            $configFile = self::$apiJsonConfigFile;
            if (file_exists($configFile)) {
                $configContent = file_get_contents($configFile);
                $configArray   = json_decode($configContent, true);
                $rootDir       = dirname($configFile);
                foreach ($configArray as $configCategory) {
                    if (isset($configCategory['apiFiles'])) {
                        foreach (
                            glob(
                                $rootDir.DS.$configCategory['apiFiles']
                            ) as $filename
                        ) {
                            $tmp         = file_get_contents($filename);
                            $jsonContent = json_decode($tmp, true);
                            if ( ! array_key_exists(
                                $jsonContent['id'], self::$methodList
                            )
                            ) {
                                self::$methodList[$jsonContent['id']]
                                    = $jsonContent;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 从缓存中读取API方法
     *
     * @param $method
     *
     * @return multitype|mixed
     */
    static private function _parseRequestApiMethod($method)
    {
        if (empty(self::$methodList)) {
            throw new ApiException( self::$di->getShared('translator')->_('无可用API方法'),ApiException::$EXCODE_INVALID_METHOD);
        }
        if ( ! array_key_exists($method, self::$methodList)) {
            throw new ApiException( self::$di->getShared('translator')->_('API中 %action% 接口不存在', ['action' => $method]),ApiException::$EXCODE_INVALID_METHOD);

        }

        return self::$methodList[$method];
    }

    /**
     * 将接口接收到的实际传参进行过滤和验证，只会留下想要的参数
     *
     * @param $apiConfigData api接口配置的json内容
     * @param $params        接口实际接收到的传参
     *
     * @return array|multitype
     */
    static private function _filterValidParams($apiConfigData, $params)
    {
        $requestConfig = isset($apiConfigData['request']) ? $apiConfigData['request'] : [];
        $validParams   = [];
        if (is_array($requestConfig) && sizeof($requestConfig) > 0) {
            foreach ($requestConfig as $requestItem) {
                if (isset($requestItem['param'])) {
                    //定义的必传项是否有传进来，没有就要报错
                    $key                     = $requestItem['param'];
                    $requestItem['required'] = isset($requestItem['required']) ? $requestItem['required'] : false;
                    if ( ! isset($params[$key]) && $requestItem['required']) {
                        throw new ApiException(
                            self::$di->getShared('translator')->_(
                                '接口缺少参数 %param% ', ['param' => $key]
                            ), ApiException::$EXCODE_PARAMMISS
                        );
                    } elseif ( ! isset($params[$key])
                        && $requestItem['default']!==''
                        && isset($requestItem['type'])
                    ) {
                        //未传值但系统给了默认值和值的类型时，使用默认值
                        switch (strtolower($requestItem['type'])) {
                            case 'integer':
                                $validParams[$key] = intval(
                                    $requestItem['default']
                                );
                                break;
                            case 'boolean':
                                $validParams[$key] = boolval(
                                    $requestItem['default']
                                );
                                break;
                            case 'float':
                                $validParams[$key] = floatval(
                                    $requestItem['default']
                                );
                                break;
                            case 'string':
                            default:
                                $validParams[$key] = trim(
                                    strval($requestItem['default'])
                                );
                                break;
                        }
                    } elseif (isset($params[$key])) {
                        switch (strtolower($requestItem['type'])) {
                            case 'integer':
                                $validParams[$key] = intval($params[$key]);
                                break;
                            case 'boolean':
                                $validParams[$key] = boolval($params[$key]);
                                break;
                            case 'float':
                                $validParams[$key] = floatval($params[$key]);
                                break;
                            case 'string':
                            default:
                                $validParams[$key] = trim(
                                    strval($params[$key])
                                );
                        }
                    }
                }
            }
        }

        return $validParams;
    }


    /**
     * 调用API接口
     *  1.先从配置中读取所有的API接口(以后可以缓存起来)
     *  2.从所有的API接口中找到当前调用的接口
     *  3.验证API的相关参数是否正确，必填的要传，非必填的，可读取默认值
     *  4.接口对应的实际类与方法要存在，不存在报错
     *  5.调用接口，并返回
     *
     * @param \Kuga\Core\Api\Request $req
     *
     * @return multitype
     */
    static public function invoke($req)
    {
        try {
            $method      = $req->getMethod();
            $params      = $req->getParams();
            $appKey      = $req->getAppKey();
            $accessToken = $req->getAccessToken();
            $version     = $req->getVersion();
            $locale      = $req->getLocale();
            //读取可用方法
            self::_fetchValidMethod();
            $apiConfigData = self::_parseRequestApiMethod($method);
            //            $loader        = new \Phalcon\Loader();
            //            $loader->registerNamespaces(
            //                [$apiConfigData['namespace'] => $apiConfigData['path']]
            //            )->register();

            list ($module, $action) = explode('.', $apiConfigData['method']);
            self::_validName($module);
            self::_validName($action);
            //$module    = ucfirst(strtolower($module));
            $className = $apiConfigData['namespace'].'\\'.$module;
            if (isset($apiConfigData['disableFilterParams'])
                && $apiConfigData['disableFilterParams']
            ) {
                $validParams = $params;
            } else {
                $validParams = self::_filterValidParams(
                    $apiConfigData, $params
                );
            }
            $level = 0;
            if (isset($apiConfigData['accessLevel'])
                && $apiConfigData['accessLevel'] > 0
            ) {
                //需要AccessToken
                $level = intval($apiConfigData['accessLevel']);
            }
            //需要accessToken，但又没有 accessToken
            if ($level == 1 && ! $accessToken) {
                return self::_responseError(
                    ApiException::$EXCODE_INVALID_ACCESSTOKEN, self::$di->getShared('translator')->_('access_token 未传值')
                );
            }

            if (class_exists($className)) {
                $refObj = new \ReflectionClass($className);
                $modObj = $refObj->newInstance(self::$di);
                $modObj->setAccessToken($accessToken);
                $modObj->setAccessTokenRequiredLevel($level);
                $modObj->initParams($validParams, $method);
                $modObj->setAppKey($appKey);
                $modObj->setAppSecret($req->getAppSecret());
                $modObj->setVersion($version);
                $modObj->setLocale($locale);
                self::$apiLoggerService->setAccessMemberId(
                    self::$invokeId, $modObj->getUserMemberId()
                );
                if ($action && $refObj->hasMethod($action)) {
                    $result = $modObj->$action();

                    return self::_responseData($result);
                } else {
                    return self::_responseError(
                        ApiException::$EXCODE_INVALID_METHOD, self::$di->getShared('translator')->_(
                        'API中 %action% 接口不存在', ['action' => $action]
                    )
                    );
                }
            } else {
                return self::_responseError(
                    ApiException::$EXCODE_INVALID_METHOD, self::$di->getShared('translator')->_(
                    'API中 %module% 模块不存在', ['module' => $module]
                )
                );
            }
        } catch (\Exception $e) {
            $code = $e->getCode() ? $e->getCode() : ApiException::$EXCODE_UNKNOWN;

            return self::_responseError($code, $e->getMessage(),$e->getTraceAsString());
        }
    }

    /**
     * 加密数据
     *
     * @param unknown $data
     * @param number  $time
     *
     * @return \Phalcon\string
     */
    static public function cryptData($data, $time = 0)
    {
        $crypt = new \Phalcon\Crypt();
        $time || $time = self::$_lifetime;
        $time += time();
        $data = ['data' => $data, 'time' => $time];
        $data = serialize($data);
        $txt  = $crypt->encryptBase64($data, md5(self::$_appKey));

        return $txt;
    }

    /**
     * 解密数据
     *
     * @param unknown $data
     * @param number  $time
     *
     * @return unknown|NULL
     */
    static public function decryptData($data, $time = 0)
    {
        $crypt = new \Phalcon\Crypt();
        $time || $time = time();
        $txt  = $crypt->decryptBase64($data, md5(self::$_appKey));
        $data = @unserialize($txt);
        if ($time <= $data['time']) {
            return $data['data'];
        } else {
            return null;
        }
    }


    /**
     * 正常响应
     *
     * @param unknown $data
     *
     * @return multitype:unknown
     */
    static private function _responseData($data)
    {
        $result = ['status' => ApiException::$EXCODE_SUCCESS, 'data' => $data];
        self::beforeResponse($result);

        return $result;
    }

    /**
     * 报错响应
     *
     * @param integer $code     错误代码
     * @param String  $msg      报错信息
     * @param String  $debugMsg DEBUG信息
     *
     * @return multitype:multitype:unknown
     */
    static private function _responseError($code, $msg = '', $debugMsg = '')
    {

        if ($msg == '') {
            $msg = ApiException::getExMsg($code);
        }

        $result = ['status' => $code, 'data' => $msg];
        try {
            self::beforeResponse($result);
        } catch (\Exception $e) {
        }
        if ($debugMsg) {
            $result['debug'] = $debugMsg;
        }

        return $result;
    }
}
