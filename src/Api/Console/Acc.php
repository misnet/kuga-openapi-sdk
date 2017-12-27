<?php

namespace Kuga\Api\Console;

use Kuga\Core\Acc\Model\RoleUserModel;
use Kuga\Core\Acc\Model\RoleModel;
use Kuga\Core\Api\Exception as ApiException;
use Kuga\Core\User\UserModel;

/**
 *
 * @package Kuga\Api\Console
 */
class Acc extends BaseApi
{

    /**
     * 给某些用户分配指定的角色
     */
    public function assignRoleToUsers()
    {
        $data        = $this->_toParamObject($this->getParams());
        $data['rid'] = intval($data['rid']);
        $roleRow     = RoleModel::findFirstById($data['rid']);
        if ( ! $roleRow) {
            throw new ApiException($this->translator->_('指定的角色不存在'));
        }
        $idList  = explode(',', $data['uid']);
        $success = 0;
        foreach ($idList as $uid) {
            $uid = intval($uid);
            if ($uid) {
                $hasAssigned = RoleUserModel::count(['rid=?1 and uid=?2', 'bind' => [1 => $data['rid'], 2 => $uid]]);
                if ( ! $hasAssigned) {
                    $row      = new RoleUserModel();
                    $row->rid = $data['rid'];
                    $row->uid = $uid;
                    $result   = $row->create();
                    if ($result) {
                        $success++;
                    }
                }
            }
        }
        return true;
    }

    /**
     * 取消某些用户的指定角色
     */
    public function unassignRoleToUsers()
    {
        $data        = $this->_toParamObject($this->getParams());
        $data['rid'] = intval($data['rid']);
        $roleRow     = RoleModel::findFirstById($data['rid']);
        if ( ! $roleRow) {
            throw new ApiException($this->translator->_('指定的角色不存在'));
        }
        $idList  = explode(',', $data['uid']);
        $success = 0;
        $ruModel = new RoleUserModel();
        $sql = 'delete from '.RoleUserModel::class.' where rid=:rid: and uid in ({uid:array})';
        $bind=[
            'rid'=>$data['rid'],
            'uid'=>$idList
        ];
        $result = $ruModel->getModelsManager()->executeQuery($sql,$bind);
        return $result->success()===true;
    }

    /**
     * 列出角色已分配的用户列表
     *
     * @return array
     */
    public function listRoleUser()
    {
        $data       = $this->_toParamObject($this->getParams());
        $data['rid'] = intval($data['rid']);
        $bind       = ['rid' => $data['rid']];
        $roleRow     = RoleModel::findFirst([
            'id=?1',
            'bind'=>[1=>$data['rid']],
            'columns'=>['id','name']
        ]);
        if ( ! $roleRow) {
            throw new ApiException($this->translator->_('指定的角色不存在'));
        }
        $searcher   = RoleUserModel::query();
        $searcher->join(UserModel::class, RoleUserModel::class.'.uid=user.uid', 'user');
        $searcher->columns(
            ['user.uid']
        );
        $searcher->orderBy(RoleUserModel::class.'.id desc');
        $searcher->where('rid=:rid:');
        $searcher->bind($bind);
        $result       = $searcher->execute();
        $assignedList = $result->toArray();

        $userSearcher = UserModel::query();
        //$userSearcher->where(UserModel::class.'.uid not in (select '.RoleUserModel::class.'.uid from '.RoleUserModel::class.' where rid=:rid:)');
        //$userSearcher->bind($bind);
        $userSearcher->columns(
            [UserModel::class.'.uid', 'username']
        );
        $result         = $userSearcher->execute();
        $unassignedList = $result->toArray();

        return ['role'=>$roleRow->toArray(),'assigned' => $assignedList, 'unassigned' => $unassignedList,];
    }

    /**
     * 角色列表
     */
    public function listRole()
    {
        $data          = $this->_toParamObject($this->getParams());
        $data['page']  = intval($data['page']);
        $data['limit'] = intval($data['limit']);
        $data['limit'] || $data['limit'] = 10;
        $data['page'] || $data['page'] = 1;

        $searcher = RoleModel::query();
        $searcher->columns('count(0) as total');
        $result = $searcher->execute();
        $total  = $result->getFirst()->total;

        $searcher->columns(
            [RoleModel::class.'.id', RoleModel::class.'.name', RoleModel::class.'.defaultAllow', RoleModel::class.'.assignPolicy',
             RoleModel::class.'.priority', RoleModel::class.'.roleType',
             '(select count(0) from '.RoleUserModel::class.' where rid='.RoleModel::class.'.id) as cntUser']
        );
        $searcher->orderBy('priority asc,id desc');
        $result = $searcher->execute();
        $list   = $result->toArray();

        return ['total' => intval($total), 'list' => $list, 'page' => $data['page'], 'limit' => $data['limit']];
    }

    /**
     * 创建角色
     */
    public function createRole()
    {
        $data = $this->_toParamObject($this->getParams());
        $row  = new RoleModel();
        $row->initData($data->toArray(), ['id']);
        $result = $row->create();
        if ( ! $result) {
            throw new ApiException($row->getMessages()[0]->getMessage());
        }

        return $result;
    }

    /**
     * 修改角色
     */
    public function updateRole()
    {
        $data       = $this->_toParamObject($this->getParams());
        $data['id'] = intval($data['id']);
        $row        = RoleModel::findFirstById($data['id']);
        if ( ! $row) {
            throw new ApiException(ApiException::$EXCODE_NOTEXIST);
        }
        $row->initData($data->toArray());
        $result = $row->update();
        if ( ! $result) {
            throw new ApiException($row->getMessages()[0]->getMessage());
        }

        return $result;
    }
}