<?php

class FrontModule extends BaseModule
{

	/** @const default route prefix to all front submodules */
//	const ROUTE_PREFIX = 'Front:';
	const ROUTE_PREFIX = ':Front'; // - reversed order
	
	/** @var array of classnames of loaded modules */
	protected static $_modules = array();
	
	/** @var int to avoid recursion in calling createRoutes() */
	private static $countCalled = 0;
	
	
	/**
	 * creates routes for all front submodules
	 *
	 * @param IRouter $router
	 * @param string $prefix
	 */
	public static function createRoutes(IRouter $router, $prefix = NULL)
	{
		self::$countCalled++;
		if (self::$countCalled > 1) {
			return false;
//			die('recursive');
		}

		//	create routes on submodule first if there are any defined
		foreach (self::$_modules as $module) {
			if (method_exists($module, 'createRoutes')) {
				call_user_func_array(array($module, 'createRoutes'), array($router, $prefix));
			}	
		}
		
		// Nette does NOT know <submodule> but only <module>
		$router[] = new Route('[<lang [a-z]{2}>/]' . $prefix . '<module>/<presenter>/<action>/[<id>]', array(
			'module' => array(
                Route::FILTER_IN => callback('FrontModule::findIdByUri'),
                Route::FILTER_OUT => callback('FrontModule::findUriById'),
	        ),
			'presenter' => 'Default',
			'action' => 'default',
		));

	}
	
}
