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
		$this->items = $this->model->findAll();
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
	 * render item list
	 */
	public function renderList()
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
	 * edit lightbox name using jEditable on frontend
	 * prints updated name and exits
	 *
	 * @param int lightbox id
	 * @param string new lightbox's name
	 * @return void
	 */
	public function handleEditFileDesc($id, $desc)
	{
		if ($this->user->isAllowed(new FileResource($id), 'edit_description')) {
			try {
				$this->model->update($id, array(
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
		$items = array($form['lightboxes_id']->items[0]) + $this->model->fetchOwnUnusedLightboxes($fileId);
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
		$form = new MyAppForm($this, $name);
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
					if ($_this->user->isAllowed(new FileResource($fileId), 'bind_tag')) {
					
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
						$_this->model->bindTags($fileId, array_keys($tags));
						
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
	
	
	
	
	protected function createComponentItemPaginator($name)
	{
		return new VisualPaginator($this, $name);
	}
	

}