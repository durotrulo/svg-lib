<?php

class AclModule extends BaseModule
{
	
	/**
	 * registers module in application
	 */
	public static function register()
	{
		Acl_AdminModule::register();
		self::setupHooks();
	}
	
	
	protected static function setupHooks()
	{
		$db_config = Environment::getConfig('database');
        define('TABLE_ACL', $db_config->tables->acl);
        define('TABLE_PRIVILEGES', $db_config->tables->acl_privileges);
        define('TABLE_RESOURCES', $db_config->tables->acl_resources);
        define('TABLE_ROLES', $db_config->tables->acl_roles);
        define('TABLE_USERS', $db_config->tables->users);
        define('TABLE_USERS_ROLES', $db_config->tables->users_2_roles);
        
        $acl_config = Environment::getConfig('acl');
        define('ACL_RESOURCE', $acl_config->resource);
        define('ACL_PRIVILEGE', $acl_config->privilege);
        define('ACL_CACHING', $acl_config->cache);
        define('ACL_PROG_MODE', $acl_config->programmer_mode);
        
	}

}
