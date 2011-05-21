<?php
/**
 * inspired by Panda
 * http://forum.nette.org/cs/2626-2009-09-25-adresarova-struktura-a-moduly?pid=18673#p18673
 *
 */
class MyApplication extends Application
{
    protected $modules;

    /**
     * @var Cache
     */
    protected $cache;

    protected $hooks;
    
    /**
     * @var array of Route
     */
    protected $routes = array();

    /**
     * na pripojenie rout z bootstrapu
     *
     * @param array $routes
     */
    public function addRoutes(array $routes)
    {
    	$this->routes = array_merge($this->routes, $routes);
    }
    
    public function run()
    {
        $this->cache = Environment::getCache('Application');

        $modules = Environment::getConfig('modules');
        if (!empty($modules)) {
	        foreach ($modules as $module)
	            $this->loadModule($module);
        }

        $this->setupRouter();
//        $this->setupHooks();

        // Requires database connection
        $this->onRequest[] = array($this, 'setupPermission');

        // ...

        // Run the application!
        parent::run();

        $this->cache->release();
    }

    protected function loadModule($module)
    {
        $class = $module . 'Module';
        call_user_func(array($class, 'register'));
//        $this->modules[$module] = new $class;
    }

    protected function setupRouter()
    {
        if (Environment::isProduction() && isset($this->cache['router'])) {
            $this->setRouter($this->cache['router']);
        } else {
//          SiteRoute::initialize();

            $router = $this->getRouter();

            // pripojim routy z bootstrapu
            foreach ($this->routes as $route) {
            	$router[] = $route;
            }

            // Homepage
            $router[] = new Route('index.php', array(
				'module' => 'Front',
				'presenter' => 'Files',
				'action' => 'list',
//				'presenter' => 'Homepage',
//				'action' => 'default',
			), Route::ONE_WAY);
            
            // Modules routes
//                foreach ($this->modules as $module)
//                    $module->setupRouter($router);

			if (is_callable(array('AdminModule', 'createRoutes'))) {
				AdminModule::createRoutes($router);
			}
			FrontModule::createRoutes($router); // setups routes for submodules

			// Default route
			$router[] = new Route('[<lang [a-z]{2}>/]<module Front|Admin>/<presenter>/<action>/<id>', array(
				'module' => 'Front',
//				'presenter' => 'Homepage',
				'presenter' => 'Files',				
				'action' => 'list',
//				'presenter' => 'Page',
//				'action' => 'default',
//				'action' => 'home',
				'id' => NULL,
			));

            $this->cache->save('router', $router);
        }
    }

    // todo: nejak implementovat?
    public function setupPermission()
    {
            // ...
    }

    // todo: nejak implementovat?
    protected function setupHooks()
    {
        if (Environment::isProduction() && isset($this->cache['hooks'])) {
            $this->hooks = $this->cache['hooks'];
        } else {
            $this->hooks = new SiteHooks();

            foreach ($this->modules as $module)
                $module->setupHooks($this->hooks);

            $this->cache->save('hooks', $this->hooks);
        }
    }

    // ...
}
?>