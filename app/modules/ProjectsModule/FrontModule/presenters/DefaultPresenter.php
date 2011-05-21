<?php

class Projects_Front_DefaultPresenter extends Front_InternalPresenter
{
	const RENDER_MODE_LIST = 'list';
	const RENDER_MODE_THUMBNAILS = 'thumbnails';
	
	const COOKIE_NAME_RENDER_MODE = 'projects_renderMode';
	
	/** @persistent */
	public $renderMode;
	
	protected $_allowedRenderModes = array(
		self::RENDER_MODE_LIST,
		self::RENDER_MODE_THUMBNAILS,
	);
	
	protected $_allowedFilters = array(
		ProjectsModel::FILTER_COMPLETED,
		ProjectsModel::FILTER_IN_PROGRESS,
	);
	
	protected $_allowedOrderby = array(
		ProjectsModel::ORDER_BY_NAME,
		ProjectsModel::ORDER_BY_DATE,
	);
	
	private $defaultRenderMode = self::RENDER_MODE_LIST;
	
	protected function startup()
	{
		parent::startup();

		$this->config = Environment::getConfig('projects');

		if (!is_null($this->renderMode) and !in_array($this->renderMode, $this->_allowedRenderModes)) {
			throw new InvalidStateException('Parameter renderMode must be one of ' . join(',', $this->_allowedRenderModes) . ".'$this->renderMode' given.");
		}

		// default render mode
		if (is_null($this->renderMode)) {
			$this->renderMode = Environment::getHttpRequest()->getCookie(self::COOKIE_NAME_RENDER_MODE) ? Environment::getHttpRequest()->getCookie(self::COOKIE_NAME_RENDER_MODE) : $this->defaultRenderMode;
		}
		Environment::getHttpResponse()->setCookie(self::COOKIE_NAME_RENDER_MODE, $this->renderMode, Tools::YEAR);
		
		$this->model = $this->projectsModel;
	}
	
	
	public function actionList()
	{
		$this->items = $this->model->findAll();
//		try {
			$this->model->filter($this->items, $this->filter)
						->filterByName($this->items, $this->q)
//						->filterByComplexity($this->items, $this->complexity)
						->order($this->items, $this->orderby, $this->sorting);
//		} catch (TagNotFound $e) {
//			$this->flashMessage($e->getMessage(), self::FLASH_MESSAGE_ERROR);
//			$this->refresh('flashes', 'this', array('q' => null));
//		}
	}
	
	
	public function renderList()
	{
		$this->template->projectsModel = $this->projectsModel;
		$this->template->renderMode = $this->renderMode;

		$this->template->itemsCount = $itemsCount = $this->items->count();
		
		$vp = $this['itemPaginator'];
		$vp->selectItemsPerPage = array(1, 8, 16, 24, 32, 40, 48, 56, 64);
		$vp->itemsPerPageAsSelect = true;
 		$vp->setDefaultItemsPerPage($this->config->defaultItemsPerPage);
        $vp->paginator->itemCount = $itemsCount;
        $vp->itemString = 'per page';
		$this->template->items = $this->items
										->toDataSource()
										->applyLimit($vp->paginator->itemsPerPage, $vp->paginator->offset)
										->fetchAll();
										
		// when paging refresh only items
		if ($vp->paginated && !$vp->itemsPerPageChanged) {
			$this->invalidateControl('itemList');
		} else {
			$this->invalidateControl();
		}
	}
	
}
