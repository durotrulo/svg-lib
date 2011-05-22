<?php

abstract class Front_InternalPresenter extends Front_BasePresenter
{
	const COOKIE_THUMBSIZE = 'thumbSize';

	/** @persistent */
	public $filter = null;
	
	/** @persistent */
	public $orderby = FilesModel::ORDER_BY_NAME;
	
	/** @persistent */
	public $sorting = dibi::DESC;
	
//	/** @persistent @var int */
//	public $itemsPerPage = 16;
	
	/** @persistent @var string search query */
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
	
	/** @var ProjectsModel */
	private $projectsModel;
	
	/** @var ComplexityModel */
	private $complexityModel;
	
	/** @var FilesModel */
	private $filesModel;
	
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
		if (!in_array($this->userIdentity->role, $this->_allowedUserRoles)) {
			$this->flashMessage('You are not allowed to enter this section', self::FLASH_MESSAGE_ERROR);
			// todo: ked klikne napr. client na link, do kt. nema pristup, tak vznikne slucka pri presmerovani - lebo je sice prihlaseny, ale nie v potrebnej roli -> treba to tu oifovat, kam podla role presmerovat
			$this->redirect(':Front:Login:login');
//			$this->redirect(':Front:Login:login', $this->getApplication()->storeRequest());
		}
				
		if (!is_null($this->filter) and !in_array($this->filter, $this->_allowedFilters)) {
			throw new InvalidStateException('Parameter filter must be one of ' . join(',', $this->_allowedFilters) . ".'$this->filter' given.");
		}
		
		if (!is_null($this->orderby) and !in_array($this->orderby, $this->_allowedOrderby)) {
			throw new InvalidStateException('Parameter orderby must be one of ' . join(',', $this->_allowedOrderby) . ".'$this->orderby' given.");
		}
		
		BaseModel::validateSorting($this->sorting, dibi::DESC);
		
		// set itemsPerPage by paginator to have correct links
//		if (!empty($this['itemPaginator']->itemsPerPage)) {
//			$this->itemsPerPage = $this['itemPaginator']->itemsPerPage;
//		}
		
		MyTagInput::register();
	}
	
	
	protected function createComponentItemPaginator($name)
	{
		return new VisualPaginator($this, $name);
	}
	
	
	protected function createComponentInternalNavigation($name)
	{
		$nav = new NavigationControl($this, $name);
//		$nav->setupHomepage("Úvod", $this->link("Homepage:"));
		
			/*
			$args = !is_null($item->nette_link_args) ? Basic::string2array(str_replace('%id%', $item->id, $item->nette_link_args)) : array();
			
			switch ($item->id) {
				case 3:
					$currentLink = ':News:Front:Default:*';
					break;
					
				case 5:
					$currentLink = ':Publications:Front:Default:*';
					break;
			
				default:
					$currentLink = null;
					break;
			}
			*/
			$nav->add('Project', $this->link(':Front:Projects:list'));
			$nav->add('Vector', $this->link(':Front:Files:list', array('type' => 'vector')));
			$nav->add('Bitmap', $this->link(':Front:Files:list', array('type' => 'bitmap')));
			$nav->add('Inspiration', $this->link(':Front:Projects:list'));
			$nav->add('All Files', $this->link(':Front:Files:list', array('type' => null)));
			$nav->add('Lightboxes', $this->link(':Front:Projects:list'));
	}
	
	
	
	public function actionTagInputSuggestTags($tagFilter)
	{
		$form = $this->getComponent('fileUploadForm');
		$form['tags']->renderResponse($this, $tagFilter);
	}
	
	protected function createComponentFileUploadForm($name)
	{
		//todo: mozno odstranit (presunut init do startup?) - zatial iba tu treba projectsModel aj complexityModel - pri volaniach zvonka sa pouzije getter a vsetko je OK
		$this->getFilesModel();
		$this->getProjectsModel();
		$this->getComplexityModel();
		
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
		
//		$form->addSelect('projects_id', 'Project Name', BaseModel::prepareSelect($this->projectsModel->fetchPairs()))
//	    		->skipFirst()
		$form->addSelect('projects_id', 'Project Name', ($this->projectsModel->fetchPairs()))
	            ->addRule(Form::FILLED)
	            ->getControlPrototype()
	            	->class('project-select');
	            
	    $complexity = BaseModel::prepareSelectTree($this->complexityModel->getTree(), 1);
		$form->addSelect('complexity_id', 'Complexity', $complexity)
	    		->skipFirst()
	            ->addRule(Form::FILLED)
	            ->getControlPrototype()
	            	->class('complexity-select');
//			->setDefaultValue($this->complexity);

		$form->addTag('tags', 'Tags', $this->getTagsModel()->fetchPairs())
		 	->addRule(Form::FILLED, 'Enter Tags!')
		 	->addRule(MyTagInput::UNIQUE, 'Tags must be unique!')
			->getControlPrototype()
            	->class('tags-input');
			
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

	
	protected function createComponentSearchForm($name)
	{
		$form = new MyAppForm($this, $name);
		$form->enableAjax();
			
		$form->addText('q', 'Search')
			->setDefaultValue($this->q);
			
		$form->addSubmit('search', 'Search')
			->getControlPrototype()->class('noJS noJS-tr');

		$presenter = $this;
		$form->onSubmit[] = function(MyAppForm $form) use (&$presenter) {
			if ($form['search']->isSubmittedBy()) {
				$values = $form->getValues();
				$presenter->q = $values['q'];
			}

			$presenter->refresh(array('searchForm', 'itemList', 'options'), 'this');
//			$presenter->refresh(null, 'this');
		};

		return $form;
	}
	
	
	public function handleClearSearchForm()
	{
		$this->q = null;
		if ($this->isAjax()) {
			$this->actionList();
		}
		$this->refresh(array('searchForm', 'itemList', 'options'), 'this');
//			$this->refresh(null, 'this');
//		$form->resetValues();
	}

	
	public function handleReloadItemList()
	{
		$this->refresh('itemList', 'this');
	}
	
}