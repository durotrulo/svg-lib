<?php

/**
 * ancestor of all my controls
 * 
 * sets 
 * 	current user as $this->user
 * 	dibi as $this->db
 * 
 * implements 
 * 	$this->translate
 *  webloader scripts and styles inserting via MyWebloader
 *
 * @author Matus Matula
 */
class BaseControl extends Control
{
	const FLASH_MESSAGE_INFO = 'info';
	const FLASH_MESSAGE_WARNING = 'warning';
	const FLASH_MESSAGE_ERROR = 'error';
	const FLASH_MESSAGE_SUCCESS = 'success';
	
	/** @var bool print errors as flash messages? */
//	public static $errorsAsFlashMessages = true;
	public $errorsAsFlashMessages = true;
	
	/** @var bool show control flash messages as presenter's flash messages */
	protected $usePresenterFlashes = true;
	
	/** @var DibiConnection */
	protected $db;
	
	/** @var User | NULL */
	protected $user = NULL;

	/** @var int number of rendered login components used for unique identification */
	protected static $count = 0;
	
	/** @var string path to scripts, usually __DIR__ */
	protected static $webloaderSrcPath = NULL;
	
	/** @var string path to scripts used by webloader */
	protected static $webloaderDestPath = NULL;
	
	/** @var string uri to scripts used by webloader */
	protected static $webloaderDestUri = NULL;
	
	/** @var PresenterComponent holds presenter control is attached to */
	private static $presenter = null;
	
	public function __construct(IComponentContainer $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);
		$this->db = dibi::getConnection();
		$this->user = $this->getUser();

		self::$count++;
//		$this->setWebloaderPaths();
	}
	
	// kvoli tomu, ze sa vola komponenta viac krat, tak sa cesta k webloaderu nastavena v construct moze prepisat, treba to znova nastavit
	protected function setWebloaderPaths()
	{
		// sets webloader source path to file calling this one ..
		$backtrace = debug_backtrace();
		self::$webloaderSrcPath = $callingFile = dirname($backtrace[0]['file']);
		
		// sets uri to webtemp control dir
		$webtempDir = Environment::getVariable('webtempDir');
		$baseUri = Environment::getVariable('baseUri');
		$webtempDirSlashed = str_replace('\\', '/', $webtempDir);
		$webtempDirname = substr($webtempDirSlashed, strpos($webtempDirSlashed, $baseUri) + strlen($baseUri));
		$controlName = basename(self::$webloaderSrcPath);
		
		self::$webloaderDestPath = $webtempDir . '/' . $controlName;
		self::$webloaderDestUri = $baseUri . $webtempDirname . '/' . $controlName;
		
		// create automatically dir for scripts even if not used
		$dest = $webtempDir . '/' . $controlName;
		Basic::mkdir($dest);
		Basic::mkdir($dest . '/js');
		Basic::mkdir($dest . '/css');
	}
	
	
	/**
	 * returns relative path from WWW_DIR
	 *
	 * @param string $path
	 * @return string
	 */
	public function getRelativePath($path)
	{
		$pathSlashed = str_replace('\\', '/', $path);
		$relativePath = substr($pathSlashed, strpos($pathSlashed, WWW_DIR) + strlen(WWW_DIR));
		return $relativePath;
	}
	
	
	/**
	 * enables show flash messages like presenter's messages and invalidates them every time
	 * translates all flash messages, DO NOT CALL TRANSLATE() IN FLASHMESSAGE() DIRECTLY!
	 *
	 * @param string $message
	 * @param string $type
	 */
	public function flashMessage($message, $type = self::FLASH_MESSAGE_INFO)
	{
		$message = $this->translate($message);
		if ($this->usePresenterFlashes) {
			$this->getPresenter()->flashMessage($message, $type);
//			$this->presenter->invalidateControl('flashes'); // done automatically
		} else {
			parent::flashMessage($message, $type);
			$this->invalidateControl('flashes');
		}
		/*
		if ($this->presenter->isAjax()) {
//			$this->invalidateControl('flashes');
			$this->presenter->invalidateControl('flashes');
		}*/
	}
	
	
	/**
	 * exactly the same as in BasePresenter
	 *
	 */
	public function redirect($code, $destination = NULL, $args = array())
	{
		if ($this->presenter->isAjax()) {
			// bez zbytocnych dalsich prietahov
			$this->sendPayload();
			
			/*
			$hasPayload = (array) $this->presenter->payload;
			//	if nothing new to send, exit..to avoid sending whole page back
			if ($this->presenter->isControlInvalid() === false and $this->isControlInvalid() === false and !$hasPayload) {
				$this->presenter->sendPayload();
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
	
																			/***** ***** ***** *****
																			 *	WEBLOADER + TEMPLATE *
																			****** ***** ***** *****/
																			
	/**
	 * called directly from control's method render()
	 *
	 */
	protected function addJsFiles()
	{
		self::addJsScript(func_get_args());
	}
	
	
	/**
	 * called directly from control's method render()
	 *
	 */
	protected function addCssFiles()
	{
		self::addCssStyle(func_get_args());
	}
	
 	protected function attached($presenter)
 	{
 		parent::attached($presenter);
 		self::$presenter = $presenter;
 	}
	
	
	protected function createTemplate()
	{
		LatteMacros::$defaultMacros["addJs"] = '<?php %BaseControl::macroAddJsFiles% ?>';
		LatteMacros::$defaultMacros["addCss"] = '<?php %BaseControl::macroAddCssFiles% ?>';
		return $this->presenter->getEnrichedTemplate(parent::createTemplate());
    }
    
    
    /**
	 * copy $path to webmodulePublicDir/$path
	 * @param string
	 * @param bool is copied file mandatory?
	 */
	protected function copy($path, $need = true)
	{
		$dest = Basic::addLastSlash(self::$webloaderDestPath) . $path;
		$src = Basic::addLastSlash(self::$webloaderSrcPath) . $path;
		if (!file_exists($dest)) {
			if (file_exists($src)) {
				Basic::copyr($src, $dest);
			} elseif ($need) {
				throw new ArgumentOutOfRangeException("Source path '$src' does NOT exist");
			}
		}
	}

    /**
     * adds js files to presenter's controlsJs array
     *
     * @param mixed $files
     */
    public static function addJsScript($files)
    {
    	if (is_null(self::$presenter)) {
			throw new Exception('Cannot add css files before control is attached to presenter');
		}
		
		// called from macros
//		if (func_num_args() > 1) {
		if (!is_array($files)) {
			$files = func_get_args();
		}
		
    	foreach ($files as $v) {
    		if (is_array($v)) {
	    		MyWebloader::addJsFile(self::$webloaderSrcPath, $v[0], $v[1]);
    		} else {
	    		MyWebloader::addJsFile(self::$webloaderSrcPath, $v);
    		}
    	}
    }
    

    /**
     * adds css files to presenter's controlsCss array
     *
     * @param mixed $files
     */
    public static function addCssStyle($files)
    {
    	if (is_null(self::$presenter)) {
			throw new Exception('Cannot add css files before control is attached to presenter');
		}
		
		// called from macros
//		if (func_num_args() > 1) {
		if (!is_array($files)) {
			$files = func_get_args();
		}
		
    	foreach ($files as $v) {
    		if (is_array($v)) {
	     		MyWebloader::addCssFile(self::$webloaderSrcPath, $v[0], $v[1]);
    		} else {
	     		MyWebloader::addCssFile(self::$webloaderSrcPath, $v);
    		}
    	}
    }
    
    public static function macroAddJsFiles($files)
    {
   		return "BaseControl::addJsScript($files);";
    }
    
    public static function macroAddCssFiles($files)
    {
    	return "BaseControl::addCssStyle($files);";
    }
    
 
																			/***** ***** ***** ***** *****
																			 *	WEBLOADER + TEMPLATE END *
																			****** ***** ***** ***** *****/
	   
    protected function translate($message, $count = NULL)
	{
		return $this->presenter->translate($message, $count);
	}
	
	
	/**
	 * @return User
	 */
	protected function getUser()
	{
		return Environment::getUser();
	}
	
	/**
	 * @return Application
	 */
	protected function getApplication()
	{
		return Environment::getApplication();
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
//            if (Annotations::has($method, 'secured')) {
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
	
	//---------------------------------------------------------------------------
	//-- Secure Behaviour - secured from CSRF attacks
	//-- END
	//---------------------------------------------------------------------------
	

	
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
	
}