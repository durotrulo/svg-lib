<?php

abstract class BaseModule extends Object 
{
	/** @var array of classnames of loaded modules */
	private static $_modules = array();
	
	public static function createRoutes(IRouter $router, $prefix = NULL) { }
	// todo: implement
//    public function setupPermission(SitePermission $permission) { }
//    public function setupHooks(SiteHooks $hooks) { }
	
	public static function registerModule($module)
	{
		// register module in closest true parent module [ie. AdminModule, FrontModule]
		if (!in_array($module, static::$_modules)) {
			static::$_modules[] = $module;
		}
		
		// then register in BaseModule
		if (!in_array($module, self::$_modules)) {
			self::$_modules[] = $module;
		}
		
	}
	
	public static function unregisterModule($module){} //todo: implement?
	
	public static function getModules()
	{
		return static::$_modules;
	}
	
	
	
	/**
	 * gets id [i.e. 'Admin:News'] from uri [i.e. 'news']
	 * gets id [i.e. 'News:Admin'] from uri [i.e. 'news'] - reversed order
	 *
	 * @param string $uri lowercase submodule name 
	 * @return string|null
	 */
	public static function findIdByUri($uri)
	{
		foreach (static::$_modules as $module) {
			$moduleName = substr($module, 0, -12); // 12 for '_FrontModule', resp. '_AdminModule' -> i.e. News
			if (strtolower($moduleName) == $uri) {
				return $moduleName . static::ROUTE_PREFIX; // - reversed order
			}
		}
		return null;
	}
	
	
	/**
	 * gets uri [i.e. 'news'] from id [i.e. 'Admin:News']
	 * gets uri [i.e. 'news'] from id [i.e. 'News:Admin'] - reversed order
	 *
	 * @param string $id submodule route 
	 * @return string|null
	 */
	public static function findUriById($id)
	{
//		$moduleNameFromId = substr($id, strlen(self::ROUTE_PREFIX));

		// News:Admin
		$submoduleNameFromId = substr($id, 0, -6); // News
		$moduleNameFromId = substr($id, -5); // Admin|Front
		
		/*
		// Admin:News
		$orig_submoduleNameFromId = substr($id, 6); // News
		$orig_moduleNameFromId = substr($id, 0, 5); // Admin|Front
		*/
		
		foreach (static::$_modules as $module) {
			$submoduleName = substr($module, 0, -12); // 12 for '_FrontModule', resp. '_AdminModule' -> i.e. News
			$moduleName = substr($module, -11, 5); // Front | Admin
			if (
				$submoduleName == $submoduleNameFromId
				&& $moduleName == $moduleNameFromId	
			) {
				return strtolower($submoduleName);
			}
			
			
			/*
			if (
				$submoduleName == $orig_submoduleNameFromId
				&& $moduleName == $orig_moduleNameFromId	
			) {
				return strtolower($submoduleName);
			}
			*/
		}
		return null;
	}

}