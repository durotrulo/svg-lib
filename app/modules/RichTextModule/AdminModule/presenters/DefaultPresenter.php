<?php

class RichText_Admin_DefaultPresenter extends Admin_BasePresenter
{

	protected function startup()
	{
		parent::startup();
		$this->model = new RichTextModel();
		$this->config = Environment::getConfig('richText');
	}

	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->title = $this->translate('RichText'); // optional, shown as heading and title of html page
//		$this->template->description = $this->translate('Little piece of description for module RichText'); // optional, describes functionality of module
//		$this->template->description = 'Little piece of description for module RichText'; // optional, describes functionality of module
	}
	

	/********************* views add & edit *********************/


	public function renderEdit($id = 0)
	{
		$form = $this['itemForm'];
		if (!$form->isSubmitted()) {
			$row = $this->model->find($id);
			if (!$row) {
				throw new BadRequestException(RECORD_NOT_FOUND);
			}
			$form->setDefaults($row);
			
			$this->template->title = $row->label_en . ' - edit';
		}
		
		$this->setView('edit');
	}


	/********************* component factories *********************/


	protected function createComponentItemForm()
	{
		$form = new MyAppForm;
		
		if (!is_null($this->getTranslator())) {
			$form->setTranslator($this->getTranslator());
		}
		
		foreach (LangsModel::$supportedLangs as $lang) {
			$form->addRichTextArea("data_$lang", "Text-$lang")
				->addRule(Form::FILLED);
		}
		
		$form->addSubmit('save', 'Edit');
		$form->addSubmit('cancel', 'Cancel')->setValidationScope(NULL);
		$form->onSubmit[] = callback($this, 'itemFormSubmitted');
		
		return $form;
	}

	
	public function itemFormSubmitted(AppForm $form)
	{
		try {
			if ($form['save']->isSubmittedBy()) {
				$id = (int) $this->getParam('id');
				$values = $form->getValues();
				
				if ($id > 0) {
					$this->model->update($id, $values);
					$this->flashMessage('Data has been saved.', self::FLASH_MESSAGE_SUCCESS);
				} else {
					throw new Exception('$id must be positive integer!');
				}
			}
		} catch (DibiDriverException $e) {
			$this->flashMessage("ERROR: cannot save data!", self::FLASH_MESSAGE_ERROR);
		}

		$this->redirect('this');
	}
	
}
