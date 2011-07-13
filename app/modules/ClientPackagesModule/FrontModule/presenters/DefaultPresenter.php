<?php

class ClientPackages_Front_DefaultPresenter extends Front_OwnerBasedPresenter
{
	/** 
	 * @var char(1) first letter for filtering projects
	 * @persistent
	 */
	public $firstLetter;
	
//	protected $_allowedFilters = array(
//		ProjectsModel::FILTER_COMPLETED,
//		ProjectsModel::FILTER_IN_PROGRESS,
//	);
	
	protected $_allowedOrderby = array(
		ClientPackagesModel::ORDER_BY_NAME,
		ClientPackagesModel::ORDER_BY_DATE,
	);
	
	/** @var DibiRow */
	private $package;
	
	
	protected function startup()
	{
		parent::startup();

		$this->config = Environment::getConfig('clientPackages');

		// trim to length === 1
		if (!empty($this->firstLetter)) {
			$this->firstLetter = substr($this->firstLetter, 0, 1);
		}
		
		$this->model = $this->getClientPackagesModel();
	}

	
	protected function beforeRender()
	{
		parent::beforeRender();

		$this->template->packages = $this->template->ownerItems;
		$this->template->packageOwners = $this->model->findOwners($this->firstLetter);
//		$this->template->packageOwners = $this->template->itemOwners; // clients
		
		$this->setRenderSections(array(
			self::RENDER_SECTION_FILTERS => true,
		));
		
//		$this->template->project = $this->template->package;
		
		/*
*/
		// list projects to panel (and content if on project display)
		$packagesList = $this->model->findAll();
		$this->model->filter($packagesList, ProjectsModel::FILTER_FIRST_LETTER, $this->firstLetter)
					->filterByNameOrSubtitle($packagesList, $this->q)
					->order($packagesList, $this->orderby, $this->sorting);
		$this->template->packagesList = $packagesList;

		$this->template->packagesModel = $this->getClientPackagesModel();
		
		if (!$this->isControlInvalid()) {
			$this->invalidateControl();
		}
	}
	
	
	public function actionList()
	{
//		parent::actionList($id);
		$this->items = $this->model->findAll();
		$this->model->filter($this->items, ProjectsModel::FILTER_FIRST_LETTER, $this->firstLetter)
					->filterByNameOrSubtitle($this->items, $this->q)
					->order($this->items, $this->orderby, $this->sorting);

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
										
		// when paging refresh only items
		if ($vp->paginated && !$vp->itemsPerPageChanged) {
			$this->invalidateControl('itemList');
		} else {
			$this->invalidateControl();
		}
	}
	

	
	public function actionDetail($id)
	{
		$this->package = $this->model->find($id);
		if (!$this->package) {
			throw new BadRequestException('Package does not exist');
		}

		// init filesControl
		$fileControl = $this['filesControl'];
		$fileControl->applyFilters(
			array(
				FilesModel::FILTER_BY_CLIENT_PACKAGE => $id,
			)
		);
		$this->template->filesControl = $fileControl;
	}

	
	public function renderDetail($id)
	{
//		$this->template->thumbSize = Environment::getHttpRequest()->getCookie(self::COOKIE_THUMBSIZE) ? Environment::getHttpRequest()->getCookie(self::COOKIE_THUMBSIZE) : FilesModel::SIZE_MEDIUM;

		$this->template->package = $this->package;
		$this->template->package['topFiles'] = $this->model->getTopFiles($id);
	}
	
	
	/*
	public function handleDownloadAll($projectId)
	{
		$this->model->download($projectId);
		$files = $this->getFilesModel()->findAll();
	}
	*/
	
	
	public function handleSetFirstLetter($fl)
	{
		$this->firstLetter = $fl;
		$this->ownerId = null;
		$this->ownerIds = null;
		if ($this->getAction() === 'detail') {
			$this->refresh('firstletter');
		} else {
			$this->refresh(array('firstletter', 'itemList'), 'this', array(), true);
		}
	}
	
	
	/**
	 * edit package name using jEditable on frontend
	 * prints updated name and exits
	 *
	 * @param int package id
	 * @param string new package's name
	 * @return void
	 */
	public function handleEditName($id, $name)
	{
		parent::editName(new ClientPackageResource($id), $id, $name);
	}
	
	
	/**
	 * delete package
	 *
	 * @param int
	 */
	public function handleDelete($delId)
	{
		parent::delete(new ClientPackageResource($delId), $delId, 'Lightbox');
	}
	
}
