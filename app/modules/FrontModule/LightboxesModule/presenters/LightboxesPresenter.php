<?php

//class Front_LightboxesPresenter extends Front_InternalPresenter
class Front_LightboxesPresenter extends Front_OwnerBasedPresenter
{
	protected $_allowedOrderby = array(
		null,
	);
	
	protected $defaults = array(
		'orderby' => null,
	);
	
	
	protected function startup()
	{
		parent::startup();

		$this->config = Environment::getConfig('files');

		$this->defaults['orderby'] = null;
		$this->orderby = null;
		
		$this->model = new LightboxesModel();
	}
	
	
	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->lightboxes = $this->template->ownerItems;
		$this->template->lightboxOwners = $this->model->findOwners();
		
		$this->setRenderSections(array(
			self::RENDER_SECTION_OPTIONS => false,
		));
	}
	
	
	/**
	 * list lightbox's files
	 *
	 * @param int lightbox id
	 */
	public function actionList($id)
	{
		$this->setTemplateOwnerItems();
		
		// show latest lb of logged user by default
		if (!$id) {
			$id = $this->model->findUserLatestId();
			$this->redirect('list', array($id));
		} else {
			$this->template->lightbox = $lb = $this->model->find($id);
		}
		
		if ($lb === false) {
			throw new BadRequestException('Lightbox does NOT exist!');
		}
	}
	
	
	public function renderList($id)
	{
		// init filesControl
		$fileControl = $this['filesControl'];
		$fileControl->applyFilters(
			array(
				FilesModel::FILTER_BY_LIGHTBOX => $id,
			)
		);
		$this->template->filesControl = $fileControl;
			
		// when paging refresh only items
		$vp = $fileControl['itemPaginator'];
		if ($vp->paginated && !$vp->itemsPerPageChanged) {
			$this->invalidateControl('itemList');
		} elseif (!$this->isControlInvalid()) {
			$this->invalidateControl();
		}
	}
	
	
	/**
	 * download all files from given lightbox
	 *
	 * @param int
	 */
	public function handleDownload($id)
	{
		$fileControl = $this['filesControl'];
		$fileControl->applyFilters(
			array(
				FilesModel::FILTER_BY_LIGHTBOX => $id,
			)
		);
		$fileIds = array();
		foreach ($fileControl->getItems() as $item) {
			$fileIds[] = $item->id;
		}
		$this->filesModel->download($fileIds);
	}
	
	
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
		parent::editName(new LightboxResource($id), $id, $name);
	}
	
	
	/**
	 * delete lightbox
	 *
	 * @param int
	 */
	public function handleDelete($delId)
	{
		parent::delete(new LightboxResource($delId), $delId, 'Lightbox');
	}
	
	
	/**
	 * factory for creating lightbox
	 *
	 * @return MyAppForm
	 */
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
	
//			$_this->refresh(null, 'this');
			$_this->refresh('lb-' . $_this->userId, 'this');
		};
		
		return $form;
	}

}
