<?php

class UsersModule extends BaseModule
{
		
	/**
	 * registers module in application
	 */
	public static function register()
	{
		Users_AdminModule::register();
//		Stories_FrontModule::register();
		
//		parent::registerModule(__CLASS__);
		// todo: vyriesit viac konfigov
//		Environment::loadConfig(__DIR__ . '/config.ini');
	}

}
