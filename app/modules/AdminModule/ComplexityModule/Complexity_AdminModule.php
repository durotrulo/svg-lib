<?php

class Complexity_AdminModule extends AdminModule
{
	/* uri for this module in generated menu */
	const DEFAULT_URI = ':Complexity:Admin:Default:';
	
	/* label for this module shown in generated menu */
	const MENU_LABEL = 'Complexity';

	
	/**
	 * registers module in application
	 */
	public static function register()
	{
		parent::registerModule(__CLASS__);
		// todo: vyriesit viac konfigov
//		Environment::loadConfig(__DIR__ . '/config.ini');
	}

}
