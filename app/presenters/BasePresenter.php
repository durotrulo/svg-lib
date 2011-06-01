<?php
/**
 * Base class for all application presenters.
 *
 * @author Matus Matula
 */
abstract class BasePresenter extends Presenter
{
	const FLASH_MESSAGE_INFO = 'info';
	const FLASH_MESSAGE_WARNING = 'warning';
	const FLASH_MESSAGE_ERROR = 'error';
	const FLASH_MESSAGE_SUCCESS = 'success';
	
	/** @var mixed User|NULL  */
	protected $user = NULL;

	/** @var mixed int|NULL  */
	protected $userId;
	
	/** @var mixed IIdentity|NULL  */
	protected $userIdentity = NULL; 

	/** 
	 * @persistent 
	 * @todo: ak pouzivame iba 1 jazyk (config.multipleLangs == false) treba nastavit defaultny jazyk, inak MUSI byt default null, aby sa vedelo rozpoznat v $this->checkAndRegisterLang()
	 */
	public $lang = "en";
//	public $lang;
	
	/** @var ITranslator */
	public $translator = NULL;
	
	/** @var model for current presenter .. used in modules */
	protected $model;

	/** @var config for current presenter .. used in modules startup()*/
	protected $config;
		
	
	
    public $cache;


    /**
     * save current uri to provide 'start where you ended' after logout and login
     * called from startup() methods of presenters to be tracked
     */
    public function setLastRequest()
    {
		$uri = $this->getHttpRequest()->getUri()->getAbsoluteUri();
		$this->getHttpResponse()->setCookie('lastRequest', $uri, Tools::YEAR);
    }
	
    
	public function getModel()
	{
		return $this->model;
	}
	
	
	/**
	 * allows to call $this->user from within closure
	 *
	 * @return Nette/Web/User
	 */
	public function getUser()
	{
		return parent::getUser();
	}
	
	
	/**
	 * allows to call $this->userId from within closure
	 *
	 * @return int|NULL
	 */
	public function getUserId()
	{
		return $this->userId;
	}
	
	
	protected function startup()
	{
		parent::startup();

		//canonicalization for non-existing flash messages
      	if (!empty($this->params[self::FLASH_KEY]) && !$this->hasFlashSession()) {
        	$params = $this->params;
        	unset($params[self::FLASH_KEY]);
        	$this->redirect(IHttpResponse::S301_MOVED_PERMANENTLY, 'this', $params);
      	}
      	
//		if (Environment::getConfig('langs')->multipleLangs) {
			$this->setSupportedLangs();
			$this->checkAndRegisterLang();
//		}
		
		$this->registerTranslator();
		
		$this->registerUser();
		
		Environment::setVariable('basePath', substr(Environment::getVariable('baseUri'), 0, -1));
	}
	
	protected function registerUser(&$tpl = null)
	{
		if (!$this->user) {
			$this->user = $this->getUser();
			$this->userIdentity = $this->user->isLoggedIn() ? $this->user->getIdentity() : NULL;
			$this->userId = $this->userIdentity ? $this->userIdentity->data['id'] : NULL;
			
			// set ACL if needed (use cache optionally)
	        if (defined('ACL_CACHING') and ACL_CACHING) {
	            $this->cache = Environment::getCache();
	            if (!isset($this->cache['gui_acl'])) {
	                $this->cache->save('gui_acl', new Acl(), array(
	                    'files' => array(APP_DIR.'/config.ini'),
	                ));
	            }
	            $this->user->setAuthorizationHandler($this->cache['gui_acl']);
	        }
	        else {
	            $this->user->setAuthorizationHandler(new Acl());
	        }

		}
		
		if ($tpl instanceof Template) {
			$tpl->user = $this->user;
			$tpl->userId = $tpl->id_user = $this->userId;
			$tpl->userIdentity = $this->userIdentity;
		}
	}
	
	
	protected function createTemplate()
	{
		return $this->getEnrichedTemplate(parent::createTemplate());
	}
	
	
	/**
	 * attaches helpers, translator and other useful variables into template
	 * called also from BaseControl::createTemplate()
	 *
	 * @param ITemplate
	 * @return ITemplate
	 */
	public function getEnrichedTemplate(ITemplate $tpl)
	{
		$tpl->setTranslator($this->getTranslator());
		$tpl->registerHelper('html', array('Helpers', 'html'));
		$tpl->registerHelper('latte', array('Helpers', 'latte'));
		$tpl->registerHelperLoader('Helpers::functionLoader');

		$this->registerUser($tpl);

		$tpl->model = $this->model;
		
		$tpl->httpHost = 'http://' . $_SERVER['HTTP_HOST'];
		
		return $tpl;
	}
	
	protected function beforeRender()
	{
		parent::beforeRender();

		if (isset($this->model)) {
			$this->template->model = $this->model;
			$this->template->relativePath = $this->model->getRelativePath();
		}
		$this->setTemplateConfig();
		
		if (!isset($this->template->errors)) {
			$this->template->errors = array();
		}
		
		$this->loadWebModule('FlashMsgsWebModule', Environment::getConfig('webmodules')->flashMessages->skin);
	}
	
		
	protected function setTemplateConfig()
	{
		if ($this->config instanceof IteratorAggregate) {
			foreach ($this->config as $k => $v) {
				$this->template->$k = $v;
			}
		}
	}
	
	
	/**
	 * ads 'modules/' to paths where to find views
	 * ads reversed order of modules to paths where to find views, ie. from Admin:News: to NewsModule:AdminModule
	 *
	 * @param  string
	 * @param  string
	 * @return array
	 */
	public function formatTemplateFiles($presenter, $view)
    {
    	$paths = parent::formatTemplateFiles($presenter, $view);
    	
        $appDir = Environment::getVariable('appDir');
        $path = '/modules/' . str_replace(':', 'Module/', $presenter);
        $modulePresPath = $path . "Module/templates";
        // add presenter to module
//		$cleanPres = substr($presenter, strrpos($presenter, ':')+1);
//        $modulePresPath = $path . "Module/templates/$cleanPres";
        
        $pathP = substr_replace($path, '/templates', strrpos($path, '/'), 0);
        $path = substr_replace($path, '/templates', strrpos($path, '/'));
//        dump($path);
//        dump($pathP);
        
		/* reversed order for modules, ie. from Admin:News: to NewsModule:AdminModule*/
        $chunks = explode(':', $presenter);
        // if no module [e.g. it's ErrorPresenter from no module] return - @deprecated, should not be needed
        if (!isset($chunks[1])) {
        	return $paths;
        }
        $reversedModulePresenter = implode(':', array_merge(array($chunks[1], $chunks[0]), array_splice($chunks, 2)));
        $reversedModulePath = '/modules/' . str_replace(':', 'Module/', $reversedModulePresenter);
        $reversedModulePathP = substr_replace($reversedModulePath, '/templates', strrpos($reversedModulePath, '/'), 0);
        $reversedModulePath = substr_replace($reversedModulePath, '/templates', strrpos($reversedModulePath, '/'));
//        dump($reversedModulePathP);
//        dump($reversedModulePath);

//dump($path);
//dump("$appDir$modulePresPath/$view.phtml");
       
        return array_merge($paths, array(
        	"$appDir$modulePresPath/$view.phtml",
        	"$appDir$pathP/$view.phtml",
            "$appDir$pathP.$view.phtml",
            "$appDir$path/@global.$view.phtml",
        	"$appDir$reversedModulePathP/$view.phtml",
        	"$appDir$reversedModulePathP.$view.phtml",
        	"$appDir$reversedModulePath/@global.$view.phtml",
        ));
    }
    
    /**
	 * ads 'modules/' to path where to find layouts
	 * ads reversed order of modules to paths where to find layouts, ie. from Admin:News: to NewsModule:AdminModule
	 *
	 * @param  string
	 * @param  string
	 * @return array
	 */
    public function formatLayoutTemplateFiles($presenter, $layout)
    {
    	$paths = parent::formatTemplateFiles($presenter, $layout);
    	
    	$appDir = Environment::getVariable('appDir');
		$path = '/modules/' . str_replace(':', 'Module/', $presenter);
		$pathP = substr_replace($path, '/templates', strrpos($path, '/'), 0);
		$list = array(
			"$appDir$pathP/@$layout.phtml",
			"$appDir$pathP.@$layout.phtml",
			"{$appDir}{$path}Module/templates/@$layout.phtml", // /modules/FrontModule/Homepage/templates/@layout.phtml
		);
//		dump($list);die();
//		dump($path);
//		dump($pathP);
//		dump("$appDir$pathP/@$layout.phtml");
//		dump($presenter);

		// ked pouzivam aj reversed order, tak nechcem prebublat az do '/app/modules/templates/@layout.phtml', resp. '/app/templates/@layout.phtml', tam sa dostanu az po skuseni reversed order
//		while (($path = substr($path, 0, strrpos($path, '/'))) !== false) {
		while (($path = substr($path, 0, strrpos($path, '/'))) !== "/modules") {
			$list[] = "$appDir$path/templates/@$layout.phtml";
		}
		
		/* reversed order for modules, ie. from Admin:News: to NewsModule:AdminModule*/
        $chunks = explode(':', $presenter);
        // if no module [e.g. it's ErrorPresenter from no module] return - @deprecated, should not be needed
        if (!isset($chunks[1])) {
        	return $paths;
        }
        $reversedModulePresenter = implode(':', array_merge(array($chunks[1], $chunks[0]), array_splice($chunks, 2)));
        $reversedModulePath = '/modules/' . str_replace(':', 'Module/', $reversedModulePresenter);
        $reversedModulePathP = substr_replace($reversedModulePath, '/templates', strrpos($reversedModulePath, '/'), 0);
//        $reversedModulePath = substr_replace($reversedModulePath, '/templates', strrpos($reversedModulePath, '/'));
//        dump($reversedModulePath);
//        dump($reversedModulePathP);

		$list[] = "$appDir$reversedModulePathP/@$layout.phtml";
		$list[] = "$appDir$reversedModulePathP.@$layout.phtml";
		while (($reversedModulePath = substr($reversedModulePath, 0, strrpos($reversedModulePath, '/'))) !== FALSE) {
			$list[] = "$appDir$reversedModulePath/templates/@$layout.phtml";
		}
		
//		dump($path);
//		dump($list);die();
        return array_merge($paths, $list);
    }

	public function getTranslator()
	{
		if (is_null($this->translator)) {
			$this->translator = Environment::getService('Nette\ITranslator');
		}
		
		return $this->translator;
	}
	
	
	
							/************************* LANGUAGES CONCERNING *********************************/

	// public lebo volana aj z components							
	public function translate($message, $count = NULL)
	{
		return $this->getTranslator()->translate($message, $count);	
	}
	
	private function checkAndRegisterLang()
	{
		if (Environment::getConfig('langs')->multipleLangs) {
	//		dump( Environment::getHttpRequest()->getUri() );
			if ($this->getParam("lang") 
				&& LangsModel::isAllowed($this->getParam("lang"))
			) {
				$this->lang = $this->getParam("lang");
			} else {
				$this->redirect(301, ":Front:Homepage:", array(
					'lang' => Environment::getVariable("lang"),
				));
			}
		}
		
		$this->template->lang = $this->lang;
		Environment::setVariable('lang', $this->lang);
	}

	
	private function registerTranslator()
	{
		$rebuild = false;
		$this->template->setTranslator($this->getTranslator());
		$this->getTranslator()->buildDictionary(LangsModel::$supportedLangs, $rebuild);
	}
	
	
	/**
	 * set up supported langs from database, also set up language select and all store in cache
	 *
	 */
	private function setSupportedLangs()
	{
		$supportedLangs = Environment::getCache("SupportedLanguages");
		if ($supportedLangs['data'] === null) {
			$allLangs = LangsModel::getAll();
			$langs = array();
			$select = array();
			foreach ($allLangs as $v) {
				$langs[$v['id']] = $v['lang'];
				$select[$v['id']] = $v['name'];
			}
			
			$supportedLangs['data'] = array(
				'langs' => $langs,
				'select' => $select,
			);
		}
		
		LangsModel::setLangsSelect($supportedLangs['data']['select']);
		$this->template->langs = LangsModel::$supportedLangs = $supportedLangs['data']['langs'];
		
	}
	
							/************************* LANGUAGES CONCERNING END *********************************/
	
	
	/**
	 * invalidates flash messages every time
	 * translates all flash messages, DO NOT CALL TRANSLATE() IN FLASHMESSAGE() DIRECTLY!
	 *
	 * @param string $message
	 * @param string $type
	 */
	public function flashMessage($message, $type = self::FLASH_MESSAGE_INFO)
	{
		parent::flashMessage($this->translate($message), $type);
//		parent::flashMessage($message, $type);
		$this->invalidateControl('flashes');
	}
	
	
	/**
	 * exactly the same as in BaseControl
	 *
	 */
	public function redirect($code, $destination = NULL, $args = array())
	{

		if ($this->isAjax()) {
			// bez zbytocnych dalsich prietahov
			$this->sendPayload();
			
			/*
			$hasPayload = (array) $this->payload;
			//	if nothing new to send, exit..to avoid sending whole page back
			if ($this->isControlInvalid() === false and !$hasPayload) {
				$this->sendPayload();
			}
			*/
		} else {
			if (!is_numeric($code)) {
				$args = $destination;
				$destination = $code;
				if (empty($args)) {
					parent::redirect($destination);
				} else {
					parent::redirect($destination, $args);
				}
			} else {
				if (empty($args)) {
					if (is_null($destination)) {
						parent::redirect($code);
					} else {
						parent::redirect($code, $destination);
					}
				} else {
					parent::redirect($code, $destination, $args);
				}
			}
			
			parent::redirect($code, $destination, $args);
		}
	}
	
	
	/**
	 * @param string|array snippet names
	 * @param string link destination in format "[[module:]presenter:]view" or "signal!"
	 * @param array|mixed
	 * @param bool forward request when using ajax? - useful when processing form and need reload dependencies already loaded in actionXYZ()
	 * @see http://forum.nette.org/cs/6394-refreshovanie-ajaxoveho-obsahu
	 * @return void
	 */
	public function refresh($snippets = NULL, $destination = 'this', $args = array(), $forward = false)
	{
		BasePresenterControl::refresh($this, $snippets, $destination, $args, $forward);
	}
	
	
	/**
	 * sends error message to output /redirect or via payload/
	 *
	 * @param string
	 * @param string
	 * @param string
	 */
	protected function sendError($error, $dest = 'this', $flashType = self::FLASH_MESSAGE_ERROR)
	{
		$this->sendMsg($error, $flashType, $dest);
	}
	
	
	/**
	 * posle [chybovu] hlasku userovi nezavisle ci je ajax alebo nie
	 *
	 * @param string $msg hlaska
	 * @param string $type pridava sa ako class k $flashes
	 */
	protected function sendMsg($msg, $type = self::FLASH_MESSAGE_ERROR, $destination = 'this', $plink = false, $backlink = null)
	{
		BasePresenterControl::sendMsg($this, $msg, $type, $destination, $plink, $backlink);
	}
	
	
	public function actionLogout()
	{
		$this->getUser()->logout(true);
		$this->flashMessage('Úspešne ste sa odhlásili zo systému');
		$this->redirect(':Front:Homepage:');
	}
	

																			/***** ***** **
																			 *	WEBLOADER *
																			****** ***** **/
	
	protected function createComponentJs($name)
	{
	    $js = new JavaScriptLoader($this, $name);
	
	    $js->tempUri = Environment::getVariable("baseUri") . "webtemp";
	    $js->tempPath = WWW_DIR . "/webtemp";
	    $js->sourcePath = WWW_DIR . "/js";
	
	    // při development módu vypne spojování souborů
	    $js->joinFiles = Environment::isProduction();
	
	    // proměnné filtru je možné nastavit buď přímo v konstruktoru
	    $filter = new VariablesFilter(array(
	        "baseUri" => Environment::getVariable('baseUri'),
	    ));
	    $js->filters[] = $filter;

	    // v production módu zapne minimalizaci javascriptu
	    if (Environment::isProduction()) {
	        $js->filters[] = array($this, "packJs");
	    } else {
//	        $js->filters[] = array($this, "packJs");
//         	$js->joinFiles = true;
		    $js->throwExceptions = true;
	    }
	
	    return $js;
	}
	
	public function packJs($code)
	{
	    $packer = new JavaScriptPacker($code, "None");
	    return $packer->pack();
	}
	
	protected function createComponentCss($name)
	{
		$css = new CssLoader($this, $name);

	    // cesta na disku ke zdroji
	    $css->sourcePath = WWW_DIR . "/css";
	
	    // cesta na webu k cílovému adresáři
	    $css->tempUri = Environment::getVariable("baseUri") . "webtemp";
	
	    // cesta na disku k cílovému adresáři
	    $css->tempPath = WWW_DIR . "/webtemp";
	
	   	// při development módu vypne spojování souborů
	    $css->joinFiles = Environment::isProduction();
	    
	    if (Environment::isProduction()) {
//	    	die('hui');
		    $css->filters[] = function ($code) {
				return CssMin::minify($code);
			};
	    } else {
		    $css->throwExceptions = true;
	    }
	    
	    // proměnné filtru je možné nastavit buď přímo v konstruktoru
//	    $filter = new VariablesFilter(array(
//	        "cervena" => "red",
//	        "zelena" => "green",
//	    ));
//	    $css->filters[] = $filter;
	    
	    return $css;
	}

	
	public function renderWebloaderFiles()
	{
		echo '
			<div id="scripts">' . MyWebloader::renderJs($this['js']) .	'</div>
			<div id="styles">' . MyWebloader::renderCss($this['css']) . '</div>
		';
	}
																			/***** ***** ******
																			 *	WEBLOADER END *
																			****** ***** ******/

	/**
	 * nacita web module, parametre pre modul sa predavaju cez parametre c.2+
	 *
	 * @param string $module
	 */
	protected function loadWebModule($module)
	{
		$class = new $module;
		if (!is_callable(array($class, 'init'))) {
			throw new InvalidArgumentException("Invalid webModule callback.");
		}
		
//		$class->init();
		$args = array_slice(func_get_args(), 1);
		call_user_func_array(array($class, 'init'), $args);
	}

	
	
	//---------------------------------------------------------------------------
	//-- Secure Behaviour - secured from CSRF attacks
	//-- @see http://forum.nette.org/cs/2384-widget-predani-parametru-komponente-potvrzeni-smazani#p17654
	//-- i.e. 
	// /**
	// * @secured
	// */
	// public function handleEdit($id){}
	//---------------------------------------------------------------------------
	
	/**
	 * For @secure annotated signal handler methods checks if URL parameters has not been changed
	 * @param string $signal
	 * @throws BadSignalException
	 */
	public function signalReceived($signal)
	{
        $methodName = $this->formatSignalMethod($signal);
        if (method_exists($this, $methodName)) {
            $method = $this->getReflection()->getMethod($methodName);
//            if (Annotation::has($method, 'secured')) {
            if ($method->hasAnnotation('secured')) {
                $protectedParams = array();
                foreach ($method->getParameters() as $param) {
                    $protectedParams[$param->name] = $this->getParam($param->name);
                }
                if ($this->getParam('__secu') !== $this->createSecureHash($protectedParams)) {
                    throw new BadSignalException('Secured parameters are not valid.');
                }
        	}
        }

        parent::signalReceived($signal);
	}

	/**
	 * Generates link. If links points to @secure annotated signal handler method, additonal
	 * parameter preventing changing parameters will be added.
	 *
	 * @param string  $destination
	 * @param array|mixed $args
	 * @return string
	 */
	public function link($destination, $args = array())
	{
        if (!is_array($args)) {
            $args = func_get_args();
            array_shift($args);
        }

        $link = parent::link($destination, $args);
        $lastrequest = $this->presenter->lastCreatedRequest;

        /* --- Bad link --- */
        if ($lastrequest === NULL) {
            return $link;
        }

        /* --- Not a signal --- */
        if (substr($destination, - 1) !== '!') {
            return $link;
        }

        /* --- Exclude Form submits --- */
        if (substr($destination, - 8) === '-submit!') {
            return $link;
        }
        
        /* --- Only on same presenter --- */
        if ($this->getPresenter()->getName() !== $lastrequest->getPresenterName()) {
            return $link;
        }

        $signal = trim($destination, '!');
        $rc = $this->getReflection()->getMethod($this->formatSignalMethod($signal));

//        if (Annotations::has($rc, 'secured') === FALSE) {
        if ($rc->hasAnnotation('secured') === FALSE) {
            return $link;
        }

        $origParams = $lastrequest->getParams();
        $protectedParams = array();

        foreach ( $rc->getParameters() as $param) {
          	$protectedParams[$param->name] = ArrayTools::get($origParams, $this->getParamId($param->name));
        }
        $args['__secu'] = $this->createSecureHash($protectedParams);
        return parent::link($destination, $args);
	}
	
	
	/**
	 * Creates secure hash from array of arguments.
	 * @param array $param
	 * @return string
	 */
	protected function createSecureHash($params)
	{
     	$ns = Environment::getSession('securedlinks');
     	if ($ns->key === NULL) {
            $ns->key = uniqid();
        }
        $s = implode('|', array_keys($params)) . '|' . implode('|', array_values($params)) . $ns->key;

        return substr(md5($s), 4, 8);
	}
}
