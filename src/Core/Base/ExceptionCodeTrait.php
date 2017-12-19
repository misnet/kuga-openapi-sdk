<?php
namespace Kuga\Core\Base;
trait ExceptionCodeTrait{
    public static $EXCODE_SUCCESS = 0;
    public static $EXCODE_UNKNOWN  = 99999;
    /**
     * 无效的appKey
     * @var int
     */
    public static $EXCODE_INVALID_CLIENT = 99000;
    /**
     * 签名错误
     * @var int
     */
    public static $EXCODE_ERROR_SIGN     = 99001;
    /**
     * 无效的API方法
     * @var int
     */
    public static $EXCODE_INVALID_METHOD = 99002;
    /**
     * 必要参数缺少
     * @var int
     */
    public static $EXCODE_PARAMMISS = 99003;

    /**
     * 无效Token
     * @var int
     */
    public static $EXCODE_INVALID_ACCESSTOKEN = 99004;

    /**
     * 记录不存在
     * @var int
     */
    public static $EXCODE_NOTEXIST = 99005;


    /**
     * 无效的刷新token
     * @var int
     */
    public static $EXCODE_INVALID_REFRESHTOKEN = 99006;

    /**
     * 手机号已注册
     * @var int
     */
    public static $EXCODE_MOBILE_REGISTERED = 89001;

    /**
     * 无效手机号
     * @var int
     */
    public static $EXCODE_MOBILE_INVALID = 89002;

    /**
     * 密码错误
     * @var int
     */
    public static $EXCODE_INVALID_PASSWD = 89003;

    /**
     * 未发过短信验证码
     * @var int
     */
    public static $EXCODE_NEVER_SENT_SMSCODE = 89004;

    /**
     * 无效短信验证码
     * @var int
     */
    public static $EXCODE_INVALID_SMSCODE = 89005;
    /**
     * 手机号未注册过
     * @var int
     */
    public static $EXCODE_MOBILE_NOTREGIST = 89006;


    /**
     * 非能识别的用户，用户未登录
     * @var int
     */
    public static $EXCODE_EMPTY_UID = 89007;

    /**
     * 非所有者, 进行一些操作时，这些信息的所有者非当前用户，系统会禁止
     * @var int
     */
    public static $EXCODE_NOT_OWNER = 89008;

    public static function getAllExceptions(){
        $di = \Phalcon\DI::getDefault();
        $t = $di->getShared('translator');
        return array(
            self::$EXCODE_SUCCESS=>'',
            self::$EXCODE_UNKNOWN=>'',
            self::$EXCODE_NOTEXIST => $t->_('数据不存在'),
            self::$EXCODE_INVALID_ACCESSTOKEN => $t->_('Access Token无效'),
            self::$EXCODE_INVALID_REFRESHTOKEN => $t->_('Refresh Token无效'),
            self::$EXCODE_PARAMMISS => $t->_('参数缺失'),
            self::$EXCODE_INVALID_CLIENT=>$t->_('无效appkey或appsecret'),
            self::$EXCODE_ERROR_SIGN=>$t->_('无效签名'),
            self::$EXCODE_INVALID_METHOD=>$t->_('无效的接口'),
            self::$EXCODE_NOT_OWNER=>$t->_('非所有者'),
            self::$EXCODE_MOBILE_REGISTERED=>$t->_('手机号已注册过'),
            self::$EXCODE_MOBILE_INVALID=>$t->_('手机号码不正确'),
            self::$EXCODE_INVALID_PASSWD=>$t->_('密码错误'),
            self::$EXCODE_NEVER_SENT_SMSCODE=>$t->_('短信验证码未发送过或已失效'),
            self::$EXCODE_INVALID_SMSCODE=>$t->_('错误的短信验证码'),
            self::$EXCODE_MOBILE_NOTREGIST=>$t->_('手机号未注册'),
        );
    }
    public static function getExMsg($code){
        $_data = self::getAllExceptions();
        return array_key_exists($code, $_data)?$_data[$code]:'';
    }
}