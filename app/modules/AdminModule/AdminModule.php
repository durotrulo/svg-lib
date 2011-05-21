<?php

class AdminModule extends BaseModule
{

	/** @const default route prefix for links to all admin submodules */
//	const ROUTE_PREFIX = 'Admin:';
	const ROUTE_PREFIX = ':Admin'; // - reversed order

		
	/** @var array of classnames of loaded modules */
	protected static $_modules = array();
	
	
	/**
	 * creates routes for all admin submodules
	 *
	 * @param IRouter $router
	 * @param string $prefix
	 */
	public  static function createRoutes(IRouter $router, $prefix = 'admin')
	{

		// Nette does NOT know <submodule> but only <module>
		$router[] = new Route('[<lang [a-z]{2}>/]' . $prefix . '/<module>/<presenter>/<action>/<id>', array(
			'module' => array(
                Route::FILTER_IN => callback('AdminModule::findIdByUri'),
                Route::FILTER_OUT => callback('AdminModule::findUriById'),
//                Route::VALUE => 'Admin:News',
	        ),
        
			'presenter' => 'Default',
			'action' => 'default',
			'lang' => NULL,
			'id' => NULL,
		));

	}
	
}
