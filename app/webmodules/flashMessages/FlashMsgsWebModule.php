<?php

/**
 * Flash Messages Web Module
 *
 * @author Matus Matula
 */
class FlashMsgsWebModule extends BaseWebModule implements IWebModule
{
	/** @const identifies module and destination dirname too */
	const ID = 'flashMessages';
	
	/** @var string Specifies skin to use. Mandatory */
	public $skin = 'big-dark-fixed';
	
  	public function init()
  	{
//  		parent::init();
  		
  		if (func_num_args() > 0) {
  			$args = func_get_args();
  			call_user_func_array(array($this, 'setParams'), $args);
  		}
  		
  		//	old static layout
  		if ($this->skin == 'old-static') {
	  		$this->addJsFile('basic.js', '/js');
  		} else {
  		// fixed position, close all button, ...
	  		$this->addJsFile('advanced.js', '/js');
  		}
  		
		$skinSrc = 'skins/' . $this->skin;
		$this->addCssFile('skin.css', $skinSrc . '/css');
  		$this->copy($skinSrc . '/images');	// copy images to webtemp if not present, CSS urls rely on that
	}
  	
	
	private function setParams($skin)
	{
		$this->skin = $skin;
	}
}