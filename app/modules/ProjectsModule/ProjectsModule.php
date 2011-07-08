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
	}

}
