<?php

class RichText_AdminModule extends AdminModule
{
	/* uri for this module in generated menu */
	const DEFAULT_URI = ':RichText:Admin:Default:';
	
	/* label for this module shown in generated menu */
	const MENU_LABEL = 'RichText';

	
	/**
	 * registers module in application
	 */
	public static function register()
	{
		parent::registerModule(__CLASS__);
	}

}
