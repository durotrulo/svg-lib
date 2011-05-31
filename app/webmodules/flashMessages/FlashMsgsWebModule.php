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
  		
  		$assets = array(
  			'css' => array(),
  			'js' => array(),
  		);
  		
  		switch ($this->skin) {
	  		//	old static layout
  			case 'old-static':
  				$assets['js'][] = 'basic.js';
  				break;

  			case 'jGrowl':
  				$assets['js'][] = 'jGrowl.js';
  				$assets['js'][] = 'jquery.jgrowl.js';
  				$assets['css'][] = 'jquery.jgrowl.css';
  				break;
  				
	  		// fixed position, close all button, ...
  			default:
  				$assets['js'][] = 'advanced.js';
  				break;
  		}

  		// load js
  		foreach ($assets['js'] as $js) {
  			$this->addJsFile($js, '/js');
  		}
  		
  		// load css
		$skinSrc = 'skins/' . $this->skin;
  		$assets['css'][] = 'skin.css';
  		foreach ($assets['css'] as $css) {
			$this->addCssFile($css, $skinSrc . '/css');
  		}

  		// copy images to webtemp if not present, CSS urls rely on that
  		$this->copy($skinSrc . '/images');
	}
  	
	
	private function setParams($skin)
	{
		$this->skin = $skin;
	}
}