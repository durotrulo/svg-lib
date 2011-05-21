<?php

/**
 * Homepage presenter.
 *
 * @author	   Matus Matula
 */
class Front_HomepagePresenter extends Front_BasePresenter
{
	
	protected function startup()
	{
		$this->redirect(':Front:Login:login');
	}
	
	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->isHomepage = true;
	}

	public function renderDefault()
	{
		$this->template->title = 'Homepage';
	}

}
