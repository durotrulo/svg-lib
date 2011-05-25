<?php

abstract class Admin_BasePresenter extends BasePresenter
{
	/**
	 * acl resource and privilege name, each descendant should declare these constants if custom ACL required (otherwise these const used)
	 * @dbsync
	 */
	const ACL_RESOURCE = 'administration';
	const ACL_PRIVILEGE = 'admin';
	
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
			
			if (!$user->isAllowed(static::ACL_RESOURCE, static::ACL_PRIVILEGE)) {
				$this->unauthorized();
			}
		}
	}
	
	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->modules = AdminModule::getModules();
		
		$this->template->title = 'Admin';
		$this->template->heading = 'Administrácia';

		$this->template->isAdminModule = TRUE;
	}
	
	protected function unauthorized()
	{
		$this->flashMessage('Prístup zamietnutý! Dôvod: nedostatočné oprávnenia', self::FLASH_MESSAGE_ERROR);
//		$this->redirect(':Front:Login:login', $this->getApplication()->storeRequest());
		$this->redirect(':Front:Login:login');
	}
	
}