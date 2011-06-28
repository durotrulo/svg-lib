<?php

class Front_LightboxesPresenter extends Front_InternalPresenter
{
	
	/** 
	 * @var int
	 * @persistent 
	 */
	public $ownerId;
	
	/**
	 * @var string comma separated user ids (owners of lbs)
	 * @persistent
	 */
    public $ownerIds = '';
    
    /** @var array $this->ownerIds cast to array */
    public $ownerIds_a;
	
    protected $_allowedFilters = array(
		FilesModel::FILTER_BY_VECTOR, 
		FilesModel::FILTER_BY_BITMAP, 
		FilesModel::FILTER_BY_INSPIRATION
	);
	
	protected $_allowedOrderby = array(
		FilesModel::ORDER_BY_NAME,
		FilesModel::ORDER_BY_DATE,
		FilesModel::ORDER_BY_SIZE,
	);
	
	
	/**
	 * initialize $this->ownerIds_a based on $this->ownerIds
	 * called from startup()
	 */
    private function initOwnerIdsA()
	{
		//	aby mi to nevracalo pole s prazdnym retazcom
		if (!empty($this->ownerIds)) {
			$this->ownerIds_a = explode('-', $this->ownerIds);
		} else {
			$this->ownerIds_a = array();
		}
	}
	
	
	
    /**
     * nastavi id tagov, podla kt. sa sortuje
     *
     */
	private function processOwnerId()
	{
		if (!empty($this->ownerId)) {
			//	pridam tag, iba ak sa tam este nenachadza taky tag
//			if (!in_array($this->ownerId, $this->ownerIds_a) && $this->ownerId !== 0) {
			if (!in_array($this->ownerId, $this->ownerIds_a)) {
				$this->ownerIds_a[] = $this->ownerId;
			//	inak ho vyhodim .. testovacia tmp?
			} else {
				MyArrayTools::unsetByValue($this->ownerIds_a, $this->ownerId);
			}

			// vynulujem tag, aby som sa vyhol smycke, kedze zmena persistentneho parametra sposobuje redirect/reload
//			$this->ownerId = 0;
			$this->ownerIds = join('-', $this->ownerIds_a);
		
//			if ($this->isAjax()) {
//				$this->invalidateControl();
//			}
		}
	}
	
	protected function startup()
	{
		parent::startup();

		$this->initOwnerIdsA();
		$this->processOwnerId();
		
		$this->config = Environment::getConfig('files');

		// if filtering by inspiration complexity -> show it in select box complexity
//		if ($this->filter === FilesModel::COMPLEXITY_INSPIRATION_ID) {
//			$this->complexity = $this->filter;
//		}
		
		$this->model = new LightboxesModel();
//		$this->model = $this->filesModel;
	}
	
	
	protected function beforeRender()
	{
		parent::beforeRender();
//		$this->template->lightboxes = $this->model->findAll();
		$this->template->lightboxOwners = $this->model->findOwners();
	}
	
	
	public function handleLoadLightboxesByOwner()
	{
		$this->refresh("lb_$this->ownerId");
	}
	
	
	/**
	 * load lightboxes by owner
	 * @internal 
	 */
	public function setTemplateLightboxes()
	{
		if (!empty($this->ownerIds_a)) {
			$lightboxes = array();
			foreach ($this->ownerIds_a as $ownerId) {
				$lightboxes[$ownerId] = $this->model->findByOwner($ownerId);
			}
			
			$this->template->lightboxes = $lightboxes;
		}
//		if ($this->ownerId) {
//			$this->template->lightboxes = array(
//				$this->ownerId => $this->model->findByOwner($this->ownerId),
//			);
//		}
	}
	
	/**
	 * list lightbox's files
	 *
	 * @param int lightbox id
	 */
	public function actionList($id)
	{
		$this->setTemplateLightboxes();
		
		// load files
		if ($id) {
			
			$this->template->lightbox = $lb = $this->model->find($id);
			if ($lb === false) {
				throw new BadRequestException('Lightbox does NOT exist!');
			}
			
			
			$this->items = $this->filesModel->findAll();
			try {
				$this->filesModel
							->filterByLightbox($this->items, $id)
	//						->filterByTag($this->items, $this->q)
	//						->filterByComplexity($this->items, $this->complexity)
							->order($this->items, $this->orderby, $this->sorting);
			} catch (TagNotFound $e) {
				$this->flashMessage($e->getMessage(), self::FLASH_MESSAGE_ERROR);
				$this->refresh('flashes', 'this', array('q' => null));
			}
		}
	}
	
	
	public function renderList()
	{
//		$this->template->thumbSize = Environment::getHttpRequest()->getCookie(self::COOKIE_THUMBSIZE) ? Environment::getHttpRequest()->getCookie(self::COOKIE_THUMBSIZE) : FilesModel::SIZE_MEDIUM;
//

		if ($this->items) {
			$this->template->itemsCount = $itemsCount = $this->items->count();
			$vp = $this['itemPaginator'];
			$vp->selectItemsPerPage = array(1,8, 16, 24, 32, 40, 48, 56, 64);
			$vp->itemsPerPageAsSelect = true;
	 		$vp->setDefaultItemsPerPage($this->config->defaultItemsPerPage);
	        $vp->paginator->itemCount = $itemsCount;
	        $vp->itemString = 'per page';
			$this->template->items = $this->items
											->toDataSource()
											->applyLimit($vp->paginator->itemsPerPage, $vp->paginator->offset)
											->fetchAll();
											
	//		 when paging refresh only items
			if ($vp->paginated && !$vp->itemsPerPageChanged) {
				$this->invalidateControl('itemList');
			} elseif (!$this->isControlInvalid()) {
				$this->invalidateControl();
			}
		}
	}
	
	
//	public function handleDownload($id)
//	{
//		$file = $this->filesModel->download($id);
//	}
	
	public function handleShare($id)
	{
		$link = $this->model->generateShareLink($id);
		$this->payload->snippets['snippet--sharelink'] = $link;
		$this->refresh('sharelink');
		$this->terminate();
	}

	
	/**
	 * edit lightbox name using jEditable on frontend
	 * prints updated name and exits
	 *
	 * @param int lightbox id
	 * @param string new lightbox's name
	 * @return void
	 */
	public function handleEditName($id, $name)
	{
		if ($this->user->isAllowed(new LightboxResource($id), 'edit')) {
			try {
				$this->model->updateName($id, $name);
				echo $name;
			} catch (DibiDriverException $e) {
				echo OPERATION_FAILED;
			}
		} else {
			echo NOT_ALLOWED;
		}
		$this->terminate();
	}
	
	
	public function handleDelete($id)
	{
		try {
			if ($this->user->isAllowed(new LightboxResource($id), 'delete')) {
				$this->model->delete($id);
				$this->flashMessage('Lightbox deleted.', self::FLASH_MESSAGE_SUCCESS);
			} else {
				$this->flashMessage(NOT_ALLOWED, self::FLASH_MESSAGE_ERROR);
			}
		} catch (DibiDriverException $e) {
			$this->flashMessage('Lightbox could NOT be deleted.', self::FLASH_MESSAGE_ERROR);
		}

		// reload owner's lightboxes
		$this->setTemplateLightboxes();
		$this->refresh(null, 'list');
	}
	
	
	protected function createComponentAddLightboxForm()
	{
		$form = new MyAppForm;
		$form->enableAjax();
		
		if (!is_null($this->getTranslator())) {
			$form->setTranslator($this->getTranslator());
		}
		
		$form->addText('name', 'Name')
            ->addRule(Form::FILLED);
            
        $form->addSubmit('save', 'Add');
        $_this = $this;
		$form->onSubmit[] = function(MyAppForm $form) use($_this) {
			try {
				if ($form['save']->isSubmittedBy()) {
					$values = $form->getValues();
					
					$_this->model->insert($values);
					$_this->flashMessage('Lightbox created.', $_this::FLASH_MESSAGE_SUCCESS);
					$form->resetValues();
		
				}
			} catch (DibiDriverException $e) {
				// duplicate entry
				if ($e->getCode() === 1062) {
					$_this->flashMessage("ERROR: " . $e->getMessage(), $_this::FLASH_MESSAGE_ERROR);
				} else {
					throw $e;
					$_this->flashMessage("ERROR: cannot save data!", $_this::FLASH_MESSAGE_ERROR);
				}
			}
	
			// reload owner's lightboxes
			$_this->setTemplateLightboxes();
//			$_this->refresh(null, 'this');
			$_this->refresh('lb_' . $_this->userId, 'this');
		};
		
		return $form;
	}

}
