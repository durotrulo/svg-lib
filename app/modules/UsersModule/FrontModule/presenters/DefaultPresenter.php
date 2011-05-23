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
		$form = $this['credentialsForm'];
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

	
	protected function createComponentCredentialsForm()
	{
		$form = new MyAppForm;
		$form->getElementPrototype()->class('ajax');
		
		if (!is_null($this->getTranslator())) {
			$form->setTranslator($this->getTranslator());
		}
		
		$form->addText('username', 'Prihlasovacie meno')
				->addRule(Form::FILLED)
	            ->addRule(Form::MIN_LENGTH, "%label musí mať aspoň %d znakov.", 3);
			
		$form->addPassword("password", "Nové heslo", 60)
				->addRule(Form::FILLED)
	            ->addRule(Form::MIN_LENGTH, "%label musí mať aspoň %d znakov.", 6);

		$form->addPassword("password2", "Potvrďte nové heslo", 60)
				->addRule(Form::FILLED, "%label !")
	            ->addRule(Form::EQUAL, 'Heslá sa nezhodujú!', $form['password']);

		$form->addPassword("currentPassword", "Stávajúce heslo", 60)
				->addRule(Form::FILLED);

		$_this = $this;
		$form->addSubmit('save', 'Nastav');
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
		};
		
		return $form;
	}
	
}
