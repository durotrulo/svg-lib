<?php
/**
 * Acl model
 *
 * @author  Tomas Marcanik
 * @package GUI for Acl
 */
class AclModel extends BaseModel
{
    /**
     * Put in to array parents of specific role
     *
     * @param integer ID of parent role
     * @param string Key name of parent role
     */
    public function getParentRole($parent_id, $parent_key, &$roles) {
        $sql = dibi::query('SELECT id, key_name
                                FROM [' . self::ACL_ROLES_TABLE . ']
                                WHERE %and;', array('parent_id' => $parent_id));
        $rows = $sql->fetchAll();
        if (count($sql)) {
            foreach ($rows as $row) {
                $roles[] = array('key_name' => $row->key_name, 'parent_key' => $parent_key);
                $this->getParentRole($row->id, $row->key_name, $roles);
            }
        }
    }
    /**
     * Return all roles hierarchically ordered
     *
     * @return  array
     */
    public function getRoles() {
        $roles = array();
        $this->getParentRole(NULL, NULL, $roles);
        return $roles;
    }

    /**
     * Put in to array parents of specific resource
     *
     * @param integer ID of parent resource
     * @param string Key name of parent resource
     * @param array Array of all resource
     */
    public function getParentResource($parent_id, $parent_key, &$resources) {
        $sql = dibi::query('SELECT id, key_name
                                FROM [' . self::ACL_RESOURCES_TABLE . ']
                                WHERE %and;', array('parent_id' => $parent_id));
        $rows = $sql->fetchAll();
        if (count($sql)) {
            foreach ($rows as $row) {
                $resources[] = array('key_name' => $row->key_name, 'parent_key' => $parent_key);
                $this->getParentResource($row->id, $row->key_name, $resources);
            }
        }
    }
    /**
     * Return all resources hierarchically ordered
     *
     * @return  array
     */
    public function getResources() {
        $resources = array();
        $this->getParentResource(NULL, NULL, $resources);
        return $resources;
    }

    /**
     * Return all rules of permissions
     * 
     * @return  object
     */
    public function getRules() {
         $sql = dibi::query('
            SELECT
                a.access as access,
                ro.key_name as role,
                re.key_name as resource,
                p.key_name as privilege,
                asr.class as assertion
                FROM [' . self::ACL_TABLE . '] a
                JOIN [' . self::ACL_ROLES_TABLE . '] ro ON (a.role_id = ro.id)
                LEFT JOIN [' . self::ACL_RESOURCES_TABLE . '] re ON (a.resource_id = re.id)
                LEFT JOIN [' . self::ACL_PRIVILEGES_TABLE . '] p ON (a.privilege_id = p.id)
               	LEFT JOIN [' . self::ACL_ASSERTIONS_TABLE . '] asr ON a.assertion_id=asr.id
                ORDER BY a.id ASC
        ');
         $sql->setType('access', Dibi::BOOL);
         return $sql->fetchAll();
    }
}

/**
 * Acl object
 *
 * @author  Tomas Marcanik
 * @package GUI for Acl
 */
class Acl extends Permission
{
	public function __construct()
	{
        $model = new AclModel();

        $roles = $model->getRoles();
        foreach($roles as $role) {
//        	dump($role['key_name'], $role['parent_key']);
            $this->addRole($role['key_name'], $role['parent_key']);
        }
        
        $resources = $model->getResources();
        foreach($resources as $resource) {
//        	dump($resource['key_name'], $resource['parent_key']);
            $this->addResource($resource['key_name'], $resource['parent_key']);
        }
        
        foreach($model->getRules() as $rule) {
        	if (!is_null($rule->assertion)) {
        		$rule->assertion = new $rule->assertion;
        	}
//        	dump($rule->access ? 'allow' : 'deny', $rule->role, $rule->resource, $rule->privilege, $rule->assertion);
        	
            $this->{$rule->access ? 'allow' : 'deny'}($rule->role, $rule->resource, $rule->privilege, $rule->assertion);
        }
//        die();
    }
    
    
    /**
     * support for @http://forum.nette.org/cs/1231-2009-01-21-sikovnejsi-permission
     */
	public function isAllowed($role = self::ALL, $resource = self::ALL, $privilege = self::ALL)
	{
		$roleClassName = ucfirst($role) . "Role";
		if (class_exists($roleClassName)) {
			$role = new $roleClassName;
			$role->id = intval(Environment::getUser()->getIdentity()->data['id']);
		}
		
		return parent::isAllowed($role, $resource, $privilege);
	}
    
}
?>