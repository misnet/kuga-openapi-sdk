<?php
/**
 * 后台系统用户类目API
 */

namespace Kuga\Api\Console;

use Kuga\Core\User\UserModel;
use Kuga\Core\Acc\Service\Acc as AccService;
use Kuga\Core\Acc\Service\Acl as AclService;
use Kuga\Core\Api\Exception as ApiException;
use Kuga\Core\Menu\MenuService;

class User extends BaseApi
{

    /**
     * 删除用户
     */
    public function delete()
    {
        $data   = $this->_toParamObject($this->getParams());
        $row    = UserModel::findFirstByUid($data['uid']);
        $result = true;
        if ($row) {
            if ($this->_userMemberId == $row->uid) {
                throw new ApiException($this->translator->_('这个用户是当前用户，不可删除'));
            }
            $result = $row->delete();
            if ( ! $result) {
                throw new ApiException($row->getMessages()[0]->getMessage());
            }
        }

        return $result;
    }

    /**
     * 更新用户
     */
    public function update()
    {
        $data = $this->_toParamObject($this->getParams());
        $row  = UserModel::findFirstByUid($data['uid']);
        if ( ! $row) {
            throw new ApiException($this->translator->_('找不到用户，可能已被删除'));
        }
        $row->username = $data['username'];
        if ($data['password']) {
            $row->password = $data['password'];
        }
        $row->mobile = $data['mobile'];
        $result      = $row->update();
        if ( ! $result) {
            throw new ApiException($row->getMessages()[0]->getMessage());
        }

        return $result;
    }

    /**
     * 创建用户
     *
     * @return bool
     * @throws ApiException
     */
    public function create()
    {
        $data                 = $this->_toParamObject($this->getParams());
        $model                = new UserModel();
        $model->username      = $data['username'];
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

    /**
     * 显示用户列表
     *
     * @return array
     */
    public function userList()
    {
        $data = $this->_toParamObject($this->getParams());
        $data['limit'] || $data['limit'] = 10;
        $data['page'] || $data['page'] = 1;
        $list  = UserModel::find(
            ['limit'   => $data['limit'], 'offset' => ($data['page'] - 1) * $data['limit'],
             'columns' => ['uid', 'username', 'createTime', 'mobile', 'gender'], 'order' => 'uid desc']
        );
        $total = UserModel::count();

        return ['list' => $list, 'total' => $total, 'page' => $data['page'], 'limit' => $data['limit']];
    }

    /**
     * 管理人员登录
     */
    public function login()
    {
        $data      = $this->_toParamObject($this->getParams());
        $userModel = new UserModel();
        $row       = UserModel::findFirstByUsername($data['user']);
        if ( ! $row) {
            throw new ApiException($this->translator->_('账户或密码错误'));
        } elseif ($userModel->passwordVerify($row->password, $data['password'])) {
            $row->lastVisitIP   = \Qing\Lib\Utils::getClientIp();
            $row->lastVisitTime = time();
            $row->update();

            //取得角色
            $result                               = $this->getRoles($row->uid);
            $result[$this->_accessTokenUserIdKey] = $row->uid;
            $accessToken                          = $this->_createAccessToken($result);

            //取得可以访问的菜单
            $menuService = new MenuService($this->_di);
            $aclService  = new AclService($this->_di);
            $aclService->setUserId($row->uid);
            $aclService->setRoles($result['console.roles']);
            $menuService->setAclService($aclService);
            $returnData['menuList'] = $menuService->getAll(true, true);

            $returnData['accessToken'] = $accessToken;
            $returnData['uid']         = $row->uid;
            $returnData['username']    = $row->username;

            //返回当前用户可以看的菜单列表

            return $returnData;
        } else {
            throw new ApiException($this->translator->_('账户或密码错误'));
        }
    }

    /**
     * 载入角色信息
     *
     * @param integer $userId
     */
    private function getRoles($userId)
    {
        $acc                        = new AccService($this->_di);
        $roles                      = $acc->findRolesByUserid($userId);
        $data['console.roles']      = $roles;
        $data['console.super_role'] = false;
        if (is_array($roles)) {
            $superRoles = $acc->findRolesByTypeId(AccService::TYPE_ADMIN);

            if (is_array($superRoles)) {
                $ids = [];
                foreach ($roles as $role) {
                    $ids[] = $role['id'];
                }
                $data['console.role_ids'] = $ids;
                foreach ($superRoles as $superRole) {
                    if (in_array($superRole['id'], $ids)) {
                        $data['console.super_role'] = true;
                        unset ($superRoles);
                        break;
                    }
                }
            }
        }

        return $data;
    }
}