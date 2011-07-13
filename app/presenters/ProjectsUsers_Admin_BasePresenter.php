<?php

/**
 * common ancestor for Admin Projects and Admin Users presenters
 * allows to check availability of project/user - if it exists
 * allows to search items via search form
 *
 */
abstract class ProjectsUsers_Admin_BasePresenter extends Admin_BasePresenter
{

	/** 
	 * @var string search query 
	 * @persistent 
	 */
	public $q;
	
	
	/**
	 * useful when cancel clicked when editing
	 *
	 */
	public function actionAdd()
	{
		$this->invalidateControl('itemForm');
		$form = $this['itemForm'];
		if (!$form->isSubmitted()) {
			$form->resetValues();
		}
	}
	
	
	/**
	 * check if requested name is available for given $type
	 * and send response in json
	 *
	 * @param string to test if available
	 * @param string type of item [project, user, package]
	 */
	public function handleCheckAvailability($name, $type)
	{
		Common::handleCheckAvailability($this, $name, $type);
	}

	
	protected function createComponentSearchForm($name)
	{
		$form = new MyAppForm($this, $name);
		$form->enableAjax();
			
		$form->addText('q')
			->setDefaultValue($this->q)
			->getControlPrototype()->setPlaceholder('Search');
			
		$form->addSubmit('search', 'Search')
			->getControlPrototype()->class('noJS noJS-tr');

		$_this = $this;
		$form->onSubmit[] = function(MyAppForm $form) use (&$_this) {
			if ($form['search']->isSubmittedBy()) {
				$values = $form->getValues();
				$_this->q = $values['q'];
			}

			$_this->validateControl('itemForm');
			$_this->refresh(array('itemList'), 'this');
		};

		return $form;
	}
	
	public function handleClearSearchForm()
	{
		$this->q = null;
		$this->validateControl('itemForm');
		$this->refresh(array('searchForm', 'itemList'), 'this');
	}
	
}
