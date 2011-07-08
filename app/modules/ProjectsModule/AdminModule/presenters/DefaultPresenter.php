<?php

class Projects_Admin_DefaultPresenter extends ProjectsUsers_Admin_BasePresenter
{

	/** @var int */
	private $projectId;
	
	
	protected function startup()
	{
		parent::startup();
		$this->model = new ProjectsModel();
		$this->config = Environment::getConfig('projects');
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->title = $this->translate('Projects Admin'); // optional, shown as heading and title of html page
		$this->template->description = $this->translate('Little piece of description for module Projects'); // optional, describes functionality of module
		$this->template->topHeading = ucfirst($this->getAction()) . ' Project';

		if ($this->getAction() === 'add' or $this->getAction() === 'edit') {
			$items = $this->model->findAll();
			$this->model->filterByNameOrSubtitle($items, $this->q);
			
			$vp = $this['itemPaginator'];
			$vp->selectItemsPerPage = array(8, 16, 24, 32, 40,);
			$vp->itemsPerPageAsSelect = true;
//				$vp->isResultsCountChangable = false;
	 		$vp->setDefaultItemsPerPage($this->config->defaultItemsPerPage);
	        $vp->paginator->itemCount = $items->count();
	        $vp->itemString = 'per page';
			$this->template->projects = $items
									->toDataSource()
									->applyLimit($vp->paginator->itemsPerPage, $vp->paginator->offset)
									->fetchAll();
											
			// when paging refresh only items
			if ($vp->paginated || $vp->itemsPerPageChanged) {
				$this->invalidateControl('itemList');
			}
			
			$this->invalidateControl('topHeading');
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
  	}

  	
	/********************* views add & edit *********************/


	public function actionEdit($id)
	{
		$this->projectId = $id;
		$form = $this['itemForm'];
		$form['save']->caption = 'Edit Project';
		if (!$form->isSubmitted()) {
			$row = $this->model->find($id);
			if (!$row) {
				throw new BadRequestException(RECORD_NOT_FOUND);
			}
			$row['related_projects'] = $this->model->getRelatedProjects($id);
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
		
		$form->getRenderer()->wrappers['label']['requiredsuffix'] = " *";

		$form->addText('name', 'Project Name')
            ->addRule(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, NULL, 2)
            ->addRule(Form::MAX_LENGTH, NULL, 70)
			->getControlPrototype()
				->data('nette-check-url', $this->link('checkAvailability!', array('__NAME__', 'project')))
				->class[] = 'checkAvailability';
				
        $form->addText('subtitle', 'Subtitle')
            ->addCondition(Form::FILLED)
	            ->addRule(Form::MIN_LENGTH, NULL, 2)
	            ->addRule(Form::MAX_LENGTH, NULL, 70);

		$form->addDatePicker('completed', 'Completed')
			->getControlPrototype()->autocomplete('off');
//		    ->addCondition(Form::FILLED)
//			    ->addRule(Form::VALID, 'Entered date is not valid!');
//
	
		/*
	    $form->addSelect('manager_id', 'Manager', $this->getManagersSelect())
	    		->skipFirst()
	            ->addRule(Form::FILLED);
		*/
		
	    $form->addMultiSelect('related_projects', 'Related Projects', $this->getRelatedProjectsSelect(), 8);

	    //	photo is optional when editing
		if ($this->getParam('action') == 'edit') {
			$form->addFile('main_img', 'Thumbnail')
				->setOption('description', 'Choose photo only if you want to change current one')
				->addCondition(Form::FILLED)
					->addRule(MyAppForm::SUFFIX, 'File attachment must be image (jpg, gif, png)', 'jpg, gif, png')
					->addRule(Form::MAX_FILE_SIZE, 
						'File attachment exceeds maximum file size ' . Environment::getConfig("upload")->max_file_size . 'B',
						Environment::getConfig("upload")->max_file_size);
		} else {
			$form->addFile('main_img', 'Thumbnail')
				->addRule(Form::FILLED, 'Choose %label!')
				->addRule(MyAppForm::SUFFIX, 'File attachment must be image (jpg, gif, png)', 'jpg, gif, png')
				->addRule(Form::MAX_FILE_SIZE, 
					'File attachment exceeds maximum file size ' . Environment::getConfig("upload")->max_file_size . 'B',
					Environment::getConfig("upload")->max_file_size);
		}
	    
		$form->addSubmit('save', 'Add Project')
			->getControlPrototype()->class[] = 'ok-button';
		$form->addSubmit('cancel', 'Cancel')
			->setValidationScope(NULL)
			->getControlPrototype()->class[] = 'cancel-button';
		$_this = $this;
		$form['cancel']
			->onClick[] = function() use($_this) {
				$_this->refresh(null, 'add', array(), true);
			};
			
		$form->onSubmit[] = callback($this, 'itemFormSubmitted');
		
		return $form;
	}

	
	public function itemFormSubmitted(MyAppForm $form)
	{
		try {
			if ($form['save']->isSubmittedBy()) {
				$values = $form->getValues();

				// insert
				if (is_null($this->getParam('id'))) {
					$values['created'] = dibi::datetime();
					$id = $this->model->insert($values);
					$this->flashMessage('Project created.', self::FLASH_MESSAGE_SUCCESS);
				// update
				} else {
					// 0 = GENERAL
					$id = intval($this->getParam('id'));
					$this->model->update($id, $values);
					$this->flashMessage('Project updated.', self::FLASH_MESSAGE_SUCCESS);
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
		$this->refresh(null, 'add');
	}
	
	
	public function handleDelete($id)
	{
		if ($id === ProjectsModel::GENERAL_PROJECT_ID) {
			$this->flashMessage('Project GENERAL can not be deleted', self::FLASH_MESSAGE_WARNING);
		} else {
			if ($this->user->isAllowed(Acl::RESOURCE_PROJECT, Acl::PRIVILEGE_DELETE)) {
				$this->model->delete($id);
				$this->flashMessage('Project deleted', self::FLASH_MESSAGE_SUCCESS);
			} else {
				$this->flashMessage(NOT_ALLOWED, self::FLASH_MESSAGE_ERROR);
			}
		}

		$this->refresh('itemList', 'add');
	}
	
}
