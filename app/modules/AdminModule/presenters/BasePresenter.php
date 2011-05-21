<?php

abstract class Admin_BasePresenter extends BasePresenter
{
	
	protected function startup()
	{
		parent::startup();

		// user authentication
		$user = $this->getUser();
		if (!$user->isLoggedIn()) {
			if ($user->getLogoutReason() === User::INACTIVITY) {
				$this->flashMessage('Z dôvodu nečinnosti ste boli odhlásení', self::FLASH_MESSAGE_INFO);
			} else {
				$this->flashMessage('Pre vstup do administrácie musíte byť prihlásení', self::FLASH_MESSAGE_ERROR);
//				$this->flashMessage('reason: ' . $user->getSignOutReason(), 'error');
			}
			
			$this->redirect(':Front:Login:login', $this->getApplication()->storeRequest());
		} else {
			
			/* todo:vymysliet univerzalne ulozisko konstant nastavenie konstant casto pouzivanych */
			define('RECORD_NOT_FOUND', $this->translate('record_not_found'));
			
			// TODO: doriesit ACL
//			$user_sections = array();
			// role => array of allowed presenters
			$allowed_sections = array(
				'newsletter-moderator' => array('newsletter'),
			);
			$presenter_name = strtolower(substr($this->getPresenter()->name, 6)); //trim Admin-
//			dump($presenter_name);
			
			//	ak to nie je admin a ide do sekcie, kam nema pristup, tak ho vratim na login form
			if (!$user->isInRole('admin')) {
				if (!isset($allowed_sections[$this->userIdentity->role])) {
					throw new Exception("Unknown user role! '{$this->userIdentity->role}'");
				}
				
				if (!in_array($presenter_name, $allowed_sections[$this->userIdentity->role])) {
					$this->unauthorized();
				}
			}
			$this->template->isAdminModule = TRUE;
		}
	}
	
	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->modules = AdminModule::getModules();
		
		$this->template->title = 'Admin';
		$this->template->heading = 'Administrácia';
	}
	
	protected function unauthorized()
	{
		$this->flashMessage('Prístup zamietnutý! Dôvod: nedostatočné oprávnenia', self::FLASH_MESSAGE_ERROR);
		$this->redirect(':Front:Login:login', $this->getApplication()->storeRequest());
	}
	
}