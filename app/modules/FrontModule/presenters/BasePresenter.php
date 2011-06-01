<?php

abstract class Front_BasePresenter extends BasePresenter
{
	
	const RENDER_SECTION_FILEUPLOAD = 'fileUpload';
	const RENDER_SECTION_FILTERS = 'filters';
	const RENDER_SECTION_OPTIONS = 'options';
	
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
	

	/**
	 * set sections to be rendered
	 *
	 * @param array
	 */
	protected function setRenderSections($sections = array())
	{
		$defaults = array(
			self::RENDER_SECTION_FILEUPLOAD => true,
			self::RENDER_SECTION_FILTERS => false,
			self::RENDER_SECTION_OPTIONS => true,
		);

		$this->template->renderSections = array_merge($defaults, $sections);
	}
	
	
	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->isHomepage = false;
		
		$this->setRenderSections();
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
}
