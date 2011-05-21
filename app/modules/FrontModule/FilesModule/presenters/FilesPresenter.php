<?php

class Front_FilesPresenter extends Front_InternalPresenter
{
	/** @persistent */
	public $complexity = FilesModel::COMPLEXITY_ALL_LEVELS_ID;
	
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
	
	
	protected function startup()
	{
		parent::startup();

		// if filtering by inspiration complexity -> show it in select box complexity
//		if ($this->filter === FilesModel::COMPLEXITY_INSPIRATION_ID) {
//			$this->complexity = $this->filter;
//		}
		
//		$this->model = new FilesModel();
		$this->model = $this->filesModel;
	}
	
	
	public function actionList()
	{
		$this->items = $this->model->findAll();
		try {
			$this->model->filter($this->items, $this->filter)
						->filterByTag($this->items, $this->q)
						->filterByComplexity($this->items, $this->complexity)
						->order($this->items, $this->orderby, $this->sorting);
		} catch (TagNotFound $e) {
			$this->flashMessage($e->getMessage(), self::FLASH_MESSAGE_ERROR);
			$this->refresh('flashes', 'this', array('q' => null));
		}
	}
	
	
	public function renderList()
	{
		$this->template->thumbSize = Environment::getHttpRequest()->getCookie('thumbSize') ? Environment::getHttpRequest()->getCookie('thumbSize') : FilesModel::SIZE_MEDIUM;

		$this->template->filesModel = $this->filesModel;
		
		$this->template->itemsCount = $itemsCount = $this->items->count();
		
		$vp = $this['itemPaginator'];
		$vp->selectItemsPerPage = array(1,8, 16, 24, 32, 40, 48, 56, 64);
		$vp->itemsPerPageAsSelect = true;
 		$vp->setDefaultItemsPerPage($this->itemsPerPage);
        $vp->paginator->itemCount = $itemsCount;
        $vp->itemString = 'per page';
		$this->template->items = $this->items
										->toDataSource()
										->applyLimit($vp->paginator->itemsPerPage, $vp->paginator->offset)
										->fetchAll();
										
		// when paging refresh only items
		if ($vp->paginated && !$vp->itemsPerPageChanged) {
			$this->invalidateControl('itemList');
		} elseif (!$this->isControlInvalid()) {
			$this->invalidateControl();
		}
	}
	
	
	public function handleDownload($id)
	{
		$file = $this->filesModel->download($id);
	}

	
	/**
	 * fill addFile2LightboxForm values
	 *
	 * @param int
	 */
	public function prepareAddFile2LightboxForm($fileId)
	{
		$form = $this["addFile2LightboxForm_$fileId"];
		$form['files_id']->value = $fileId;
		// keep challenge option (first) and add only user's own lightboxes that file is not in yet
		$items = array($form['lightboxes_id']->items[0]) + $this->filesModel->fetchOwnUnusedLightboxes($fileId);
		$form['lightboxes_id']->items = $items;
	}

	
	/**
	 * factory for AddFile2LightboxForm
	 *
	 */
	protected function getComponentAddFile2LightboxForm()
	{
		$form = new MyAppForm();
		$form->enableAjax();
			
		// fill with all user's lightboxes by default (we don't know fileId in time of creation form, so we have to change items later when rendering by calling $this->prepareAddFile2LightboxForm(fileId))
		$lightboxes = BaseModel::prepareSelect($this->lightboxModel->getOwnPairs(), 'Add to lightbox', true);
		$form->addSelect('lightboxes_id', null, $lightboxes)
			->skipFirst()
			->addRule(Form::FILLED)
			->getControlPrototype()
				->class = 'sumbitOnChange';

		$form->addHidden('files_id');
		
		$form->addSubmit('set', 'Set')
			->getControlPrototype()->class('noJS noJS-tr');

		$presenter = $this;
		$form->onSubmit[] = function($form) use ($presenter) {
			$values = $form->getValues();
			$fileId = $values['files_id'];
			$lightboxId = $values['lightboxes_id'];
			$presenter->filesModel->add2lightbox($fileId, $lightboxId);
			$form->resetValues();
			$presenter->flashMessage('File added to lightbox', $presenter::FLASH_MESSAGE_SUCCESS);
			$presenter->refresh('items', 'this');
//			$presenter->refresh("item-$fileId", 'this');
		};

		return $form;
	}
		
	protected function createComponent($name)
	{
		if (strpos($name, 'addFile2LightboxForm') === 0) {
			$component = $this->getComponentAddFile2LightboxForm();
		}
		
//		dump($name);
//		switch ($name) {
//			case 'addFile2LightboxForm':// @intentionally no break
//		 		$component = new VisualPaginator();
//				break;
//		
//			default:
//				break;
//		}

		if (isset($component)) {
			if ($component instanceof IComponent && $component->getParent() === NULL) {
				$this->addComponent($component, $name);
			}
		} else {
			parent::createComponent($name);
		}
	}
	


	protected function createComponentComplexityForm($name)
	{
		$form = new MyAppForm($this, $name);
		$form->enableAjax();
			
		$complexity = BaseModel::prepareSelectTree($this->complexityModel->getTree(), 1);
//		$form->addSelect('complexity_id', 'Complexity', $complexity)
		$form->addSelect('complexity_id', null, $complexity)
			->setDefaultValue($this->complexity);

		$form->addSubmit('set', 'Set')
			->getControlPrototype()->class('noJS');

		$presenter = $this;
		$form->onSubmit[] = function($form) use ($presenter) {
			$values = $form->getValues();
			$presenter->complexity = $values['complexity_id'];
			$presenter->refresh(null, 'this');
		};

		return $form;
	}
}
