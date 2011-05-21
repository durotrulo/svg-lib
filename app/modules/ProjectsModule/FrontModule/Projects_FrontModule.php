<?php

/**
 * class News_FrontModule
 *
 */
class Projects_FrontModule extends FrontModule
{
	
	/**
	 * registers module in application
	 */
	public static function register()
	{
		parent::registerModule(__CLASS__);
	}

	
	/**
	 * creates routes for 'detail' action
	 *
	 * @param IRouter $router
	 * @param string $prefix
	 */
	public static function createRoutes(IRouter $router, $prefix = NULL)
	{

		// i.e /novinky/fabryka-vyhrala-kreativu-pre-senginer nad /novinky/list
		// or /novinky/detail/fabryka-vyhrala-kreativu-pre-senginer and /novinky/ for commented code
		$router[] = new Route('[<lang [a-z]{2}>/]' . $prefix . 'projects/<action>/[<id>]', array(
//		$router[] = new Route('<lang>/' . $prefix . 'novinky/<id> ? <action>', array(
//		$router[] = new Route('<lang>/' . $prefix . 'novinky/[<id [^(detail|list)]>/][<action>/]', array(
//		$router[] = new Route('<lang>/' . $prefix . 'novinky/[<id [^(detail)|(list)]>/][<action>/]', array(
			'module' => 'Projects:Front',
			'presenter' => 'Default',
			'action' => 'list',
//			'action' => 'detail',
//			'id' => array(
//                Route::FILTER_IN => callback('NewsSeoModel::findIdByUri'),
//                Route::FILTER_OUT => callback('NewsSeoModel::findUriById'),
//	        ),
		));

	}
	
}
