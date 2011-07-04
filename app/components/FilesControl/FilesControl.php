<?php

/**
 * Login control
 * 
 * generates login form and handles whole authentication process
 * 
 * 
 * options:
 * 	multiple rows / inline visual layout possible by css change
 *
 * @author Matus Matula
 */
class FilesControl extends BaseControl
{
	/**
	 * @var string name of cookie to store size of thumbnails
	 */
	const COOKIE_THUMBSIZE = 'thumbSize';

	/** 
	 * @var string - one of _allowedOrderby
	 * @persistent 
	 */
	public $orderby = FilesModel::ORDER_BY_NAME;
	
	/**
	 * @var string [dibi::DESC | dibi::ASC]
	 * @persistent 
	 */
	public $sorting = dibi::DESC;

	/**
	 * @var int #complexity.id 
	 * @persistent 
	 */
	public $complexity = FilesModel::COMPLEXITY_ALL_LEVELS_ID;

	/**
	 * allowed values of $this->orderby
	 * @var array
	 */
	private $_allowedOrderby = array(
		FilesModel::ORDER_BY_NAME,
		FilesModel::ORDER_BY_DATE,
		FilesModel::ORDER_BY_SIZE,
	);
	
	/** DibiRow array of files */
	private $items;
	
	/** @var int */
	private $itemsCount;
		
	/** @var ComplexityModel */
	private $complexityModel;
	
	/** @var LightboxesModel */
	private $lightboxModel;

	/** @var TagsModel */
	private $tagsModel;
	
	
	
	const MODE_STANDARD = 'standard_mode';
	const MODE_LIGHTBOX = 'lightbox_mode';
	public $isAddToLbAllowed = true;
	public $isDownloadAllowed = true;
	public $isBulkActionAllowed = false;
	public $isRemoveFromLbAllowed = false;
	
	
	const BULK_ACTION_REMOVE_FROM_LB = 'remove_from_lightbox';
	const BULK_ACTION_DOWNLOAD = 'download_files';
	
	
	public function __construct(IComponentContainer $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);
		
		$this->model = new FilesModel();
		$this->complexityModel = new ComplexityModel();

		$this->config = Environment::getConfig('files');
		
		if (!is_null($this->orderby) and !in_array($this->orderby, $this->_allowedOrderby)) {
			throw new InvalidStateException('Parameter orderby must be one of ' . join(',', $this->_allowedOrderby) . ".'$this->orderby' given.");
		}
		
		BaseModel::validateSorting($this->sorting, dibi::DESC);
		
		$this->loadItems();
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
	
	
	/**
	 * return path to template containing head files (js, css) to be included
	 *
	 * @return string
	 */
	public function getHeadFilesTplPath()
	{
		$path = __DIR__ . '/headFiles.phtml';
		return $path;
	}

	
	/**
	 * render options for itemList
	 *
	 */
	public function renderOptions()
	{
		$tpl = $this->template;
		$tpl->setFile(__DIR__ . '/options.phtml');

		$tpl->render();
	}
	
	
	/**
	 * load items to $this->items
	 * equivalent to actionList()
	 * called on each request
	 */
	public function loadItems()
	{
		$this->items = $this->model->findAll()
									->where('is_top_file = 0');
		try {
			$this->model->filter($this->items, $this->presenter->filter)
						->filterByTag($this->items, $this->presenter->q)
						->filterByComplexity($this->items, $this->complexity)
						->order($this->items, $this->orderby, $this->sorting);
		} catch (TagNotFound $e) {
			$this->flashMessage($e->getMessage(), self::FLASH_MESSAGE_ERROR);
			$this->refresh('flashes', 'this', array('q' => null));
		}
		
		$this->template->itemsCount = $this->itemsCount = $this->items->count();
		$this->template->thumbSize = Environment::getHttpRequest()->getCookie(self::COOKIE_THUMBSIZE) ? Environment::getHttpRequest()->getCookie(self::COOKIE_THUMBSIZE) : FilesModel::SIZE_MEDIUM;
	}
	
	
	/**
	 * apply additional filters
	 *
	 * @param array (filterName => filterValue)
	 */
	public function applyFilters($filters)
	{
		foreach ($filters as $filter => $filterVal) {
			$this->model->filter($this->items, $filter, $filterVal);
		}
//		dump($this->items);

		$this->template->itemsCount = $this->itemsCount = $this->items->count();
	}
	
	
	private function setMode($mode)
	{
		switch ($mode) {
			case self::MODE_LIGHTBOX:
				$this->isAddToLbAllowed = false;
				$this->isBulkActionAllowed = true;
				$this->isDownloadAllowed = true;
				$this->isRemoveFromLbAllowed = true;
				$this->template->thumbSize = FilesModel::SIZE_MEDIUM;
				break;
				
			case self::MODE_STANDARD: // @intentionally no break
			default:
				$this->isAddToLbAllowed = true;
				$this->isBulkActionAllowed = false;
				$this->isDownloadAllowed = true;
				$this->isRemoveFromLbAllowed = false;
				break;
		}
	}
	
	
	/**
	 * render item list
	 * @param string render mode
	 * @param array optional params to be injected to template
	 */
	public function renderList($mode = self::MODE_STANDARD, $tplParams = array())
	{
		$tpl = $this->template;
		$tpl->setFile(__DIR__ . '/itemList.phtml');

		$vp = $this['itemPaginator'];
		$vp->selectItemsPerPage = array(1, 8, 16, 24, 32, 40, 48, 56, 64);
		$vp->itemsPerPageAsSelect = true;
 		$vp->setDefaultItemsPerPage($this->config->defaultItemsPerPage);
        $vp->paginator->itemCount = $this->itemsCount;
        $vp->itemString = 'per page';
		$tpl->items = $this->items
							->toDataSource()
							->applyLimit($vp->paginator->itemsPerPage, $vp->paginator->offset)
							->fetchAll();

		$this->setMode($mode);
		foreach ($tplParams as $k=>$v) {
			$tpl->$k = $v;
		}
		// when paging refresh only items - DONE IN PRESENTER
//		if ($vp->paginated && !$vp->itemsPerPageChanged) {
//			$this->invalidateControl('itemList');
//		} elseif (!$this->isControlInvalid()) {
//			$this->invalidateControl();
//		}

		$this->invalidateControl();
		
		$tpl->render();
	}
	
	
	/**
	 * download file
	 *
	 * @param int fileId
	 */
	public function handleDownload($id)
	{
		$file = $this->model->download($id);
	}
	
	
	/**
	 * remove file from lightbox
	 *
	 * @param int
	 * @param int
	 */
	public function handleRemoveFromLightbox($fileId, $lightboxId)
	{
		$this->handleBulkAction(self::BULK_ACTION_REMOVE_FROM_LB, $fileId, $lightboxId);
	}

	
	public function handleBulkAction($action, $fileIds, $lightboxId)
	{
		$fileIds = explode('-', $fileIds);
		try {
			switch ($action) {
				case self::BULK_ACTION_REMOVE_FROM_LB:
	//				$this->model->removeFromLightbox($fileIds, $lightboxId);
					$this->presenter->payload->actions = array(
						array(
							'name' => 'fileRemovedFromLb',
							'itemIds' => $fileIds,
						),
					);
					break;
			
				case self::BULK_ACTION_DOWNLOAD:
					$this->model->download($fileIds);
					break;
					
				default:
					break;
			}
		} catch (DibiDriverException $e) {
			$this->flashMessage($e->getMessage(), self::FLASH_MESSAGE_ERROR);
			$this->refresh('flashes');
		}

		$this->presenter->terminate();
	}
	
	
	/**
	 * load tags bound to file ($fileId) and send to client
	 *
	 * @param int
	 */
	public function handleGetTags($fileId)
	{
		$this->presenter->payload->tags = $this->model->getTags($fileId);
		$this->presenter->terminate();
	}
	
		
	/**
	 * load file description and send to client
	 *
	 * @param int
	 */
	public function handleGetDesc($fileId)
	{
		$this->presenter->payload->desc = nl2br($this->model->getDesc($fileId));
		$this->presenter->terminate();
	}
	
	
	/**
	 * set thumb size for files - no js fallback
	 */
	public function handleSetThumbSize($size)
	{
		if (!in_array($size, $this->model->sizes)) {
			throw new ArgumentOutOfRangeException('Parameter $size must be on of ' . join(',', $this->model->sizes) . '. Given: ' . $size);
		}
		
		Environment::getHttpResponse()->setCookie(self::COOKIE_THUMBSIZE, $size, Tools::YEAR);
		$this->refresh('this');
	}
	
	
	/**
	 * fill addFile2LightboxForm values
	 * called on every file in list
	 *
	 * @param int
	 */
	public function prepareAddFile2LightboxForm($fileId)
	{
		$form = $this["addFile2LightboxForm_$fileId"];
		$form['files_id']->value = $fileId;
		// keep challenge option (first) and add only user's own lightboxes that file is not in yet
		$items = array($form['lightboxes_id']->items[-1]) + $this->model->fetchOwnUnusedLightboxes($fileId);
		$form['lightboxes_id']->items = $items;
	}
	
	
	
							/****************************************/
							/* COMPONENT FACTORIES     			  	*/
							/****************************************/

	
	/**
	 * factory for AddFile2LightboxForm
	 */
	protected function getComponentAddFile2LightboxForm()
	{
		$form = new MyAppForm();
		$form->enableAjax();
			
		// fill with all user's lightboxes by default (we don't know fileId in time of creation form, so we have to change items later when rendering by calling $this->prepareAddFile2LightboxForm(fileId))
		$lightboxes = BaseModel::prepareSelect($this->getLightboxModel()->getOwnPairs(), 'Add to lightbox', true);
		$form->addSelect('lightboxes_id', null, $lightboxes)
			->skipFirst()
			->addRule(Form::FILLED)
			->getControlPrototype()
				->class = 'sumbitOnChange';

		$form->addHidden('files_id');
		
		$form->addSubmit('set', 'Set')
			->getControlPrototype()->class('noJS noJS-tr');

		$_this = $this;
		$form->onSubmit[] = function($form) use ($_this) {
			$values = $form->getValues();
			$fileId = $values['files_id'];
			$lightboxId = $values['lightboxes_id'];
			$_this->model->add2lightbox($fileId, $lightboxId);
			$form->resetValues();
			$_this->flashMessage('File added to lightbox', $_this::FLASH_MESSAGE_SUCCESS);
			$_this->refresh('items', 'this');
//			$_this->refresh("item-$fileId", 'this');
		};

		return $form;
	}
		
	
	/**
	 * method to allow dynamic addFile2Lightbox forms for each file
	 */
	protected function createComponent($name)
	{
		if (strpos($name, 'addFile2LightboxForm') === 0) {
			$component = $this->getComponentAddFile2LightboxForm();
		}
		
		if (isset($component)) {
			if ($component instanceof IComponent && $component->getParent() === NULL) {
				$this->addComponent($component, $name);
			}
		} else {
			parent::createComponent($name);
		}
	}


	/**
	 * factory for complexity form
	 */
	protected function createComponentComplexityForm($name)
	{
		$form = new MyAppForm($this, $name);
		$form->enableAjax();
			
		$complexity = BaseModel::prepareSelectTree($this->complexityModel->getTree(), 1);
		$form->addSelect('complexity_id', null, $complexity)
			->setDefaultValue($this->complexity);

		$form->addSubmit('set', 'Set')
			->getControlPrototype()->class('noJS');

		$_this = $this;
		$form->onSubmit[] = function($form) use ($_this) {
			$values = $form->getValues();
			$_this->complexity = $values['complexity_id'];
			$_this->refresh(null, 'this', array(), true);
		};

		return $form;
	}
	

	/**
	 * factory for bind tag 2 file form
	 */
	protected function createComponentBindTagForm($name)
	{
		return $this->presenter->getBindTagFormComponent($this, $name);
	}
	
	
	
	
	protected function createComponentItemPaginator($name)
	{
		return new VisualPaginator($this, $name);
	}
	

}