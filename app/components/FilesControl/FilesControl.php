<?php

/**
 * Login control
 * 
 * generates login form and handles whole authentication process
 * 
 * 
 * options:
 * 	multiple rows / inline visual layout possible by css change
 *
 * @author Matus Matula
 */
class FilesControl extends BaseControl
{
	/** @var string redirect destination after logging in */
	public $redirectTo = 'this';

	/** @var bool use protection? */
	public $useProtection = false;
	
	/** @var bool use label over? */
	public $useLabelOver = true;
	
	/** @var bool use remember checkbox? */
	public $useRemember = false;
	
	/** @var bool use table layout? */
	public $useTableLayout = false;
	
	/** @var string how long user stays logged in when not remembered */
	public $shortExpiration = '+ 20 minutes';
	
	/** @var string how long user stays logged in when remembered */
	public $longExpiration = '+ 14 days';
	
	/** @var bool enable autocompletion? */
	public $useAutocomplete = false;
	
	/** @var bool enable ajax? */
	public $useAjax = false;
		

	/**
	 * return path to template containing head files (js, css) to be included
	 *
	 * @return string
	 */
	public function getHeadFilesTplPath()
	{
		$path = __DIR__ . '/headFiles.phtml';
		return $path;
	}
	
	/**
	 * return path to template containing head files (js, css) to be included
	 *
	 * @return string
	 */
	public function getOptionsTplPath()
	{
		$path = __DIR__ . '/options.phtml';
		return $path;
	}
	
	
	
	/**
	 * return path to template containing head files (js, css) to be included
	 *
	 * @return string
	 */
	public function getItemListTplPath()
	{
		$path = __DIR__ . '/itemList.phtml';
		return $path;
	}
  	
  	public function render($tplFile = NULL)
  	{
  		$this->setWebloaderPaths(); // kvoli tomu, ze sa vola komponenta viac krat, tak sa cesta k webloaderu nastavena v construct moze prepisat, treba to znova nastavit
  		
	    if ($this->useLabelOver) {
	  		$this->addCssFiles('labelOver.css');
	  		$this->addJsFiles('labelOver.js', 'labelOverReady.js');
	    }
	    
		$tpl = $this->createTemplate();
		if (!is_null($tplFile)) {
			$tpl->setFile(dirname(__FILE__) . $tplFile . '.phtml');
		} elseif ($this->useTableLayout) {
			$tpl->setFile(dirname(__FILE__) . '/tableLayout.phtml');
		} else {
			$tpl->setFile(dirname(__FILE__) . '/divLayout.phtml');
		}
		
  		$tpl->count = self::$count;
  		$tpl->render();
  	}
  	
  	/** NOT NEEDED, CSS CHANGE IS SUFFICIENT 
  	public function renderInline()
  	{
  		$this->render('inline');
  	}

  	public function renderMultipleRows()
  	{
  		$this->render('multipleRows');
  	}
  	**/
}