<?php

class Projects_Admin_DefaultPresenter extends Admin_BasePresenter
{

	private $projectId;
	
	
	protected function startup()
	{
		parent::startup();
		$this->model = new ProjectsModel();
//		$this->config = Environment::getConfig('users');
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->title = $this->translate('Projects'); // optional, shown as heading and title of html page
		$this->template->description = $this->translate('Little piece of description for module Projects'); // optional, describes functionality of module
		
		if ($this->getAction() === 'add' or 'edit') {
			$form = $this['itemForm'];
//			if ($form->isSubmitted()) {
				$this->template->projects = $this->model->findAll();
//			}
		}
	}
	
	
  	private function getManagersSelect()
  	{
  		return BaseModel::prepareSelect(UsersModel::findByRole(UsersModel::UL_PROJECT_MANAGER_ID), 'Manager');
  	}
  	
  	
  	private function getRelatedProjectsSelect()
  	{
  		// remove edited project
  		$projects = array_diff_key($this->model->fetchPairs(), array($this->projectId => 0));
  		
  		return $projects;
  		return BaseModel::prepareSelect($projects);
  	}

	
	/********************* view default *********************/
	
	
//	public function renderDefault()
//	{
//		$this->template->items = $this->model->findAll();
//	}
	

	/********************* views add & edit *********************/


//	public function renderEdit($id = 0)
	public function actionEdit($id = 0)
	{
		$this->projectId = $id;
		$form = $this['itemForm'];
		$form['save']->caption = 'Edit';
		if (!$form->isSubmitted()) {
			$row = $this->model->find($id);
			if (!$row) {
				throw new BadRequestException(RECORD_NOT_FOUND);
			}
			$row['related_projects'] = $this->model->getRelatedProjects($id)->fetchPairs();
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
		$form->enableAjaxFileUpload();
		
		$form->addClass('fileUploadForm');

		if (!is_null($this->getTranslator())) {
			$form->setTranslator($this->getTranslator());
		}
		
		$form->addText('name', 'Project Name')
            ->addRule(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, 2)
            ->addRule(Form::MAX_LENGTH, 70);

        $form->addText('subtitle', 'Subtitle')
            ->addCondition(Form::FILLED)
	            ->addRule(Form::MIN_LENGTH, 'tmpzadaj %d', 2)
	            ->addRule(Form::MAX_LENGTH, 'tmpzadaj %d', 70);

	    $form->addRadioList('type', 'Type', array(
		    	'client' => 'client', 
		    	'internal' =>'internal',
	    	))
	    	->addRule(Form::FILLED);
	    	
		$form->addDatePicker('completed', 'Completed');
//		    ->addCondition(Form::FILLED)
//			    ->addRule(Form::VALID, 'Entered date is not valid!');
//
	
	    $form->addSelect('manager_id', 'Manager', $this->getManagersSelect())
	    		->skipFirst()
	            ->addRule(Form::FILLED);

	    $form->addMultiSelect('related_projects', 'Related Projects', $this->getRelatedProjectsSelect(), 8);

	    //	photo is optional when editing
		if ($this->getParam('action') == 'edit') {
			$form->addFile('main_img', 'Photo')
				->setOption('description', 'Choose photo only if you want to change current one')
				->addCondition(Form::FILLED)
					->addRule(MyAppForm::SUFFIX, 'File attachment must be image (jpg, gif, png)', 'jpg, gif, png')
					->addRule(Form::MAX_FILE_SIZE, 
						'File attachment exceeds maximum file size ' . Environment::getConfig("upload")->max_file_size . 'B',
						Environment::getConfig("upload")->max_file_size);
		} else {
			$form->addFile('main_img', 'Photo')
				->addRule(Form::FILLED, 'Choose %label!')
				->addRule(MyAppForm::SUFFIX, 'File attachment must be image (jpg, gif, png)', 'jpg, gif, png')
				->addRule(Form::MAX_FILE_SIZE, 
					'File attachment exceeds maximum file size ' . Environment::getConfig("upload")->max_file_size . 'B',
					Environment::getConfig("upload")->max_file_size);
		}
	    
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

				if ($id > 0) {
					$this->model->update($id, $values);
					$this->flashMessage('Project updated.', self::FLASH_MESSAGE_SUCCESS);
				} else {
					$values['created'] = dibi::datetime();
					$id = $this->model->insert($values);
					$this->flashMessage('Project created.', self::FLASH_MESSAGE_SUCCESS);
				}
			}
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
	
	
	public function handleDelete($id)
	{
		$this->model->delete($id);
		$this->refresh('projectsList', 'add');
	}
	
}
