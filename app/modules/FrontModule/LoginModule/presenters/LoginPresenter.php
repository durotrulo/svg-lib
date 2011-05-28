<?php

/**
 * Login / logout presenters.
 *
 * @author Matus Matula
 */
//class Front_LoginPresenter extends Front_BasePresenter
class Front_LoginPresenter extends BasePresenter
{
		
	/** @var links to redirect to, based on user's role */
	private $redirectTo = array(
//		'admin' => ':Admin:Homepage:',
//		'user' => ':Front:Homepage:',
//		'guest' => ':Front:Homepage:', // default
		'guest' => 'this', // default
	);

	
	/**
	 * @link how to http://forum.nettephp.com/cs/2175-nemohu-zprovoznit-re-storerequest?pid=14721#p14721
	 */
	public function renderLogin($key = NULL, $anchor = NULL) 
	{
		if ($key) {
           	$this->getComponent('loginForm')->getComponent('loginForm')->setDefaults(array('key' => $key, 'anchor' => $anchor));
        }
	}


	/**
	 * uživatele přesměruje, pokud už je přihlášený (aby se nemusel přihlašovat ve všech tabech, ale jen v jednom a ostatní jen refreshnout)
	 *
	 * @param string|null
	 * @param mixed|null
	 */
	public function actionLogin($key, $anchor = NULL) 
	{
		if ($this->getUser()->isLoggedIn()) {
            if ($key) {
                $this->getApplication()->restoreRequest($key, $anchor);
            }
            
			// provide 'start where you ended' after logout and login
            $lastRequest = $this->getHttpRequest()->getCookie('lastRequest');
            if (!empty($lastRequest)) {
            	$this->redirectUri($lastRequest);
            }
            
            // pre pripad, ze zhnila session a $key uz nie je platny
            //todo: docasne riesenie
			if (count(array_intersect($this->user->getRoles(), array('admin', 'superadmin', 'designer', 'projectManager'))) > 0) {
	            $this->redirect(':Front:Files:list');
            } else {
	            $this->redirect('logout');
            }
            
            $this->getRedirectToByRole(':Front:Homepage:');
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

		$c->redirectTo = $this->getRedirectToByRole('this');
		
		/*
		
		*/
		
		return $c;
	}
	
	
	/**
	 * get Presenter Link to redirect to when logged, based on user's role
	 *
	 * @param mixed default link
	 * @return string Presenter Link
	 */
	private function getRedirectToByRole($default = NULL)
	{
		if ($this->user->isLoggedIn()) {
			foreach ($this->user->getRoles() as $role) {
				if (isset($this->redirectTo[$role])) {
					return $this->redirectTo[$role];
				}
			}
		} else {
			return $this->redirectTo['guest'];
		}
		
		return $default;
	}

}
