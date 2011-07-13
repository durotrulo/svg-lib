<?php

class ClientPackagesModule extends BaseModule
{
	
	/**
	 * registers module in application
	 */
	public static function register()
	{
		ClientPackages_FrontModule::register();
		ClientPackages_AdminModule::register();
	}

}
