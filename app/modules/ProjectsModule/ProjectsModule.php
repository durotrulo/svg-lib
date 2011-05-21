<?php

class ProjectsModule extends BaseModule
{
	
	/**
	 * registers module in application
	 */
	public static function register()
	{
		Projects_AdminModule::register();
		Projects_FrontModule::register();

		// todo: vyriesit viac konfigov
//		Environment::loadConfig(__DIR__ . '/config.ini');
	}

}
