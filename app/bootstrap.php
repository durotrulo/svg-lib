<?php

// ACL not allowed
define('NOT_ALLOWED', 'You are not allowed to perform this operation!');

// db error - dibiDriverException
define('OPERATION_FAILED', 'Operation could NOT be performed. Please, try again in few seconds');

// Step 1: Load Nette Framework
// this allows load Nette Framework classes automatically so that
// you don't have to litter your code with 'require' statements
require LIBS_DIR . '/Nette/loader.php';
require LIBS_DIR . '/Custom/utils.php';
require LIBS_DIR . '/Custom/MultiConfig.php';

//$config = Environment::loadConfig();
$config = MultiConfig::load();

//$loader = new RobotLoader();
//$loader->autoRebuild = true; // pokud nenajdu třídu, mám se znovusestavit?
//dump($loader);
//die();

// debug only
//Environment::setMode(Environment::PRODUCTION, 1 );

// Step 2: Configure environment
// 2a) enable Debug for better exception and error visualisation
Debug::$strictMode = TRUE; 	// determines whether to consider all errors as fatal
// todo: v novsej verzii sa predava uz iba adresar, nie nazov suboru
Debug::enable('213.215.67.27, 147.175.180.13, 127.0.0.1', Environment::getVariable('logDir') . '/err.log', 'matula.m@gmail.com');
//Debug::enable('213.215.67.27', Environment::getVariable('logDir') . '/err.log', 'matula.m@gmail.com');
//Debug::enable('213.215.67.27, 127.0.0.1', Environment::getVariable('logDir') . '/err.log', 'matula.m@gmail.com');
//dump(Debug::$productionMode);

//Environment::setMode(Environment::PRODUCTION, true);
//Environment::setMode(Environment::PRODUCTION, true);
//Environment::setMode(Environment::DEVELOPMENT, false);

// 2b) load configuration from config.ini file
//require LIBS_DIR . '/Custom/MultipleConfigurator.php';
//Environment::setConfigurator(new MultipleConfigurator());
//$config = Environment::loadConfig();

// 2c) check if cache, sessions and log directories are writable
if (@file_put_contents(Environment::expand('%tempDir%/_check'), '') === FALSE) {
	throw new Exception("Make directory '" . Environment::getVariable('tempDir') . "' writable!");
}

if (@file_put_contents(Environment::expand('%sessionDir%/_check'), '') === FALSE) {
	throw new Exception("Make directory '" . Environment::getVariable('sessionDir') . "' writable!");
}

if (@file_put_contents(Environment::expand('%logDir%/_check'), '') === FALSE) {
	throw new Exception("Make directory '" . Environment::getVariable('logDir') . "' writable!");
}


$session = Environment::getSession();
$session->setSavePath(Environment::getVariable('sessionDir'));
//Environment::getHttpResponse()->cookiePath = '/'; // toto tu treba, aby fungovali cookie..v novsej verzii je to uz fixnute
$session->setExpiration($config['session']['lifetime']);
if (!$session->isStarted()) {
	$session->start();
}

// Step 3: Configure application
// 3a) get and setup a front controller
$application = Environment::getApplication();
$application->errorPresenter = 'Front:Error';
if (Environment::isProduction() && Debug::$productionMode) {
	$application->catchExceptions = true;
} else {
	$application->catchExceptions = false;
}

dibi::connect(Environment::getConfig("database"));

// Step 4: Setup application router
//$router = $application->getRouter();
$routes = array();

/* MENU ITEMS */
//Route::addStyle('id');
//Route::setStyleProperty('id', Route::FILTER_TABLE, array(
//        'help' => '1',
//));
//$routes[] = new Route('[<lang [a-z]{2}>/]<id>/', array(
//	'module' => 'RichText:Front',
//	'presenter' => 'Default',
//	'action' => 'detail',
//	'id' => null,
//));

$application->addRoutes($routes);

FormMacros::register();


														/***** ***** **
														 *	Debug Bar *
														****** ***** **/
Debug::addPanel(new TodoPanel());

//PresenterTreePanel::register();

CallbackPanel::register();
FtpPermissionPanel::register();
//$callbacks = array();
////můj nový callback
//$callbacks[] = array(
//    'name' => "Rebuild RobotLoader Cache",
//    'callback' => callback(Environment::getService('Nette\Loaders\RobotLoader'), 'rebuild'),
//    'args' => array() //pole argumentů pro callback
//);
//CallbackPanel::register($callbacks);

Projects_AdminModule::register();


FormContainer::extensionMethod('addDatePicker', function (FormContainer $container, $name, $label = NULL) {
    return $container[$name] = new DatePicker($label);
});


														/***** ***** ******
														 *	Debug Bar END *
														****** ***** ******/
// Step 5: Run the application!
$application->run();
