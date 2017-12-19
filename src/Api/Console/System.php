<?php
/**
 * 后台系统类目API
 */
namespace Kuga\Api\Console;
use Kuga\Core\Menu\MenuModel;
use Kuga\Core\Api\ApiService;
use Kuga\Core\Api\Exception as ApiException;
use Kuga\Core\Api\Request;


use Sts\Request\V20150401 as Sts;
class System extends BaseApi {
    /**
     * 所有菜单列表
     */
    public function listMenu(){
        $data = $this->_toParamObject($this->getParams());
        $data['pid'] = intval($data['pid']);
        $result = MenuModel::find([
            'order'=>'sortByWeight desc',
            'parentId=?1',
            'bind'=>[1=>$data['pid']]
        ]);
        $list = $result->toArray();
//        $menu = MenuModel::query();
//
//        $menu->columns([
//            'id',
//            'name',
//            'controller',
//            'action',
//            'paramater',
//            'parentId',
//            'display',
//            'displayWeight',
//            'className'
//            //'(select count(0) from '.MenuModel::class.' as b where b.parentId='.MenuModel::class.'.id) as childNum'
//        ]);
//        $menu->where('parentId=:pid:');
//        $menu->bind(['pid'=>$data['pid']]);
//        $menu->orderBy('displayWeight desc');
//        $result = $menu->execute();
//        $list =  $result->toArray();
        $list || $list = [];
        foreach($list as &$item){
            $childList = MenuModel::findByParentId($item['id']);
            $item['children']= $childList->toArray();
            if(!$item['children']){
                unset($item['children']);
            }
        }
        return $list;
    }

    /**
     * 创建菜单
     */
    public function createMenu(){
        $data = $this->_toParamObject($this->getParams());
        $menu = new MenuModel();
        $menu->initData($data->toArray(),['id','createTime']);
        $result = $menu->create();
        if(!$result){
            throw new ApiException($menu->getMessages()[0]->getMessage());
        }
        return $result;
    }

    /**
     * 更新菜单
     */
    public function updateMenu(){
        $data = $this->_toParamObject($this->getParams());
        $menu = MenuModel::findFirstById($data['id']);
        if($menu){
            $menu->initData($data->toArray(),['createTime']);
            $result = $menu->update();
            if(!$result){
                throw new ApiException($menu->getMessages()[0]->getMessage());
            }
        }else{
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        return $result;
    }

    /**
     * 删除菜单
     */
    public function deleteMenu(){
        $data = $this->_toParamObject($this->getParams());
        $menu = MenuModel::findFirstById($data['id']);
        if($menu){
            $result = $menu->delete();
        }else{
            $result = true;
        }
        if(!$result){
            throw new ApiException($menu->getMessages()[0]->getMessage());
        }
        return $result;
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