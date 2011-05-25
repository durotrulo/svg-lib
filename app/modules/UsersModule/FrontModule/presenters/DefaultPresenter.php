<?php

class Users_Front_DefaultPresenter extends Front_BasePresenter
{

	protected function startup()
	{
		parent::startup();
		$this->model = new UsersModel();
//		$this->config = Environment::getConfig('settings');
	}

	
	protected function beforeRender()
	{
		parent::beforeRender();
		$this->setLayout('simpleLayout');
	}
	
	
	public function renderEdit()
	{
//		$form = $this['credentialsForm'];
		$form = $this['userInfoForm'];
		if (!$form->isSubmitted()) {
			$row = $this->model->find($this->userId);
			if (!$row) {
				throw new BadRequestException(RECORD_NOT_FOUND);
			}
			$form->setDefaults($row);
//			$this->invalidateControl('itemForm');
			$this->invalidateControl('itemList');
		}
	}

	
	/**
	 * form for setting only credentials - mandatory
	 *
	 * @return MyAppForm
	 */
	protected function createComponentCredentialsForm()
	{
		$form = new MyAppForm;
		$form->getElementPrototype()->class('ajax');
		
		if (!is_null($this->getTranslator())) {
			$form->setTranslator($this->getTranslator());
		}
		
		$form->addText('username', 'Prihlasovacie meno')
				->addRule(Form::FILLED)
                ->addRule(Form::MIN_LENGTH, NULL, 3)
            	->addRule(Form::MAX_LENGTH, NULL, 30);
			
		$form->addPassword("password", "Nové heslo", 60)
				->addRule(Form::FILLED)
	            ->addRule(Form::MIN_LENGTH, NULL, 6);

		$form->addPassword("password2", "Potvrďte nové heslo", 60)
				->addRule(Form::FILLED, "%label !")
	            ->addRule(Form::EQUAL, 'Heslá sa nezhodujú!', $form['password']);

		$form->addPassword("currentPassword", "Stávajúce heslo", 60)
				->addRule(Form::FILLED);

		$form->addSubmit('save', 'Nastav');
		$form->onSubmit[] = callback($this, 'save');
		
		/*
		$_this = $this;
		$form->onSubmit[] = function(MyAppForm $form) use($_this) {
			try {
				if ($form['save']->isSubmittedBy()) {
					$values = $form->getValues();
					unset($values['password2']);
					
					$_this->model->updateLoggedUser($values, true);
					$_this->flashMessage('Password updated.', $_this::FLASH_MESSAGE_SUCCESS);
		
				}
			} catch (DibiDriverException $e) {
				throw $e;
				$_this->flashMessage("ERROR: cannot save data!", $_this::FLASH_MESSAGE_ERROR);
			} catch (InvalidPasswordException $e) {
				$_this->flashMessage($e->getMessage(), $_this::FLASH_MESSAGE_ERROR);
			}
	
			$_this->refresh(null, 'this');
		};*/
		
		return $form;
	}
	
	
	/**
	 * factory for form for setting complete user info
	 *
	 * @return MyAppForm
	 */
	protected function createComponentUserInfoForm()
	{
		$form = new MyAppForm;
		$form->getElementPrototype()->class('ajax');
		
		if (!is_null($this->getTranslator())) {
			$form->setTranslator($this->getTranslator());
		}
		
		$form->addText('firstname', 'First Name')
            ->addRule(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, NULL, 2)
            ->addRule(Form::MAX_LENGTH, NULL, 70);

        $form->addText('lastname', 'Last Name')
            ->addRule(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, NULL, 2)
            ->addRule(Form::MAX_LENGTH, NULL, 70);

        $form->addText('username', 'User Name')
            ->addRule(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, NULL, 3)
            ->addRule(Form::MAX_LENGTH, NULL, 30);
	   
        $form->addText('email', 'E-Mail')
	            ->setEmptyValue('@')
	            ->addRule(Form::FILLED)
	            ->addRule(Form::EMAIL)
	            ->addRule(Form::MAX_LENGTH, NULL, 60);
	        
     	$form->addPassword('password', 'Password')
			->setOption('description', 'Fill in only if you want to change current password')
	    	->addCondition(Form::FILLED)
		    	->addRule(Form::MIN_LENGTH, NULL, 6);

		$form['password']->getControlPrototype()->autocomplete('off');

	    $form->addPassword('password2', 'Confirm Password')
	    	->setOption('description', 'Fill in only if you want to change current password')
				->addConditionOn($form['password'], Form::FILLED)
					->addRule(Form::FILLED, 'Confirm your password!')
		            ->addRule(Form::EQUAL, 'No match for passwords!', $form['password']);
	
		$form->addPassword("currentPassword", "Current Password", 60)
	    	->setOption('description', 'Fill in only if you want to change current password')
				->addConditionOn($form['password'], Form::FILLED)
					->addRule(Form::FILLED);

		$form->addSubmit('save', 'Nastav');
		$form->onSubmit[] = callback($this, 'save');
		
		return $form;
	}
	
	
	/**
	 * save user info
	 *
	 * @param MyAppForm
	 */
	public function save(MyAppForm $form)
	{
		try {
			if ($form['save']->isSubmittedBy()) {
				$values = $form->getValues();
				
				$this->model->updateLoggedUser($values, true);
				$this->flashMessage('Data updated.', $this::FLASH_MESSAGE_SUCCESS);
			}
		} catch (DibiDriverException $e) {
			$this->flashMessage("ERROR: cannot save data!", $this::FLASH_MESSAGE_ERROR);
		} catch (InvalidPasswordException $e) {
			$this->flashMessage($e->getMessage(), $this::FLASH_MESSAGE_ERROR);
		}

		$this->refresh(null, 'this');
	}
	
}
