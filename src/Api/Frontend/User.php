<?php
/**
 * 商户相关的API
 */
namespace Kuga\Api\Frontend;
use Kuga\Core\GlobalVar;
use Kuga\Core\User\UserModel;
use Kuga\Core\Api\Exception as ApiException;
class User extends BaseApi{
    /**
     * 注册用户
     */
    public function register(){


        $data                 = $this->_toParamObject($this->getParams());
        if (! $data['countryCode']) {
            $data['countryCode'] = GlobalVar::COUNTRY_CODE_CHINA;
        }
        $this->validMobile($data['countryCode'], $data['mobile']);
        if($this->_testModel){
            if($data['verifyCode']!=GlobalVar::VERIFY_CODE_FORTEST){
                throw new ApiException($this->translator->_("验证码不正确"));
            }
        }elseif(!$data['seed']){
            throw new ApiException($this->translator->_("手机要先获取验证码"));
        }else{
            $simpleStorage = $this->_di->get('simpleStorage');
            $key = $this->smsPrefix .$data['countryCode'].$data['mobile'].'_'.$data['seed'];
            $correctVerifyCode = $simpleStorage->get($key);
            if($data['verifyCode']!=$correctVerifyCode){
                throw new ApiException($this->translator->_("验证码不正确"));
            }
        }

        $model                = new UserModel();
        $model->username      = $data['mobile'];
        $model->password      = $data['password'];
        $model->mobile        = $data['mobile'];
        $model->createTime    = time();
        $model->lastVisitIp   = \Qing\Lib\Utils::getClientIp();
        $model->lastVisitTime = $model->createTime;
        $result               = $model->create();
        if ( ! $result) {
            throw new ApiException($model->getMessages()[0]->getMessage());
        }

        return $result;
    }
}