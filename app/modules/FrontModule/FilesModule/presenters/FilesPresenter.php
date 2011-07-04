<?php

class Front_FilesPresenter extends Front_InternalPresenter
{
	protected $_allowedFilters = array(
		FilesModel::FILTER_BY_VECTOR, 
		FilesModel::FILTER_BY_BITMAP, 
		FilesModel::FILTER_BY_INSPIRATION
	);
		
	protected $_allowedOrderby = array(
		FilesModel::ORDER_BY_NAME,
//		FilesModel::ORDER_BY_DATE,
//		FilesModel::ORDER_BY_SIZE,
	);

	
	
	protected function startup()
	{
		parent::startup();

		$this->config = Environment::getConfig('files');

		// if filtering by inspiration complexity -> show it in select box complexity
//		if ($this->filter === FilesModel::COMPLEXITY_INSPIRATION_ID) {
//			$this->complexity = $this->filter;
//		}
		

//		$this->model = new FilesModel();
		$this->model = $this->filesModel;
	}
	
	
	protected function beforeRender()
	{
		parent::beforeRender();
		$this->setRenderSections(array(
			self::RENDER_SECTION_FILTERS => true,
		));
		
//		$this->setupFilesControl();
		// init filesControl
		$this->template->filesControl = $fileControl = $this['filesControl'];

		$vp = $fileControl['itemPaginator'];
		// when paging refresh only items
		if ($vp->paginated && !$vp->itemsPerPageChanged) {
			$this->invalidateControl('itemList');
		} elseif (!$this->isControlInvalid()) {
			$this->invalidateControl();
		}
	}
	
}
