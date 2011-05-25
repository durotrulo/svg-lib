<?php

class Acl_AdminModule extends AdminModule
{
	/* uri for this module in generated menu */
	const DEFAULT_URI = ':Acl:Admin:Users:';
	
	/* label for this module shown in generated menu */
	const MENU_LABEL = 'Acl';

	
	/**
	 * registers module in application
	 */
	public static function register()
	{
		parent::registerModule(__CLASS__);
	}

}
