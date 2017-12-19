<?php

namespace Kuga\Core\Acc\Model;

use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\ModelException;
use Kuga\Core\Acc\Service\Acl as  AclService;
/**
 * 分配菜单给角色
 *
 * @author dony
 *
 */
class RoleMenuModel extends AbstractModel
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
    public $mid;

    public function getSource()
    {
        return 't_role_menu';
    }

    public function initialize()
    {
        parent::initialize();
        $this->belongsTo("rid", "RoleModel", "id",['namespace'=>'Kuga\\Core\\Acc\\Model']);
        $this->belongsTo("mid", "MenuModel", "id",['namespace'=>'Kuga\\Core\\Acc\\Model']);
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap()
    {
        return ['rid' => 'rid', 'mid' => 'mid'];
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
}
