<?php

abstract class Front_OwnerBasedPresenter extends Front_InternalPresenter
{
	const OWNER_IDS_SEP = '-';
	
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
	
	
	/**
	 * initialize $this->ownerIds_a based on $this->ownerIds
	 * called from startup()
	 */
    private function initOwnerIdsA()
	{
		//	aby mi to nevracalo pole s prazdnym retazcom
		if (!empty($this->ownerIds)) {
			$this->ownerIds_a = explode(self::OWNER_IDS_SEP, $this->ownerIds);
		} else {
			$this->ownerIds_a = array();
		}
	}
	
	
    /**
     * nastavi id tagov, podla kt. sa sortuje
     * todo: english please
     *
     */
	private function processOwnerId()
	{
		if (!empty($this->ownerId)) {
			//	pridam tag, iba ak sa tam este nenachadza taky tag
			if (!in_array($this->ownerId, $this->ownerIds_a)) {
				$this->ownerIds_a[] = $this->ownerId;
			//	inak ho vyhodim
			} else {
				MyArrayTools::unsetByValue($this->ownerIds_a, $this->ownerId);
			}
			$this->ownerIds = join(self::OWNER_IDS_SEP, $this->ownerIds_a);
		
//			if ($this->isAjax()) {
//				$this->invalidateControl();
//			}
		} else {
			// if no ids set, set logged user's id at least
			if (empty($this->ownerIds_a)) {
				$this->ownerId = $this->userId;
			}
		}
	}
	
	protected function startup()
	{
		parent::startup();

		$this->initOwnerIdsA();
		$this->processOwnerId();
	}
	
	protected function beforeRender()
	{
		parent::beforeRender();
		$this->setTemplateOwnerItems();
	}
	
	protected function setTemplateOwnerItems()
	{
		if (!empty($this->ownerIds_a)) {
			$ownerItems = array();
			// @todo: possible performance bottleneck
			foreach ($this->ownerIds_a as $ownerId) {
				$ownerItems[$ownerId] = $this->model->findByOwner($ownerId);
			}
			
			$this->template->ownerItems = $ownerItems;
		} else {
			$this->template->ownerItems = array();
		}
	}

	
//	/**
//	 * list lightbox's files
//	 *
//	 * @param int lightbox id
//	 */
//	public function actionList($id)
//	{
//		$this->setTemplateOwnerItems();
//	}
	
	
	
	/**
	 * edit lightbox name using jEditable on frontend
	 * prints updated name and exits
	 *
	 * @param int lightbox id
	 * @param string new lightbox's name
	 * @return void
	 */
	public function editName($resource, $id, $name)
	{
		if ($this->user->isAllowed($resource, Acl::PRIVILEGE_EDIT)) {
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
	
	
	public function delete($resource, $id, $itemName)
	{
		try {
			if ($this->user->isAllowed($resource, Acl::PRIVILEGE_DELETE)) {
				$this->model->delete($id);
				$this->flashMessage("$itemName deleted.", self::FLASH_MESSAGE_SUCCESS);
			} else {
				$this->flashMessage(NOT_ALLOWED, self::FLASH_MESSAGE_ERROR);
			}
		} catch (DibiDriverException $e) {
			$this->flashMessage("$itemName could NOT be deleted.", self::FLASH_MESSAGE_ERROR);
		}

		$this->refresh(null, 'list');
	}

		
	/**
	 * load items by owner $this->ownerId
	 *
	 * @param string [lb | cp]
	 */
	public function handleLoadItemsByOwner($itemType)
	{
		$this->refresh("$itemType-$this->ownerId");
		$this->refresh(array(
			'ownersList',
			"$itemType-$this->ownerId",
		));
	}
}
