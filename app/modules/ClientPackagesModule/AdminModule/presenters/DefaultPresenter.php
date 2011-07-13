<?php

class ClientPackages_Admin_DefaultPresenter extends ProjectsUsers_Admin_BasePresenter
{
	const ACL_RESOURCE = Acl::RESOURCE_PACKAGES_ADMINISTRATION;

	/** @var int */
	private $itemId;
	
	
	protected function startup()
	{
		parent::startup();
		$this->model = new ClientPackagesModel();
		$this->config = Environment::getConfig('clientPackages');
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->title = $this->translate('Packages Admin'); // optional, shown as heading and title of html page
		$this->template->description = $this->translate('Little piece of description for module Projects'); // optional, describes functionality of module
		$this->template->topHeading = ucfirst($this->getAction()) . ' Package';

		if ($this->getAction() === 'add' or $this->getAction() === 'edit') {
			$items = $this->model->findAll();
			$this->model->filterByNameOrSubtitle($items, $this->q);
			
			$vp = $this['itemPaginator'];
			$vp->selectItemsPerPage = array(8, 16, 24, 32, 40,);
			$vp->itemsPerPageAsSelect = true;
//				$vp->isResultsCountChangable = false;
	 		$vp->setDefaultItemsPerPage($this->config->defaultItemsPerPage);
	        $vp->paginator->itemCount = $items->count();
	        $vp->itemString = 'per page';
			$this->template->items = $items
									->toDataSource()
									->applyLimit($vp->paginator->itemsPerPage, $vp->paginator->offset)
									->fetchAll();
											
			// when paging refresh only items
			if ($vp->paginated || $vp->itemsPerPageChanged) {
				$this->invalidateControl('itemList');
			}
			
			$this->invalidateControl('topHeading');
		}
	}

	
  	
	/********************* views add & edit *********************/


	public function actionEdit($id)
	{
		$this->itemId = $id;
		$form = $this['itemForm'];
		$form['save']->caption = 'Edit Package';
		if (!$form->isSubmitted()) {
			$row = $this->model->find($id);
			if (!$row) {
				throw new BadRequestException(RECORD_NOT_FOUND);
			}
			$form->setDefaults($row);
			$this->invalidateControl('itemForm');
		}
		
		$this->setView('add');
	}


	/********************* component factories *********************/


	/**
	 * factory for creating client package
	 *
	 * @return MyAppForm
	 */
	protected function createComponentItemForm()
	{
		$form = new MyAppForm;
		$form->enableAjax();
		
		if (!is_null($this->getTranslator())) {
			$form->setTranslator($this->getTranslator());
		}

		$form->getRenderer()->wrappers['label']['requiredsuffix'] = " *";

		$form->addText('name', 'Package Name')
            ->addRule(Form::FILLED)
            ->addRule(Form::MIN_LENGTH, NULL, 2)
            ->addRule(Form::MAX_LENGTH, NULL, 70)
			->getControlPrototype()
				->data('nette-check-url', $this->link('checkAvailability!', array('__NAME__', 'package')))
				->class[] = 'checkAvailability';
				
        $form->addText('subtitle', 'Subtitle')
            ->addCondition(Form::FILLED)
	            ->addRule(Form::MIN_LENGTH, NULL, 2)
	            ->addRule(Form::MAX_LENGTH, NULL, 70);
            
	    $form->addSelect('owner_id', 'Package Owner', BaseModel::prepareSelect(UsersModel::findByRole(UsersModel::UL_CLIENT_ID), 'Client'))
             ->addRule(Form::FILLED);
             
        $form->addSubmit('save', 'Add')
			->getControlPrototype()->class[] = 'ok-button';

		$form->addSubmit('cancel', 'Cancel')
			->setValidationScope(NULL)
			->getControlPrototype()->class[] = 'cancel-button';

		$_this = $this;
		$form['cancel']
			->onClick[] = function() use($_this) {
				$_this->refresh(null, 'add', array(), true);
			};
			
		$form->onSubmit[] = callback($this, 'itemFormSubmitted');
		
		return $form;
	}

	
	public function itemFormSubmitted(MyAppForm $form)
	{
		try {
			if ($form['save']->isSubmittedBy()) {
				$values = $form->getValues();

				// insert
				if (is_null($this->getParam('id'))) {
					$id = $this->model->insert($values);
					$this->flashMessage('Client Package created.', self::FLASH_MESSAGE_SUCCESS);
				// update
				} else {
					$id = intval($this->getParam('id'));
					$this->model->update($id, $values);
					$this->flashMessage('Client Package updated.', self::FLASH_MESSAGE_SUCCESS);
				}
			}
		} catch (DibiDriverException $e) {
			// duplicate entry
			if ($e->getCode() === 1062) {
				$this->flashMessage("ERROR: " . $e->getMessage(), self::FLASH_MESSAGE_ERROR);
			} else {
				Debug::log($e);
				$this->flashMessage("ERROR: cannot save data!", self::FLASH_MESSAGE_ERROR);
			}
		} catch (OperationNotAllowedException $e) {
			$this->flashMessage(NOT_ALLOWED, self::FLASH_MESSAGE_ERROR);
			$this->redirect('this');
		}

		$form->resetValues();
		$this->refresh(null, 'add');
	}
	
	
	public function handleDelete($id)
	{
		try {
			$this->model->delete($id);
			$this->flashMessage('Client Package deleted', self::FLASH_MESSAGE_SUCCESS);
		} catch (OperationNotAllowedException $e) {
			$this->flashMessage(NOT_ALLOWED, self::FLASH_MESSAGE_ERROR);
		}

		$this->refresh('itemList', 'add');
	}
	
}
