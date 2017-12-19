<?php

namespace Kuga\Core\Menu;
use Kuga\Core\Base\AbstractModel;
use Kuga\Core\Base\ModelException;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf as PresenceOfValidator;
use Phalcon\Mvc\Model\Relation;
class MenuModel extends AbstractModel {
/**
	 *
	 * @var integer
	 */
	public $id;
	
	/**
	 *
	 * @var string
	 */
	public $name;
	
	/**
	 *
	 * @var string
	 */
	public $controller;
	/**
	 *
	 * @var string
	 */
	public $action;
	/**
	 *
	 * @var string
	 */
	public $parameter;
	/**
	 *
	 * @var integer
	 */
	public $parentId;
	
	/**
	 *
	 * @var integer
	 */
	public $sortByWeight;
	
	public $className;
	
    public $extName = '';
	/**
	 * Independent Column Mapping.
	 */
	public function columnMap() {
		return array (
				'id' => 'id',
				'name' => 'name',
				'controller' => 'controller',
				'action' => 'action',
				'parameter' => 'parameter',
				'parent_id' => 'parentId',
				'sort_by_weight' => 'sortByWeight',
				'display' => 'display',
				'class_name'=>'className',
                'ext_name' => 'extName'
		);
	}
	
	public function getSource() {
		return 't_menu';
	}
	
	public function initialize(){
		parent::initialize();
		//实现菜单删除时，权限分配菜单给角色的记录也删除
		$this->hasMany("id", "RoleMenuModel", "mid",array(
			'foreignKey'=>array(
				'action'=>Relation::ACTION_CASCADE
			),
            'namespace'=>'\\Kuga\\Core\\Acc\\Model'
		));
	}
	private static $_triggerDeleteTree = false;
	public function afterDelete(){
	    if(false===self::$_triggerDeleteTree){
	        self::$_triggerDeleteTree = true;
	        $rows = $this->find(array('conditions'=>'parentId=?1','bind'=>array(1=>$this->id)));
	        if($rows){
	            $rows->delete();
	        }
	        self::$_triggerDeleteTree = false;
	    }
	    $menuService = new MenuService();
	    $menuService->clearMenuAccessCache();
	}
	
	/**
	 * Validations and business logic
	 */
	public function validation() {
	    $validator = new Validation();
	    
	    $validator->add('name', new PresenceOfValidator([
	        'model'=>$this,
	        'message'=>$this->translator->_('菜单名必须填写')
	    ]));
	    return $this->validate($validator);
	}

    /**
     * 保存前钩子
     * @return bool
     * @throws Exception
     */
	public function beforeSave(){
	    if($this->id){
    	    $cond = [
    	        'conditions'=>'controller=?1 and action=?2 and parameter=?3 and extName=?4',
    	        'bind'=>[1=>$this->controller,2=>$this->action,3=>$this->parameter,4=>$this->extName]
    	    ];
    	    if($this->id){
    	        $cond['conditions'].=' and id!=?4';
    	        $cond['bind'][4] = $this->id;
    	    }
    	    $existRow = self::findFirst($cond);
    	    $childList = self::find([
    	        'parentId=?1',
                'bind'=>[1=>$this->id]
            ]);
	        if(!$this->controller && !$this->action && !$$this->parameter && !sizeof($childList)){
	            throw new ModelException('当菜单有子菜单时才可以不填controller,action,parameter');
	        }
            if($existRow && ($existRow->controller.$existRow->action.$existRow->parameter!='')){
                throw new ModelException('存在相同扩展名、controller、action、参数的菜单【'.$existRow->name.'】');
            }
            if($this->parentId==$this->id){
                throw new ModelException($this->translator->_('父级菜单不能是自己'));
            }
            //只支持了2级，多级不支持
            foreach($childList as $node){
                if($node->id==$this->parentId){
                    throw new \Exception($this->translator->_('父级菜单不能是当前菜单的子菜单'));
                }
            }

	    }
	    return true;
	}
	public function afterSave(){
	    $menuService = new MenuService();
	    $menuService->clearMenuAccessCache();
	}
}
