<?php
/**
 * Access model
 *
 * @author  Tomas Marcanik
 * @package GUI for Acl
 */

class AccessModel extends BaseModel {
    /** @var array */
    private $access = array();

    /**
     * @param array Array of roles
     */
    public function __construct($roles) {
        $resources = dibi::fetchAll('SELECT key_name, name FROM [' . self::ACL_RESOURCES_TABLE . '] ORDER BY name;');
        $privileges = dibi::fetchAll('SELECT key_name, name FROM [' . self::ACL_PRIVILEGES_TABLE . '] ORDER BY name;');

        $acl = new Acl();
        $i = 0;
        foreach ($resources as $res) {
            foreach ($privileges as $pri) {
                foreach ($roles as $role) {
                    if (@$acl->isAllowed($role->key_name, $res->key_name, $pri->key_name)) { // @ to repress NOTICE if assertion required and resource property (id, owner_id, ...) not set yet
                        $this->access[$i]['resource'] = $res->name;
                        $this->access[$i]['privileg'] = $pri->name;
                        $i++;
                        break 1;
                    }
                }
            }
        } 
    }

    /**
     * @return  array Resources and privileges for current roles
     */
    public function getAccess() {
        return $this->access;
    }
}
?>
