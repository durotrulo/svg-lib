<?php

class ClientPackages_AdminModule extends AdminModule
{
	/* uri for this module in generated menu */
	const DEFAULT_URI = ':ClientPackages:Admin:Default:add';
	
	/* label for this module shown in generated menu */
	const MENU_LABEL = 'Client Packages';

	
	/**
	 * registers module in application
	 */
	public static function register()
	{
		parent::registerModule(__CLASS__);
	}

}
