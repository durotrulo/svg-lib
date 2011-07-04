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
		$this->template->isAdminModule = false;
		
		
		$this->template->maxUploadedFilesCount = Environment::getConfig('upload')->maxUploadedFilesCount;
		
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

	
	protected function createComponentFilesControl($name)
	{
		$files = new FilesControl($this, $name);
		return $files;
	}
	
	
//	/**
//	 * set FilesControl template var and handle paging
//	 * called from beforeRender()
//	 *
//	 */
//	protected function setupFilesControl()
//	{
//		// init filesControl
//		$this->template->filesControl = $fileControl = $this['filesControl'];
//
//		$vp = $fileControl['itemPaginator'];
//		// when paging refresh only items
//		if ($vp->paginated && !$vp->itemsPerPageChanged) {
//			$this->invalidateControl('itemList');
//		} elseif (!$this->isControlInvalid()) {
//			$this->invalidateControl();
//		}
//	}
}
