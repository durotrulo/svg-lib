<?php

class Projects_Front_DefaultPresenter extends Front_InternalPresenter
{
	/** 
	 * @var char(1) first letter for filtering projects
	 * @persistent
	 */
	public $firstLetter;
	
	protected $_allowedFilters = array(
		ProjectsModel::FILTER_COMPLETED,
		ProjectsModel::FILTER_IN_PROGRESS,
	);
	
	protected $_allowedOrderby = array(
		ProjectsModel::ORDER_BY_NAME,
		ProjectsModel::ORDER_BY_DATE,
	);
	
	/** @var DibiRow */
	private $project;
	
	/** @var DibiRow array, project files */
	private $files;
	
	
	protected function startup()
	{
		parent::startup();

		$this->config = Environment::getConfig('projects');

		// trim to length === 1
		if (!empty($this->firstLetter)) {
			$this->firstLetter = substr($this->firstLetter, 0, 1);
		}
		
		$this->model = $this->projectsModel;
	}

	
	protected function beforeRender()
	{
		parent::beforeRender();

		$this->setRenderSections(array(
			self::RENDER_SECTION_FILTERS => true,
		));
		
		
		
		// list projects to panel (and content if on project display)
//		$this->items = $this->model->findAll();
////		try {
//			$this->model->filter($this->items, $this->filter)
//						->filter($this->items, ProjectsModel::FILTER_FIRST_LETTER, $this->firstLetter)
//						->filterByNameOrSubtitle($this->items, $this->q)
////						->filterByComplexity($this->items, $this->complexity)
//						->order($this->items, $this->orderby, $this->sorting);
//		
		
		$projectList = $this->model->findAll();
//		try {
			$this->model->filter($projectList, $this->filter)
						->filter($projectList, ProjectsModel::FILTER_FIRST_LETTER, $this->firstLetter)
						->filterByNameOrSubtitle($projectList, $this->q)
//						->filterByComplexity($projectList, $this->complexity)
						->order($projectList, $this->orderby, $this->sorting);
		$this->template->projectList = $projectList;

		$this->template->projectsModel = $this->projectsModel;
		
		/*
*/
		if (!$this->isControlInvalid()) {
			$this->invalidateControl();
		}
	}
	
	
	// list projects
	public function actionList()
	{
		/*
		*/
		$this->items = $this->model->findAll();
//		try {
			$this->model->filter($this->items, $this->filter)
						->filter($this->items, ProjectsModel::FILTER_FIRST_LETTER, $this->firstLetter)
						->filterByNameOrSubtitle($this->items, $this->q)
//						->filterByComplexity($this->items, $this->complexity)
						->order($this->items, $this->orderby, $this->sorting);
//		} catch (TagNotFound $e) {
//			$this->flashMessage($e->getMessage(), self::FLASH_MESSAGE_ERROR);
//			$this->refresh('flashes', 'this', array('q' => null));
//		}

		$this->template->filesControl = $this['filesControl'];

	}
	
	
	public function renderList()
	{
		$this->template->itemsCount = $itemsCount = $this->items->count();
		
		$vp = $this['itemPaginator'];
		$vp->selectItemsPerPage = array(1, 4, 8, 16, 24, 32, 40, 48, 56, 64);
		$vp->itemsPerPageAsSelect = true;
 		$vp->setDefaultItemsPerPage($this->config->defaultItemsPerPage);
        $vp->paginator->itemCount = $itemsCount;
        $vp->itemString = 'per page';
		$this->template->items = $this->items
										->toDataSource()
										->applyLimit($vp->paginator->itemsPerPage, $vp->paginator->offset)
										->fetchAll();
										
		foreach ($this->template->items as &$project) {
			$project['related'] = $this->model->getRelatedProjects($project->id);
		}
		
		// when paging refresh only items
		if ($vp->paginated && !$vp->itemsPerPageChanged) {
			$this->invalidateControl('itemList');
		} else {
			$this->invalidateControl();
		}
	}
	

	
	public function actionDetail($id)
	{
		$this->project = $this->model->find($id);
		if (!$this->project) {
			throw new BadRequestException('Project does not exist');
		}

		// init filesControl
		$fileControl = $this['filesControl'];
		$fileControl->applyFilters(
			array(
				FilesModel::FILTER_BY_PROJECT => $id,
			)
		);
		$this->template->filesControl = $fileControl;
	}

	
	public function renderDetail($id)
	{
//		$this->template->thumbSize = Environment::getHttpRequest()->getCookie(self::COOKIE_THUMBSIZE) ? Environment::getHttpRequest()->getCookie(self::COOKIE_THUMBSIZE) : FilesModel::SIZE_MEDIUM;

		$this->template->project = $this->project;
		$this->template->project['topFiles'] = $this->model->getTopFiles($id);
//		$this->template->project['files'] = $this->template->items;
//		$this->template->project->files = $this->template->items;
		$this->template->isProjectDetail = true;
	}
	
	
	public function handleDownloadAll($projectId)
	{
		$this->model->download($projectId);
		$files = $this->getFilesModel()->findAll();
	}
	
	
	/**
	 * save order after sorting topfiles
	 *
	 */
	public function handleSortTopFiles($topfile)
	{
		$sortedItems = $topfile;
		$sortableModel = new SortableModel(BaseModel::FILES_TABLE, 'id', 'top_file_order');
		$sortableModel->saveOrder($sortedItems);
		$this->flashMessage('Items order saved', self::FLASH_MESSAGE_SUCCESS);
		$this->refresh('none');
	}
	
	
	public function handleSetFirstLetter($fl)
	{
		$this->firstLetter = $fl;
		if ($this->getAction() === 'detail') {
//			$this->validateControl('itemList');
//			$this->refresh('projectList', 'this', array(), true);
			$this->refresh('projectList');
		} else {
			$this->refresh(array('projectList', 'itemList'), 'this', array(), true);
		}
	}
	
}
