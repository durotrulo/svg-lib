<?php

abstract class Front_InternalPresenter extends Front_BasePresenter
{
	const COOKIE_THUMBSIZE = 'thumbSize';

	/** @persistent */
	public $filter = null;
	
	/** @persistent */
	public $orderby = null;
//	public $orderby = FilesModel::ORDER_BY_NAME;
	
	/** @persistent */
	public $sorting = dibi::DESC;
	
//	/** @persistent @var int */
//	public $itemsPerPage = 16;
	
	/** 
	 * @var string search query 
	 * @persistent 
	 */
	public $q;

	/** @var array */
	private $_allowedUserRoles = array(
		UsersModel::UL_ADMIN,
		UsersModel::UL_SUPERADMIN,
		UsersModel::UL_DESIGNER,
		UsersModel::UL_PROJECT_MANAGER,
	);
	
	/** DibiRow array of [files|projects|lightboxes] */
	protected $items;
	
	protected $defaults = array(
		'orderby' => FilesModel::ORDER_BY_NAME,
	);
	
	/** @var ProjectsModel */
	private $projectsModel;
	
	/** @var ComplexityModel */
	private $complexityModel;
	
	/** @var FilesModel */
	private $filesModel;
	
	/** @var ClientPackagesModel */
	private $clientPackagesModel;
	
	/** @var LightboxesModel */
	private $lightboxModel;
	
	/** @var TagsModel */
	private $tagsModel;
	
	/**
	 * @return ProjectsModel
	 */
	public function getProjectsModel()
	{
		if (is_null($this->projectsModel)) {
			$this->projectsModel = new ProjectsModel();
		}
		
		return $this->projectsModel;
	}
	
	
	/**
	 * @return FilesModel
	 */
	public function getFilesModel()
	{
		if (is_null($this->filesModel)) {
			$this->filesModel = new FilesModel();
		}
		
		return $this->filesModel;
	}
	
	
	/**
	 * @return ClientPackagesModel
	 */
	public function getClientPackagesModel()
	{
		if (is_null($this->clientPackagesModel)) {
			$this->clientPackagesModel = new ClientPackagesModel();
		}
		
		return $this->clientPackagesModel;
	}
	
	
	/**
	 * @return ComplexityModel
	 */
	public function getComplexityModel()
	{
		if (is_null($this->complexityModel)) {
			$this->complexityModel = new ComplexityModel();
		}
		
		return $this->complexityModel;
	}
	
	
	/**
	 * @return LightboxesModel
	 */
	public function getLightboxModel()
	{
		if (is_null($this->lightboxModel)) {
			$this->lightboxModel = new LightboxesModel();
		}
		
		return $this->lightboxModel;
	}
	
	
	/**
	 * @return TagsModel
	 */
	public function getTagsModel()
	{
		if (is_null($this->tagsModel)) {
			$this->tagsModel = new TagsModel();
		}
		
		return $this->tagsModel;
	}
	
	
	protected function startup()
	{
		// checks user is logged in
		parent::startup();
		
		// user must be one of allowed roles
		if (count(array_intersect($this->user->getRoles(), $this->_allowedUserRoles)) === 0) {
			$this->flashMessage('You are not allowed to enter this section', self::FLASH_MESSAGE_ERROR);
			// todo: ked klikne napr. client na link, do kt. nema pristup, tak vznikne slucka pri presmerovani - lebo je sice prihlaseny, ale nie v potrebnej roli -> treba to tu oifovat, kam podla role presmerovat
			$this->redirect(':Front:Login:login');
//			$this->redirect(':Front:Login:login', $this->getApplication()->storeRequest());
		}
				
		if (!is_null($this->filter) and !in_array($this->filter, $this->_allowedFilters)) {
			throw new InvalidStateException('Parameter filter must be one of ' . join(',', $this->_allowedFilters) . ".'$this->filter' given.");
		}
		
//		if (!is_null($this->orderby) and !in_array($this->orderby, $this->_allowedOrderby)) {
		if (!in_array($this->orderby, $this->_allowedOrderby, true)) {
			if (empty($this->orderby)) {
				$this->orderby = $this->defaults['orderby'];
			} else {
				throw new InvalidStateException('Parameter orderby must be one of ' . join(',', $this->_allowedOrderby) . ".'$this->orderby' given.");
			}
		}
		
		BaseModel::validateSorting($this->sorting, dibi::DESC);
		
		// set itemsPerPage by paginator to have correct links
//		if (!empty($this['itemPaginator']->itemsPerPage)) {
//			$this->itemsPerPage = $this['itemPaginator']->itemsPerPage;
//		}
		
		MyTagInput::register();
		
		$this->setLastRequest();
		

		$this->getFilesModel();
		$this->getProjectsModel();
		$this->getComplexityModel();
		$this->getTagsModel();

	}
	
	
	
	public function actionTagInputSuggestTags($tagFilter)
	{
		$form = $this->getComponent('fileUploadForm');
		$form['tags']->renderResponse($this, $tagFilter);
	}
	
	protected function createComponentFileUploadForm($name)
	{
		$form = new MyAppForm($this, $name);
//		$form->enableAjax();
		$form->setErrorsAsFlashMessages();
		
		if (!is_null($this->getTranslator())) {
			$form->setTranslator($this->getTranslator());
		}
		
		$form->addFile('file', 'Image')
			->addRule(MyAppForm::FILLED)
			->addRule(MyAppForm::SUFFIX, 'Not supported file type', join(',', $this->filesModel->allowedSuffix))
			->getControlPrototype()
			->multiple(true);
		
		$form->addSelect('projects_id', 'Project Name', BaseModel::prepareSelect($this->projectsModel->fetchPairs(), 'Choose project', true))
	    		->skipFirst()
//		$form->addSelect('projects_id', 'Project Name', ($this->projectsModel->fetchPairs()))
	            ->addRule(Form::FILLED)
	            ->getControlPrototype()
	            	->class('project-select');
	            
	    $complexity = BaseModel::prepareSelectTree($this->complexityModel->getTree(), 1, 'Choose complexity', true);
		$form->addSelect('complexity_id', 'Complexity', $complexity)
	    		->skipFirst()
	            ->addRule(Form::FILLED)
	            ->getControlPrototype()
	            	->class('complexity-select');
//			->setDefaultValue($this->complexity);

		$form->addTag('tags', 'Tags', $this->tagsModel->fetchPairs())
		 	->addRule(Form::FILLED, 'Enter Tags!')
		 	->addRule(MyTagInput::UNIQUE, 'Tags must be unique!')
			->getControlPrototype()
            	->class('tags-input');
			
        $form->addCheckbox('is_top_file', 'Top level file')
        	->getControlPrototype()
            	->class('top-file-select');
        
		$form->addSubmit('save', 'Upload');
		$presenter = $this;
		$form->onSubmit[] = function(MyAppForm $form) use ($presenter) {
			try {
				if ($form['save']->isSubmittedBy()) {
					$values = $form->getValues();
//					dump($values);die();
					
					
					$tags = $values['tags'];
					unset($values['tags']);

					// insert new tags to DB and $tags
					$newTags = $form['tags']->getNewTags();
					if ($newTags) {
						foreach ($newTags as $k => $tag) {
							$insertId = $presenter->tagsModel->insert(array('name' => $tag));
							$tags[$insertId] = $tag;
							unset($tags[$k]); // zrus docasny index noveho tagu
						}
					}
					
					// upload file
					$values['uploaded'] = dibi::datetime();
					$id = $presenter->filesModel->insert($values);
					
					// attach tags
					$presenter->filesModel->bindTags($id, array_keys($tags));
					
					$presenter->flashMessage('File uploaded.', $presenter::FLASH_MESSAGE_SUCCESS);
				}
			} catch (DibiDriverException $e) {
				// duplicate entry
				if ($e->getCode() === 1062) {
					$presenter->flashMessage("ERROR: " . $e->getMessage(), $presenter::FLASH_MESSAGE_ERROR);
				} else {
					throw $e; //todo:remove
					$presenter->flashMessage("ERROR: cannot save data!", $presenter::FLASH_MESSAGE_ERROR);
				}
				// keep prefilled data, do not refresh page
				return false;
			}
	
			$form->resetValues();
			$presenter->refresh(null, 'this');
		};

		return $form;
	}


	/**
	 * factory for bind tag 2 file form
	 */
	protected function createComponentBindTagForm($name)
	{
		return $this->getBindTagFormComponent($this, $name);
	}

	
	/**
	 * factory for bind tag 2 file form [common for FilesControl and presenter]
	 * called by createComponentBindTagForm(name)
	 */
	public function getBindTagFormComponent($parent, $name)
	{
		$form = new MyAppForm($parent, $name);
		$form->enableAjax();
		$form->setErrorsAsFlashMessages();
		$form->setCustomRenderer($form::RENDER_MODE_INLINE_BLOCK);
		$form->getElementPrototype()
			->data('nette-spinner', '#tagSpinner')
			->class[] = 'bindTagForm';
		
		if (!is_null($this->presenter->getTranslator())) {
			$form->setTranslator($this->presenter->getTranslator());
		}
		
		$form->addTag('tags', 'Tags', $this->getTagsModel()->fetchPairs())
		 	->addRule(Form::FILLED, 'Enter Tags!')
		 	->addRule(MyTagInput::UNIQUE, 'Tags must be unique!')
			->getControlPrototype()
            	->class('tags-input');
			
        $form->addHidden('fileId');
		$form->addSubmit('save', 'Add');
		$_this = $this;
		$form->onSubmit[] = function(MyAppForm $form) use ($_this) {
			try {
				if ($form['save']->isSubmittedBy()) {
					$values = $form->getValues();

					$fileId = $values['fileId'];
					if ($_this->user->isAllowed(new FileResource($fileId), Acl::PRIVILEGE_BIND_TAG)) {
					
						$tags = $values['tags'];
						unset($values['tags']);
	
						// insert new tags to DB and $tags
						$newTags = $form['tags']->getNewTags();
						if ($newTags) {
							foreach ($newTags as $k => $tag) {
								$insertId = $_this->tagsModel->insert(array('name' => $tag));
								$tags[$insertId] = $tag;
								unset($tags[$k]); // unset temporary index of new tag
							}
						}
						
						// attach tags
						$_this->filesModel->bindTags($fileId, array_keys($tags));
						
						$_this->flashMessage('Tag added', $_this::FLASH_MESSAGE_SUCCESS);
	
						// format inserted tags for jQuery processing
						$insertedTags = array();
						$usersModel = new UsersModel();
						foreach ($tags as $k => $tag) {
							$insertedTags[] = array(
								'id' => $k,
								'name' => $tag, 
								'userLevel' => $usersModel->getRolesForTag(),
							);
						}
						$_this->presenter->payload->actions[] = 'addTag';
						$_this->presenter->payload->tags = $insertedTags;
						$_this->presenter->payload->fileId = $fileId;

					} else {
						$_this->flashMessage(NOT_ALLOWED, $_this::FLASH_MESSAGE_ERROR);
					}
				}
			} catch (DibiDriverException $e) {
				// duplicate entry
				if ($e->getCode() === 1062) {
					$_this->flashMessage("ERROR: " . $e->getMessage(), $_this::FLASH_MESSAGE_ERROR);
				} else {
					$_this->flashMessage("ERROR: cannot save data!", $_this::FLASH_MESSAGE_ERROR);
				}
				// keep prefilled data, do not refresh page
				return false;
			}
		
			$form->resetValues();
			$_this->refresh('flashes'); // do not redraw snippets, just for redirection in non-js
//			$_this->refresh(array('flashes', 'bindTagForm')); // do not redraw snippets, just for redirection in non-js
	
//			$_this->refresh(null, 'this');
		};

		return $form;
	}
	
	
	protected function createComponentSearchForm($name)
	{
		$form = new MyAppForm($this, $name);
		$form->enableAjax();
			
		$form->addText('q')
			->setDefaultValue($this->q)
			->getControlPrototype()->setPlaceholder('Search');
			
			
		$form->addSubmit('search', 'Search')
			->getControlPrototype()->class('noJS invisible');

		$_this = $this;
		$form->onSubmit[] = function(MyAppForm $form) use (&$_this) {
			if ($form['search']->isSubmittedBy()) {
				$values = $form->getValues();
				$_this->q = $values['q'];
			}

			$_this->refresh(array('searchForm', 'itemList', 'options'), 'this');
//			$_this->refresh(null, 'this');
		};

		return $form;
	}
	
	
	public function handleClearSearchForm()
	{
		$this->q = null;
		$this->refresh(array('searchForm', 'itemList', 'options'), 'this');
//			$this->refresh(null, 'this');
//		$form->resetValues();
	}

	
	public function handleReloadItemList()
	{
		$this->refresh('itemList', 'this');
//		$this->refresh('content', 'this');
	}
	
	
	public function handleUnbindTag($fileId, $tagId)
	{
		if ($this->user->isAllowed(new FileResource($fileId), Acl::PRIVILEGE_UNBIND_TAG)) {
			try {
				$this->getFilesModel()->unbindTag($fileId, $tagId);
				$this->payload->tagId = $tagId;
		 		$this->payload->actions[] = 'unbindTag';
			} catch (DibiDriverException $e) {
				$this->flashMessage(OPERATION_FAILED, self::FLASH_MESSAGE_ERROR);
			}
		} else {
			$this->flashMessage('You are not allowed to delete this tag!', self::FLASH_MESSAGE_ERROR);
//			echo NOT_ALLOWED;
		}
		$this->refresh('none'); // do not redraw snippets, just for redirection in non-js
//		$this->terminate();
	}
	
	
	/**
	 * edit lightbox name using jEditable on frontend
	 * prints updated name and exits
	 *
	 * @param int lightbox id
	 * @param string new lightbox's name
	 * @return void
	 */
	public function handleEditFileDesc($fileId, $desc)
	{
		if ($this->user->isAllowed(new FileResource($fileId), Acl::PRIVILEGE_EDIT_DESCRIPTION)) {
			try {
				$this->filesModel->update($fileId, array(
					'description' => $desc,
				));
				echo nl2br($desc);
			} catch (DibiDriverException $e) {
				throw $e;
				echo OPERATION_FAILED;
			}
		} else {
			echo NOT_ALLOWED;
		}
		$this->terminate();
	}
	
	
	/**
	 * load tags bound to file ($fileId) and send to client
	 *
	 * @param int
	 */
	public function handleGetTags($fileId)
	{
		$this->payload->tags = $this->filesModel->getTags($fileId);
		$this->terminate();
	}
	

	/**
	 * download file
	 *
	 * @param int
	 * @param bool download bitmap alternative to svg files?
	 */
	public function handleDownloadFile($fileId, $useBitmap = false)
	{
		$this->filesModel->download($id, $useBitmap);
	}
	
	
	
								/**
								 * PROJECTS + PACKAGES COMMON METHODS
								 */
	
	/**
	 * save order after sorting topfiles
	 */
	public function handleSortTopFiles($topfile)
	{
		$sortedItems = $topfile;
		$sortableModel = new SortableModel(BaseModel::FILES_TABLE, 'id', 'top_file_order');
		$sortableModel->saveOrder($sortedItems);
		$this->flashMessage('Items order saved', self::FLASH_MESSAGE_SUCCESS);
		$this->refresh('none');
	}
	
	
	/**
	 * delete file permanently from system (for topfiles - non-topfiles handled in FilesControl)
	 *
	 * @param int
	 */
	public function handleDeleteFile($fileId)
	{
		$this->filesModel->delete($fileId);
		$this->flashMessage('File deleted', self::FLASH_MESSAGE_SUCCESS);
		$this->refresh('itemList', 'this');
	}
}