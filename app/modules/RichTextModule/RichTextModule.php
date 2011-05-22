<?php

class RichTextModule extends BaseModule
{
		
	/**
	 * registers module in application
	 */
	public static function register()
	{
		RichText_AdminModule::register();
//		RichText_FrontModule::register();
	}

}
