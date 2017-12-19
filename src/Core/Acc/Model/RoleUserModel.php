<?php

namespace Kuga\Core\Acc\Model;
use Kuga\Core\Acc\Service\Acl as AclService;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\ModelException;

/**
 * 分配角色给用户
 *
 * @author dony
 *
 */
class RoleUserModel extends AbstractModel
{

    /**
     *
     * @var integer
     */
    public $rid;

    /**
     *
     * @var integer
     */
    public $uid;

    public $id;

    public $roleName;

    public $username;

    public function getSource()
    {
        return 't_role_user';
    }

    public function initialize()
    {
        parent::initialize();
        $this->belongsTo('rid', 'RoleModel', 'id',['namespace'=>'Kuga\\Core\\Acc\\Model']);
        $this->belongsTo('uid', 'UserModel', 'uid',['namespace'=>'Kuga\\Core\\User']);
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap()
    {
        return ['id' => 'id', 'rid' => 'rid', 'uid' => 'uid'];
    }

    public function joinFind($cond, $cols = [])
    {
        if (empty($cols)) {
            $cols = ['*', '`rid;name`' => 'roleName', '`uid;username`' => 'username'];
        }

        return parent::joinFind($cond, $cols);
    }

    public function beforeSave()
    {
        $acc     = new AclService();
        $isAllow = $acc->isAllowed('RES_ACC', 'OP_ASSIGN');
        if ( ! $isAllow) {
            throw new ModelException($this->_('对不起，您无权限进行此操作'));
        }

        return true;
    }

    public function beforeDelete()
    {
        $acc     = new AclService();
        $isAllow = $acc->isAllowed('RES_ACC', 'OP_ASSIGN');
        if ( ! $isAllow) {
            throw new ModelException($this->_('对不起，您无权限进行此操作'));
        }

        return true;

    }
}
