<?php

class Users_Admin_DefaultPresenter extends Admin_BasePresenter
{

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
				$this->template->users = $this->model->findAll(true, $this->userIdentity->user_levels_id);
//			}
		}
	}
	
	
	
  	private function prepareRoles()
  	{
  		return BaseModel::prepareSelect($this->model->findRoles($this->userIdentity->user_levels_id), 'User Role');
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
			
			if ($row->user_levels_id >= $this->userIdentity->user_levels_id) {
				throw new OperationNotAllowedException('You don\'t have rights to edit this user. Contact superadmin for granting higher permissions.');
			}
			
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
            ->addRule(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, 2)
            ->addRule(Form::MAX_LENGTH, 70);

        $form->addText('lastname', 'Last Name')
            ->addRule(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, 2)
            ->addRule(Form::MAX_LENGTH, 70);

        $form->addText('username', 'User Name')
            ->addRule(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, 3)
            ->addRule(Form::MAX_LENGTH, 30);
	   
            
        if ($this->getAction() === 'add') {
        	$form->addPassword('password', 'Password')
	            ->addRule(Form::FILLED)
	            ->addRule(Form::MIN_LENGTH, 6);
	
	  	  	$form->addPassword('password2', 'Confirm Password')
	            ->addRule(Form::FILLED, 'Confirm user password!')
	            ->addRule(Form::EQUAL, 'No match for passwords!', $form['password']);
        } elseif ($this->getAction() === 'edit') {
         	$form->addPassword('password', 'Password')
				->setOption('description', 'Fill in only if you want to change current password')
		    	->addCondition(Form::FILLED)
			    	->addRule(Form::MIN_LENGTH, 6);

			$form['password']->getControlPrototype()->autocomplete('off');

		    $form->addPassword('password2', 'Confirm Password')
		    	->setOption('description', 'Fill in only if you want to change current password')
					->addConditionOn($form['password'], Form::FILLED)
						->addRule(Form::FILLED, 'Confirm your password!')
			            ->addRule(Form::EQUAL, 'No match for passwords!', $form['password']);
        }
	
	    $form->addText('email', 'E-Mail')
	            ->setEmptyValue('@')
	            ->addRule(Form::FILLED)
	            ->addRule(Form::EMAIL)
	            ->addRule(Form::MAX_LENGTH, 60);
	
	    $form->addSelect('user_levels_id', 'Role', $this->prepareRoles())->skipFirst()
	            ->addRule(Form::FILLED);

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
