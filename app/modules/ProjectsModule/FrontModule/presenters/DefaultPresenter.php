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
	
	/** @var DibiRow */
	private $project;
	
	/** @var DibiRow array, project files */
	private $files;
	
	
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

	
	protected function beforeRender()
	{
		parent::beforeRender();

		$this->template->projectsModel = $this->projectsModel;
		$this->template->renderMode = $this->renderMode;
		
		
		// moved from renderList
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
	
	
	public function actionList()
	{
		$this->items = $this->model->findAll();
//		try {
			$this->model->filter($this->items, $this->filter)
						->filterByNameOrSubtitle($this->items, $this->q)
//						->filterByComplexity($this->items, $this->complexity)
						->order($this->items, $this->orderby, $this->sorting);
//		} catch (TagNotFound $e) {
//			$this->flashMessage($e->getMessage(), self::FLASH_MESSAGE_ERROR);
//			$this->refresh('flashes', 'this', array('q' => null));
//		}
	}
	
	
	public function renderList()
	{
		
	}
	

	public function actionDetail($id)
	{
		$this->project = $this->model->find($id);
		if (!$this->project) {
			throw new BadRequestException('Project does not exist');
		}
		
		$this->items = $this->filesModel->findAll();
		try {
			$this->filesModel//->filter($this->items, $this->filter)
						->filterByProject($this->items, $id)
						->filterByTag($this->items, $this->q)
//						->filterByComplexity($this->items, $this->complexity)
						->order($this->items, $this->orderby, $this->sorting);
		} catch (TagNotFound $e) {
			$this->flashMessage($e->getMessage(), self::FLASH_MESSAGE_ERROR);
			$this->refresh('flashes', 'this', array('q' => null));
		}
	}

	
	public function renderDetail($id)
	{
		$this->template->thumbSize = Environment::getHttpRequest()->getCookie(self::COOKIE_THUMBSIZE) ? Environment::getHttpRequest()->getCookie(self::COOKIE_THUMBSIZE) : FilesModel::SIZE_MEDIUM;

		$this->template->project = $this->project;
//		$this->template->project['files'] = $this->template->items;
		$this->template->project->files = $this->template->items;
	}
	
	
	public function handleDownloadAll($projectId)
	{
		$this->model->download($projectId);
		$files = $this->getFilesModel()->findAll();
	}
	
}
