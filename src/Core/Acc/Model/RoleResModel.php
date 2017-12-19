<?php

namespace Kuga\Core\Acc\Model;

use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\ModelException;
use Kuga\Core\Acc\Service\Acl as AclService;

/**
 * 分配资源给角色
 * @author dony
 *
 */
class RoleResModel extends AbstractModel {
	
	/**
	 *
	 * @var integer
	 */
	public $id;
	
	/**
	 *
	 * @var integer
	 */
	public $rid;
	
	/**
	 *
	 * @var string
	 */
	public $rescode;
	
	/**
	 *
	 * @var string
	 */
	public $opcode;
	
	/**
	 *
	 * @var integer
	 */
	public $is_allow;
	public function getSource() {
		return 't_role_res';
	}
	public function initialize(){
		parent::initialize();
		$this->belongsTo("rid", "RoleModel", "id",['namespace'=>'Kuga\\Core\\Acc\\Model']);
	}
	/**
	 * Independent Column Mapping.
	 */
	public function columnMap() {
		return array (
				'id' => 'id',
				'rid' => 'rid',
				'rescode' => 'rescode',
				'opcode' => 'opcode',
				'is_allow' => 'is_allow' 
		);
	}
    public function beforeSave(){
        $acc = new AclService();
        $isAllow = $acc->isAllowed('RES_ACC', 'OP_ASSIGN');
        if(!$isAllow){
            throw new ModelException($this->_('对不起，您无权限进行此操作'));
        }
        return true;
    }
}
