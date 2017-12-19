<?php 
/**
 * 系统菜单Service
 *
 * @author dony
 * @category Qing
 * @package Service
 * @subpackage MenuService
 */
namespace Kuga\Core\Menu;
use Kuga\Core\Acc\Service\Acc;
use Kuga\Core\Base\AbstractService;
use Kuga\Core\Base\ServiceException;

class MenuService extends AbstractService {
	private $_menuObject;
    /**
     * @var \Kuga\Service\AclService
     */
	private $_aclService;
	const PREFIX_MENULIST = 'data:menuList:';

    /**
     * 注入权限判断服务ACL
     * @param $s
     */
    public function setAclService($s){
        $this->_aclService = $s;
    }
	/**
	 * 取出所有菜单，并按层级排好顺序
	 * @param integer $visible 是否可见，1:可见,0:不可见,null:所有
	 * @param boolean $filterByAcc 是否用权限系统过滤，为true时，请先调用setAclService方法，注入ACL服务
	 * @return array
	 */
	public function getAll($visible=null,$filterByAcc=false){
	    $cacheEngine = $this->_di->get('cache');
	    if($filterByAcc && $this->_aclService){
    	    $keySeed = array(
    	        'isAdmin'=>$this->_aclService->hasSuperRole(),
    	        'roleIds'=>$this->_aclService->getRoles()
            );
	    }else{
	        $keySeed=array('allMenu'=>true);
	    }   
	    $cacheId = self::PREFIX_MENULIST.md5(serialize($keySeed));
	    $data = $cacheEngine->get($cacheId);
	    if($data){
	        $this->_menuObject = $data;
	    }else{
    		$this->_menuObject= null;
    		$this->_findChildMenu(0,$visible);
    		//通知钩子
    		if($filterByAcc){
    			$this->_menuObject = $this->_filterMenus($this->_menuObject);
    		}
    		$cacheEngine->set($cacheId,$this->_menuObject);
	    }
		return $this->_menuObject;
	}
	/**
	 * 删除菜单缓存
	 */
	public function clearMenuAccessCache(){
	    $cacheEngine = $this->_di->get('cache');
	    $cacheEngine->deleteKeys(self::PREFIX_MENULIST);
	}
	/**
	 * 登陆判断过滤
	 * @param array $menuObjects
	 */
	private  function _filterMenus($menuObjects){
		//根据权限访问过滤

		$isAdmin = $this->_aclService && $this->_aclService->hasSuperRole();
		if(!$isAdmin && $this->_aclService){
			//超级角色的有全部权限
			$currentRoles = $this->_aclService->getRoles();
			if(is_array($currentRoles) && !empty($currentRoles)){
				//根据当前用户所具有的全部角色分析，只要有一个角色有访问权限就可以访问该菜单
				$accessableMenuIds = array();
				$service = new Acc();
				foreach($currentRoles as $role){
					$hasPrivMenuIds = $service->getMenuIdsByRoleId($role['id']);
					$accessableMenuIds = array_merge($hasPrivMenuIds,$accessableMenuIds);
				}
				$accessableMenuIds = array_unique($accessableMenuIds);
				$menus = array();
				if(is_array($menuObjects) && !empty($menuObjects)){
					foreach($menuObjects as $menu){
						if(in_array($menu['id'],$accessableMenuIds)){
							$menus[] = $menu;
						}
					}
				}
				$menuObjects = $menus;
			}else{
				//无权
				$menuObjects = array();
			}
		}
		return $menuObjects;
	}
	/**
	 * 检测菜单是否可以访问，菜单不在系统库中的，默认可以访问
	 * ---这种判断只细到controller和action，参数级的无法细分
	 * @param string $ctrl
	 * @param string $action
	 * @return boolean
	 */
	public function isAccessable($ctrl='',$action=''){
	    $_separator = '-';
	    $pattern = array('#(?<=(?:[A-Z]))([A-Z]+)([A-Z][A-z])#', '#(?<=(?:[a-z0-9]))([A-Z])#');
        $replacement = array('\1' . $_separator . '\2', $_separator . '\1');
        $action = preg_replace($pattern, $replacement, $action);
        $action = strtolower($action);
        $data   = $this->getAll(true,true);
        $filteredMenus  = $this->_formatMenuData($data);
        $data   = $this->getAll(true,false);
        $allMenus  = $this->_formatMenuData($data);
        
        $hasAccess = false;
        $existMenu = false;
	    if($filteredMenus){
	        foreach($filteredMenus as $menuId=>$menu){
	            if($menu['controller']==$ctrl && $menu['action']==$action){
	                $hasAccess = true;
	            }elseif(isset($menu['submenu'])){
	                foreach($menu['submenu'] as $submenu){
	                    if($submenu['controller']==$ctrl && $submenu['action']==$action){
	                        $hasAccess = true;
	                        break;
	                    }
	                }
	            }
	            if($hasAccess){
	                break;
	            }
	        }
	    }
	    if($allMenus){
	       foreach($allMenus as $menuId=>$menu){
	            if($menu['controller']==$ctrl && $menu['action']==$action){
	                $existMenu = true;
	            }elseif(isset($menu['submenu'])){
	                foreach($menu['submenu'] as $submenu){
	                    if($submenu['controller']==$ctrl && $submenu['action']==$action){
	                        $existMenu = true;
	                        break;
	                    }
	                }
	            }
	            if($existMenu){
	                break;
	            }
	        }
	    }
	    //不存在菜单时，可以访问
	    if(!$existMenu){
	        $hasAccess = true;
	    }
	    return $hasAccess;
	}
	/**
	 * 取得菜单树
	 * 1.系统过滤掉禁用的，当前用户没权限看的菜单
	 * 2.针对菜单的controller和action进行补齐或继承
	 * @throws Exception
	 * @return Ambigous <string, multitype:unknown , multitype:, NULL, array, unknown>
	 */
	private function _formatMenuData($data){
	    $returnData = array();
	    if($data){
	        $noActionMenu = array();
	        $noCtrlMenu = array();
	        foreach ($data as $item){
	            if(!$item['controller']){
	                //菜单未指定controller时可以继承父菜单的controller
	                if(isset($returnData[$item['parentId']]['controller']) && $returnData[$item['parentId']]['controller'])
	                    $item['controller'] = $returnData[$item['parentId']]['controller'];
	                else{
	                    $noCtrlMenu[] = $item;
	                }
	            }
	            if(!$item['action']){
	                $noActionMenu[] = $item;
	            }
	            if($item['action'] && $item['parameter']){
	                $item['url'] = QING_BASEURL.$item['controller'].'/'.$item['action'].'?'.$item['parameter'];
	            }else{
	                $item['url'] = QING_BASEURL.$item['controller'].'/'.$item['action'];
	            }
	            $item['hash'] = $item['controller'].':'.$item['action'];
	
	            if(isset($returnData[$item['parentId']])){
	                $returnData[$item['parentId']]['submenu'][$item['id']] = $item;
	            }else{
	                $returnData[$item['id']] = $item;
	            }
	        }
	        //补足controller与action
	        foreach($noCtrlMenu as $item){
	            if(isset($returnData[$item['id']]['submenu'])){
	                //从第一子节点补
	                reset($returnData[$item['id']]['submenu']);
	                $firstNode = current($returnData[$item['id']]['submenu']);
	                $item['controller'] = $firstNode['controller'];
	                $returnData[$item['id']]['controller'] = $item['controller'];
	                $returnData[$item['id']]['url'] = QING_BASEURL.$item['controller'].'/'.$item['action'];
	                $returnData[$item['id']]['hash'] = $item['controller'].':'.$item['action'];
	                if($item['parameter']){
	                    $returnData[$item['id']]['hash'] .='?'.$item['parameter'];
	                    $returnData[$item['id']]['url']  .='?'.$item['parameter'];
	                }
	            }elseif(isset($returnData[$item['parentId']]) &&$returnData[$item['parentId']]['controller']){
	                //从父菜单补
	                $item['controller'] = $returnData[$item['parentId']]['controller'];
	                $item['controller']  = QING_BASEURL.$item['controller'].'/'.$item['action'].'?'.$item['parameter'];
	                $returnData[$item['parentId']]['submenu'][$item['id']]['url']  = QING_BASEURL.$item['controller'].'/'.$item['action'];
	                $returnData[$item['parentId']]['submenu'][$item['id']]['hash'] = $item['controller'].':'.$item['action'];
	                if($item['parameter']){
	                    $returnData[$item['parentId']]['submenu'][$item['id']]['hash'] .='?'.$item['parameter'];
	                    $returnData[$item['parentId']]['submenu'][$item['id']]['url']  .='?'.$item['parameter'];
	                }
	            }else{
	                throw new ServiceException($this->_translator->_('菜单【%menuName%】未指定controller',['menuName'=>$item['name']]));
	            }
	        }
	        foreach($noActionMenu as $item){
	            if(isset($returnData[$item['id']]['submenu'])){
	                //从第一子节点补
	                reset($returnData[$item['id']]['submenu']);
	                $firstNode = current($returnData[$item['id']]['submenu']);
	                	
	                $item['controller'] = $returnData[$item['id']]['controller'];
	                $returnData[$item['id']]['action'] = $item['action'] = $firstNode['action'];
	                $returnData[$item['id']]['url']    = QING_BASEURL.$item['controller'].'/'.$item['action'];
	                $returnData[$item['id']]['hash']   = $item['controller'].':'.$item['action'];
	                if($item['parameter']){
	                    $returnData[$item['id']]['hash'] .='?'.$item['parameter'];
	                    $returnData[$item['id']]['url']  .='?'.$item['parameter'];
	                }
	            }else{
	                throw new ServiceException($this->translator->_('菜单【%actionName%】未指定action',['actionName'=>$item['name']]));
	            }
	        }
	        	
	    }
	    return $returnData;
	}
	/**
	 * 取直系子菜单列表
	 * @param number $pid 父菜单id
	 * @return array
	 */
	public function findByParentId($pid=0){
		$model = new MenuModel();
		$rows = $model->find(array(
				'conditions'=>'parentId=?1',
				'bind'=>array(1=>$pid),
				'order'=>'sortByWeight desc'
		));
		if($rows){
			return $rows->toArray();
		}
		return false;
	}
	/**
	 * 根据父级菜单id取出其下所有子孙级菜单
	 * @param integer $parentId  父级菜单id
	 * @param integer $visible $visible 是否可见，1:可见,0:不可见,null:所有
	 */
	private function _findChildMenu($parentId,$visible=null){
		$model = new MenuModel();
		$condition = 'parentId=:pid:';
		$bind['pid'] = $parentId;
		if(!is_null($visible)){
			$condition.=' and display=:v:';
			$bind['v'] = $visible?1:0;
		}
		$result= $model->find(array('conditions'=>$condition,'bind'=>$bind,'order'=>'sortByWeight desc'));
		if($result){
			$rows = $result->toArray();
			foreach($rows as $row){
				$this->_menuObject[] = $row;
				$this->_findChildMenu($row['id'],$visible);
			}
		}
	}
}