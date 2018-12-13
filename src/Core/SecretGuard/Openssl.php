<?php
/**
 * OPENSSL加解密类
 * Class Openssl
 * @package Kuga\Core\SecretGuard
 */
namespace Kuga\Core\SecretGuard;

class Openssl {
    private $privateKeyRes;
    private $csrFile = '';
    private $pkcsFile = '';
    private $privatePasswd = '';
    private $publicKeyString = '';
    private $privateKeyString = '';
    public function setCsrFile($f){
        $this->csrFile = $f;
    }
    public function setPKCSFile($f){
        $this->pkcsFile = $f;
    }
    public function setPrivatePasswd($p){
        $this->privatePasswd = $p;
    }
    /**
     * 创建证书
     * @param $dn
     * @param int $numberOfDays
     * @param string $privatePasswd
     */
    public function create($dn,$numberOfDays=36500){
        $privateKeyResource = openssl_pkey_new();
        $csrResource = openssl_csr_new($dn, $privateKeyResource);
        $ssCert = openssl_csr_sign($csrResource, null, $privateKeyResource, $numberOfDays);
        openssl_x509_export_to_file($ssCert, $this->csrFile);
        openssl_pkcs12_export_to_file($ssCert, $this->pkcsFile,$privateKeyResource, $this->privatePasswd);
        $this->privateKeyRes = $privateKeyResource;
    }
    public function readPKCS12(){
        $privKey = file_get_contents($this->pkcsFile);
        openssl_pkcs12_read($privKey,$certs,$this->privatePasswd);
        $this->privateKeyString = $certs['pkey'];
        $this->publicKeyString  = $certs['cert'];
        return $certs;
    }
    public function setPublicKeyString($k){
        $this->publicKeyString = $k;
    }
    public function setPrivateKeyString($k){
        $this->privateKeyString = $k;
    }
    /**
     * 加密字串
     * @param $data
     * @return string 加密串
     */
    public function encrypt($data) {
        $res = openssl_get_publickey($this->publicKeyString);
        openssl_public_encrypt($data,$cryptedText,$res);
        return base64_encode($cryptedText);
    }

    /**
     * 调用私钥解密
     * @param $cryptedData
     * @return string 明文
     */
    public function decrypt($cryptedData){

        $res = openssl_get_privatekey($this->privateKeyString);
        openssl_private_decrypt(base64_decode($cryptedData), $data,$res);
        return $data;
    }

    /**
     * 创建签名
     * @param $data
     * @return string
     */
    public function createSign($data){
        openssl_sign($data, $signed, $this->privateKeyString,OPENSSL_ALGO_SHA1);
        return base64_encode($signed);
    }

    /**
     * 验证签名是否有效
     * @param $data 数据
     * @param $signed 签名串
     * @return int
     */
    public function valid($data,$signed){
        $unsignMsg=base64_decode($signed);
        $res = openssl_verify($data, $unsignMsg, $this->publicKeyString); //验证
        return $res;
    }
}