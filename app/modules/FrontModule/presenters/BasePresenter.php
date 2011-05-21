<?php

abstract class Front_BasePresenter extends BasePresenter
{
//	/** @persistent */
//	public $orderBy;

	
	
	protected function startup()
	{
		parent::startup();

		// user authentication
		$user = $this->getUser();
		if (!$user->isLoggedIn()) {
			if ($user->getLogoutReason() === User::INACTIVITY) {
				$this->flashMessage('Z dôvodu nečinnosti ste boli odhlásení', self::FLASH_MESSAGE_INFO);
				$this->refresh(null, ':Front:Login:login');
			} else {
				$this->flashMessage('Pre vstup musíte byť prihlásení', self::FLASH_MESSAGE_ERROR);
			}
			
			$this->redirect(':Front:Login:login', $this->getApplication()->storeRequest());
//		} else {
			
		}
	}
	

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->isHomepage = false;
	}
	
	
	protected function createComponentNavigation($name)
	{
		$nav = new NavigationControl($this, $name);
//		$nav->setupHomepage("Úvod", $this->link("Homepage:"));
		/*
		$lang = $this->lang;
		$menuItems = dibi::select("id, label_$lang AS label, nette_link, nette_link_args")
			->from('menu')
			->fetchAll();
			
		foreach ($menuItems as $item) {
			$args = !is_null($item->nette_link_args) ? Basic::string2array(str_replace('%id%', $item->id, $item->nette_link_args)) : array();
			
			switch ($item->id) {
				case 3:
					$currentLink = ':News:Front:Default:*';
					break;
					
				case 5:
					$currentLink = ':Publications:Front:Default:*';
					break;
			
				default:
					$currentLink = null;
					break;
			}
			
			$nav->add($item->label, $this->link($item->nette_link, $args), $currentLink);
		}*/
	}
	
	
	protected function downloadFile($srcFile, $publicFilename = null)
	{
		if (empty($publicFilename)) {
			$publicFilename = basename($srcFile);
		}
		
		$filedownload = new FileDownload;
		$filedownload->sourceFile = $srcFile;
		$filedownload->transferFileName = $publicFilename;
		$filedownload->download();
		exit(0);
	}

}
