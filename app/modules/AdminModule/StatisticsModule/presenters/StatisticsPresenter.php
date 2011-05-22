<?php

class Admin_StatisticsPresenter extends Admin_BasePresenter
{

	protected function startup()
	{
		parent::startup();
		$this->model = new StatisticsModel();
//		$this->config = Environment::getConfig('users');
	}

	
	/********************* view default *********************/
	
	
	public function renderDefault()
	{
		$this->template->projectStats = $this->model->findProjectStats();
	}
	

}
