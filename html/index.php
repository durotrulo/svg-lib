<?php

// the identification of this site
define('SITE', '');

// absolute filesystem path to the web root
define('WWW_DIR', dirname(__FILE__));

// absolute filesystem path to the web root
define('ROOT_DIR', WWW_DIR . '/..');

// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/../app');

// absolute filesystem path to templates to be included
define('TPL_INC_DIR', APP_DIR . '/templates/include');

// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/../libs');

// absolute filesystem path to the var
define('VAR_DIR', WWW_DIR . '/../var');

// absolute filesystem path to the non-public data [original uploads, etc.]
define('DATA_DIR', WWW_DIR . '/../data');

// absolute filesystem path to the public data
define('PUBLIC_DATA_DIR', WWW_DIR . '/data');

// absolute filesystem path to the web modules
define('WEB_MODULES_DIR', APP_DIR . '/webmodules');

// absolute filesystem path to the modules
define('MODULES_DIR', APP_DIR . '/modules');

// load bootstrap file
require APP_DIR . '/bootstrap.php';