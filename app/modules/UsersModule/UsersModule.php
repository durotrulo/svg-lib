<?php

class UsersModule extends BaseModule
{
		
	/**
	 * registers module in application
	 */
	public static function register()
	{
		Users_AdminModule::register();
		Users_FrontModule::register();
	}

}
