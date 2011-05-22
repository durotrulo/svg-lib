<?php

class RichText_Front_DefaultPresenter extends Front_BasePresenter
{

	protected function startup()
	{
		parent::startup();
		$this->model = new RichTextModel();
		$this->config = Environment::getConfig('richText');
	}

	
	public function renderDetail($id)
	{
//		$this->setLayout('../../../FrontModule/templates/Login/@layout');
		$this->template->item = $this->model->find($id, $this->lang);
		
		if ($this->template->item === false) {
			throw new BadRequestException('Page does NOT exist!');
		}
		
		$this->template->title = $this->template->item->title;

	}
	
}
