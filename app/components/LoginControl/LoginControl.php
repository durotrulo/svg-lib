<?php

/**
 * Login control
 * 
 * generates login form and handles whole authentication process
 * 
 * 
 * options:
 * 	multiple rows / inline visual layout possible by css change
 *
 * @author Matus Matula
 */
class LoginControl extends BaseControl
{
	/** @var string redirect destination after logging in */
	public $redirectTo = 'this';

	/** @var bool use protection? */
	public $useProtection = false;
	
	/** @var bool use label over? */
	public $useLabelOver = true;
	
	/** @var bool use remember checkbox? */
	public $useRemember = false;
	
	/** @var bool use table layout? */
	public $useTableLayout = false;
	
	/** @var string how long user stays logged in when not remembered */
	public $shortExpiration = '+ 20 minutes';
	
	/** @var string how long user stays logged in when remembered */
	public $longExpiration = '+ 14 days';
	
	/** @var bool enable autocompletion? */
	public $useAutocomplete = false;
	
	/** @var bool enable ajax? */
	public $useAjax = false;
		
											/*	STRING FOR TRANSLATIONS, BACK COMPATIBILITY MODE WHEN NO TRANSLATOR USED IN OLDER PROJECTS */
	/** @var string in submit button */
	public $labels = array(
		'remember' => 'Remember me on this computer',
		'username' => 'Username',
		'password' => 'Password',
		'submit' => 'Login',
	);
	
	
	protected function createComponentLoginForm($name)
  	{
	    $form = new MyAppForm($this, $name);
	    if ($this->useAjax) {
			$form->enableAjax();
	    }
	    
		if (!$this->useAutocomplete) {
		    $form->getElementPrototype()
				->autocomplete('off');
		}
					
	    if (isset($this->getPresenter()->translator)) {
		    $form->setTranslator($this->getPresenter()->translator);
	    }
	
	    if ($this->errorsAsFlashMessages) {
	    	$form->setErrorsAsFlashMessages();
	    }
	    
	    $form->addClass('loginFrm');
			
	    $form->addText('username', $this->labels['username'])
	     	->addRule(Form::FILLED, 'Enter your username!');
	
	    $form->addPassword('password', $this->labels['password'])
	        ->addRule(Form::FILLED, 'Enter your password!');
		
	    if ($this->useRemember) {
		    $form->addCheckbox('remember', $this->labels['remember']);
	    }
	
	    //	if using labelOver we need to add class to ensure label is visible with javascript disabled
	    if ($this->useLabelOver) {
			$form['password']->getLabelPrototype()->class('onlyJS');
			$form['username']->getLabelPrototype()->class('onlyJS');
	    }

		$form->addHidden('key');
		$form->addHidden('anchor');
		
		$form->addSubmit('ok', $this->labels['submit'])
			->getControlPrototype()->class[] = 'ok-button';
	
        if ($this->useProtection) {
			$form->addProtection('Form validity time expired. Please send the form again.');
        }
	
	    $form->onSubmit[] = callback($this, 'loginFormSubmitted');
	
	    return $form;
  	}
  	
  	
  	public function loginFormSubmitted($form)
  	{
  		try {
			$values = $form->values;
			if ($this->useRemember && $values['remember']) {
				$this->getUser()->setExpiration($this->longExpiration, FALSE);
			} else {
				$this->getUser()->setExpiration($this->shortExpiration, TRUE);
			}
			$this->getUser()->login($values['username'], $values['password']);
	      	$this->flashMessage('Successfuly logged in!', self::FLASH_MESSAGE_SUCCESS);
	      	
	      	//	ak je kam sa vracat, tak tam hodim aj kotvu .. na hobby je to vzdy rating [ci uz rating alebo comment]
			if ($values['key']) {
            	$this->getApplication()->restoreRequest($values['key'], $values['anchor']);
//                $this->getApplication()->restoreRequest($values['key'], 'anchor-rating');
//                $this->getApplication()->restoreRequest($values['key']);
			}
			 
			//	ak zhnije session, tak hodime tu
//	      	$this->redirect($this->redirectTo);
//	      	$this->getPresenter()->redirect($this->redirectTo);
			$this->refresh(null, $this->redirectTo);
		} catch (AuthenticationException $e) {
			$errMsg = $e->getMessage();
			if ($this->errorsAsFlashMessages) {
		      	$this->flashMessage($errMsg, self::FLASH_MESSAGE_ERROR);
		      	$this->refresh(null, 'this');
			} else {
				$form->addError($errMsg);
			}
		}
  	}
  	
  	
  	public function render($tplFile = NULL)
  	{
  		$this->setWebloaderPaths(); // kvoli tomu, ze sa vola komponenta viac krat, tak sa cesta k webloaderu nastavena v construct moze prepisat, treba to znova nastavit
  		
	    if ($this->useLabelOver) {
	  		$this->addCssFiles('labelOver.css');
	  		$this->addJsFiles('labelOver.js', 'labelOverReady.js');
	    }
	    
		$tpl = $this->createTemplate();
		if (!is_null($tplFile)) {
			$tpl->setFile(dirname(__FILE__) . $tplFile . '.phtml');
		} elseif ($this->useTableLayout) {
			$tpl->setFile(dirname(__FILE__) . '/tableLayout.phtml');
		} else {
			$tpl->setFile(dirname(__FILE__) . '/divLayout.phtml');
		}
		
  		$tpl->count = self::$count;
  		$tpl->render();
  	}
  	
  	/** NOT NEEDED, CSS CHANGE IS SUFFICIENT 
  	public function renderInline()
  	{
  		$this->render('inline');
  	}

  	public function renderMultipleRows()
  	{
  		$this->render('multipleRows');
  	}
  	**/
}