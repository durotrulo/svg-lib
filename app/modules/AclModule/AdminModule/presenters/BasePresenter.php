<?php
/**
 * GUI for Acl
 *
 * @copyright  Copyright (c) 2010 Tomas Marcanik
 * @package    GUI for Acl
 */

/**
 * Base class for all ACL presenters.
 *
 */
abstract class Acl_Admin_BasePresenter extends Admin_BasePresenter
{
	/**
	 * acl resource name
	 * @dbsync
	 */
	const ACL_RESOURCE = 'acl_permission';
	
	/**
	 * acl privilege name
	 * @dbsync
	 */
	const ACL_PRIVILEGE = 'acl_access';
	
	
    public $cache;

    public function startup() {
        parent::startup();

        $this->template->prog_mode = (ACL_PROG_MODE ? true : false);

        $this->template->current = $this->getPresenter()->getName();
    }
    
    
    protected function beforeRender()
    {
    	parent::beforeRender();
    	
        //copy assets to webtemp
    	$relPath = "/webtemp/acl";
    	$src = __DIR__ . "/../../assets";
    	$dest = WWW_DIR . $relPath;
        
    	if (!file_exists($dest)) {
			if (file_exists($src)) {
				Basic::copyr($src, $dest);
			} else {
				throw new ArgumentOutOfRangeException("Source path '$src' does NOT exist");
			}
		}

		$this->template->assetsBasePath = Environment::getVariable('basePath') . $relPath;
        $this->template->current = $this->getPresenter()->getName();
   }


    protected function createComponentPaginator($name)
	{
		$vp = new VisualPaginator($this, $name);
		$vp->isResultsCountChangable = false;
		return $vp;
	}
    
}
