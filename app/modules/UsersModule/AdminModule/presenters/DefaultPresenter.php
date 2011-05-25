<?php

class Users_Admin_DefaultPresenter extends Admin_BasePresenter
{
	const ACL_RESOURCE = 'users_admin';
	const ACL_PRIVILEGE = 'admin';
	
	protected function startup()
	{
		parent::startup();
		$this->model = new UsersModel();
		$this->config = Environment::getConfig('users');
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->title = $this->translate('Users'); // optional, shown as heading and title of html page
		$this->template->description = $this->translate('Little piece of description for module Users'); // optional, describes functionality of module
		
		if ($this->getAction() === 'add' or 'edit') {
			$form = $this['itemForm'];
//			if ($form->isSubmitted()) {
				$this->template->users = $this->model->findAll(true);
//			}
		}
	}
	
	
	
  	private function prepareRoles()
  	{
  		return BaseModel::prepareSelect($this->model->findRoles(), 'User Role');
  	}

	
	/********************* view default *********************/
	
	
	public function renderDefault()
	{
//		$this->template->items = $this->model->getAll($this->lang);
	}
	

	/********************* views add & edit *********************/


	public function renderEdit($id = 0)
	{
		$form = $this['itemForm'];
		$form['save']->caption = 'Edit';
		if (!$form->isSubmitted()) {
			$row = $this->model->find($id);
			if (!$row) {
				throw new BadRequestException(RECORD_NOT_FOUND);
			}

//			if ($row->user_levels_id <= $this->userIdentity->user_levels_id) {
//				throw new OperationNotAllowedException('You don\'t have rights to edit this user. Contact superadmin for granting higher permissions.');
//			}
			
			$form->setDefaults($row);
			$this->invalidateControl('itemForm');
		}
		
		$this->setView('add');
	}


	/********************* component factories *********************/


	protected function createComponentItemForm()
	{
		$form = new MyAppForm;
		$form->enableAjax();
		
		if (!is_null($this->getTranslator())) {
			$form->setTranslator($this->getTranslator());
		}
		
		$form->addText('firstname', 'First Name')
            ->addRule($form::FILLED)
            ->addRule($form::MIN_LENGTH, NULL, 2)
            ->addRule($form::MAX_LENGTH, NULL, 70);

        $form->addText('lastname', 'Last Name')
            ->addRule($form::FILLED)
            ->addRule($form::MIN_LENGTH, NULL, 2)
            ->addRule($form::MAX_LENGTH, NULL, 70);

        $form->addText('username', 'User Name')
            ->addRule($form::FILLED)
            ->addRule($form::MIN_LENGTH, NULL, 3)
            ->addRule($form::MAX_LENGTH, NULL, 30);
	   
            
        if ($this->getAction() === 'add') {
        	$form->addPassword('password', 'Password')
	            ->addRule($form::FILLED)
	            ->addRule($form::MIN_LENGTH, NULL, 6);
	
	  	  	$form->addPassword('password2', 'Confirm Password')
	            ->addRule($form::FILLED, 'Confirm user password!')
	            ->addRule($form::EQUAL, 'No match for passwords!', $form['password']);
        } elseif ($this->getAction() === 'edit') {
         	$form->addPassword('password', 'Password')
				->setOption('description', 'Fill in only if you want to change current password')
		    	->addCondition($form::FILLED)
			    	->addRule($form::MIN_LENGTH, NULL, 6);

			$form['password']->getControlPrototype()->autocomplete('off');

		    $form->addPassword('password2', 'Confirm Password')
		    	->setOption('description', 'Fill in only if you want to change current password')
					->addConditionOn($form['password'], $form::FILLED)
						->addRule($form::FILLED, 'Confirm your password!')
			            ->addRule($form::EQUAL, 'No match for passwords!', $form['password']);
        }
	
	    $form->addText('email', 'E-Mail')
	            ->setEmptyValue('@')
	            ->addRule($form::FILLED)
	            ->addRule($form::EMAIL)
	            ->addRule($form::MAX_LENGTH, NULL, 60);
	
//	    $form->addSelect('user_levels_id', 'Role', $this->prepareRoles())->skipFirst()
	    $form->addMultiSelect('roles', 'Role', $this->prepareRoles(), 6)->skipFirst()
	            ->addRule($form::FILLED);

		$form->addSubmit('save', 'Add');
		$form->addSubmit('cancel', 'Cancel')->setValidationScope(NULL);
		$form->onSubmit[] = callback($this, 'itemFormSubmitted');
		
		return $form;
	}

	
	public function itemFormSubmitted(MyAppForm $form)
	{
		try {
			if ($form['save']->isSubmittedBy()) {
				$id = (int) $this->getParam('id');
				$values = $form->getValues();
				unset($values['password2']);
				
				if ($id > 0) {
					$this->model->update($id, $values);
					$this->flashMessage('User updated.', self::FLASH_MESSAGE_SUCCESS);
				} else {
					$values['approved'] = true;
					$id = $this->model->insert($values);
					
					$this->flashMessage('User created.', self::FLASH_MESSAGE_SUCCESS);
	      			$this->sendRegBasicEmail($values);
				}
			}
	 	} catch (InvalidStateException $e) {
			$form->addError($this->translate('Mail could not be sent. Try again later, please'));
		} catch (DibiDriverException $e) {
			// duplicate entry
			if ($e->getCode() === 1062) {
				$this->flashMessage("ERROR: " . $e->getMessage(), self::FLASH_MESSAGE_ERROR);
			} else {
				throw $e;
				$this->flashMessage("ERROR: cannot save data!", self::FLASH_MESSAGE_ERROR);
			}
		}

		$form->resetValues();
//		$this->refresh('usersList', 'default');
		$this->refresh(null, 'default');
//		$this->redirect('default');
	}

	
	  	
  	protected function sendRegBasicEmail($values)
  	{
	    $template = new Template(APP_DIR . "/templates/mails/basicRegMail.phtml");
	    $template->registerFilter(new LatteFilter);
		$template->setTranslator($this->getTranslator());
	
	    $template->homepageLink = $this->link("//:Front:Files:list");
	    $template->login = $values['username'];
	    $template->password = $values['password'];
	    $template->title = $this->translate('Registration');

	    $mail = new Mail();
	    $mail->addTo($values['email']);
	    $mail->setFrom(Environment::getConfig("contact")->registrationEmail);
	    $mail->setSubject($template->title);
	    $mail->setHTMLbody($template);
	    $mail->send();

	    $this->flashMessage('E-mail has been sent to provided e-mail address.', self::FLASH_MESSAGE_SUCCESS);
  	}
}
