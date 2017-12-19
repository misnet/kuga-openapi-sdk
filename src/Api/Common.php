<?php
/**
 * 通用类目API
 */
namespace Kuga\Api;
use Kuga\Core\Api\AbstractApi;
use Kuga\Core\Api\ApiService;
use Kuga\Core\Api\Exception as ApiException;
use Kuga\Core\Api\Request;


use Kuga\Core\GlobalVar;
use Sts\Request\V20150401 as Sts;
class Common extends  AbstractApi {
    private $smsPrefix = 'sms';
    /**
     * 发送短信验证码
     */
    public function sendSmsVerifyCode(){
        $data = $this->_toParamObject($this->_params);
        if (! isset($data['countryCode'])) {
            $data['countryCode'] = GlobalVar::COUNTRY_CODE_CHINA;
        }
        $mobile = $data['mobile'];
        if ($data['countryCode'] == GlobalVar::COUNTRY_CODE_CHINA) {
            if (! preg_match('/^(13|14|15|17|18|19)[\d+]{9}$/', $mobile)) {
                throw new ApiException(ApiException::$EXCODE_MOBILE_INVALID);
            }
        }

        $seed = date('YmdHi');
        $key = $this->smsPrefix . $data['countryCode']. $mobile . '_' . $seed;
        $simpleStorage = $this->_di->get('simpleStorage');
        $r = $simpleStorage->get($key);
        if (! $r) {
            $r = $this->_testModel ? GlobalVar::VERIFY_CODE_FORTEST : rand(1000, 9999);
        }

        $result = $this->_testModel ? true : $this->_di->get('sms')->verifyCode($mobile, $r);
        if ($result) {
            // 写入数据库
            $s = $simpleStorage->set($key, $r);
            // 2分钟有效
            $simpleStorage->expired($key, 300);
            return $seed;
        } else {
            throw new ApiException($this->translator->_('短信发送失败'));
        }
    }

    /**
     * 批量请求API
     * 格式：method1: API方法名
     *      param1:  json格式字串
     *      method2: ...
     *      param2:  ...
     *
     * @return array
     */
    public function batchRequest(){
        $responseData = [];
        $pairMethod=[];
        $pairParams=[];


        foreach($this->getParams() as $key=>$value){
            preg_match('/^(method|param)([0-9]{1,})$/i',$key,$matches);

            if(!empty($matches)){
                if($matches[1]=='method'){
                    $pairMethod['r'.$matches[2]] = $value;
                }else{
                    $pairParams['r'.$matches[2]] = json_decode($value,true);
                }
            }
        }


        if(!empty($pairMethod)){
            ApiService::setDi($this->_di);
            foreach($pairMethod as $k=>$method){
                if(isset($pairParams[$k]) && $pairParams[$k]){
                    $params = $pairParams[$k];
                }else{
                    $params = [];
                }
                $params['method'] = $method;
                $req = $this->_createRequestObject($params);
                $responseData[$k] = ApiService::invoke($req);
            }
        }
        return $responseData;

    }

    /**
     * 取得OSS配置信息
     * 为不在程序中写死，APP需要读取本信息
     */
    public function ossSetting()
    {
        //官方说用杭州的，可以授权所有的
        $fileStorage   = $this->_di->getShared('fileStorage');
        $configSetting = $fileStorage->getOption();

        $stsRegion = $configSetting['bucket']['region'];
        $iClientProfile = \DefaultProfile::getProfile($stsRegion, $configSetting['accessKeyId'], $configSetting['accessKeySecret']);
        $client = new \DefaultAcsClient($iClientProfile);
        $request = new Sts\AssumeRoleRequest();

        // RoleSessionName即临时身份的会话名称，用于区分不同的临时身份
        // 您可以使用您的客户的ID作为会话名称
        $request->setRoleSessionName($configSetting['roleSessionName']);
        $request->setRoleArn($configSetting['roleArn']);
        $request->setPolicy($configSetting['policy']);
        $request->setDurationSeconds($configSetting['tokenExpireTime']);
        $response = $client->doAction($request);
        $result   = json_decode($response->getBody(),true);
        //采用大写Bucket，和其他统一
        $result['Bucket'] = $configSetting['bucket'];
        return $result;
    }

    /**
     * 创建请求对象
     * @param $params
     * @return Request
     */
    private function _createRequestObject($params){
        $apiKeyFile = $this->_di->get('config')->apiKeys;
        $apiKeys = [];
        if(file_exists($apiKeyFile)){
            $apiKeys = json_decode(file_get_contents($apiKeyFile),true);
        }
        $data['appkey'] = $this->_appKey;

        foreach($params as $k=>$v){
            $data[$k]   = $v;
        }
        if(isset($this->_accessToken)){
            $data['access_token'] = $this->_accessToken;
        }
        if(isset($this->_params['appid'])){
            $data['appid'] = $this->_params['appid'];
        }
        $data['sign']   = Request::createSign($apiKeys[$this->_appKey]['secret'], $data);
        return new Request($data);
    }

}