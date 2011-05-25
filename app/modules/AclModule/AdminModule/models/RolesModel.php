<?php
/**
 * Roles model
 *
 * @author  Tomas Marcanik
 * @package GUI for Acl
 */
class RolesModel extends BaseModel
{
	const TABLE = self::ACL_ROLES_TABLE;
	
	
    /**
     * Has parent of node children?
     *
     * @param   integer Parent id
     * @return  integer Number of children
     */
    public function hasChildNodes($parent_id) {
        $sql = dibi::query('SELECT * FROM ['. self::ACL_ROLES_TABLE .'] WHERE %and;', array('parent_id' => $parent_id));
        return count($sql);
    }

    /**
     * Return all children of specific parent of node
     *
     * @param   integer Parent id
     * @return  object
     */
    public function getChildNodes($parent_id) {
        $sql = dibi::query('SELECT r.id, r.name, r.comment, count(ur.user_id) AS members
                                FROM ['. self::ACL_ROLES_TABLE .'] AS r
                                LEFT JOIN ['. self::ACL_USERS_2_ROLES_TABLE .'] AS ur ON r.id=ur.role_id
                                WHERE %and
                                GROUP BY r.id, r.name, r.comment
                                ORDER BY r.name;', array('r.parent_id' => $parent_id));
        return $sql->fetchAll();
    }

    /**
     * Return all roles in the tree structure
     *
     * @return  array
     */
    public function getTreeValues() {
        $roles = array();
        $this->getParents(NULL, $roles, 0);
        return $roles;
    }
    /**
     * All children of specific parent of role placed in a array
     *
     * @param   integer Parent id
     * @param   array Array of curent resources
     * @param   integer Depth of tree structure
     */
    public function getParents($parent_id, &$array, $depth) {
        $sql = dibi::query('SELECT id, name FROM ['. self::ACL_ROLES_TABLE .'] WHERE %and ORDER BY name;', array('parent_id' => $parent_id));
        $rows = $sql->fetchAll();
        foreach ($rows as $row) {
            $array[$row->id] = ($depth ? str_repeat("- - ", $depth) : '').$row->name;
            $this->getParents($row->id, $array, ($depth+1));
        }
    }
    
    
    /**
     * update user roles [delete & insert]
     *
     * @param int User id
     * @param int Role id
     */
    public function updateUserRoles($userId, $roles)
    {
    	try {
	    	dibi::begin();
	    	dibi::delete(self::ACL_USERS_2_ROLES_TABLE)
	    		->where('user_id = %i', $userId)
	    		->execute();
	
	    	foreach ($roles as $role) {
		    	dibi::insert(self::ACL_USERS_2_ROLES_TABLE, array(
		    		'user_id' => $userId,
		    		'role_id' => $role,
		    	))->execute();
	    	}
	    	dibi::commit();
    	} catch (DibiDriverException $e) {
    		dibi::rollback();
    		throw $e;
    	}
    }
}
?>
