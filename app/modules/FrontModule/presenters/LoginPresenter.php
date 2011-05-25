<?php

/**
 * Login / logout presenters.
 *
 * @author Matus Matula
 */
//class Front_LoginPresenter extends Front_BasePresenter
class Front_LoginPresenter extends BasePresenter
//class Front_LoginPresenter extends Front_PagePresenter
{
	
	/**
	 * @link how to http://forum.nettephp.com/cs/2175-nemohu-zprovoznit-re-storerequest?pid=14721#p14721
	 */
	public function renderLogin($key = NULL, $anchor = NULL) 
	{
		if ($key) {
           	$this->getComponent('loginForm')->getComponent('loginForm')->setDefaults(array('key' => $key, 'anchor' => $anchor));
//           	$this->getComponent('loginForm')->setDefaults(array('key' => $key, 'anchor' => $anchor));
        }
	}


	public function actionLogin($key, $anchor = NULL) 
	{
//		uživatele přesměruje, pokud už je přihlášený (aby se nemusel přihlašovat ve všech tabech, ale jen v jednom a ostatní jen refreshnout):
		if ($this->getUser()->isLoggedIn()) {
            if ($key) {
                $this->getApplication()->restoreRequest($key, $anchor);
            }

            // pre pripad, ze zhnila session a $key uz nie je platny
            //todo: docasne riesenie
			if (count(array_intersect($this->user->getRoles(), array('admin', 'superadmin', 'designer', 'projectManager'))) === 0) {
	            $this->redirect('logout');
            }
            
            $this->redirect(':Front:Files:list');
//            $this->redirect(':Front:Homepage:');
        }
	}
	
	/**
	 * Login form component factory.
	 * @return mixed
	 */
	protected function createComponentLoginForm()
	{
		$c = new LoginControl();
		$c->useRemember = true;
		$c->useTableLayout = true;
		$c->useLabelOver = false;
//		$c->useAjax = true;
		return $c;
	}

}
