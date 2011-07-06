<?php

/**
 * MultiConfig
 * 
 * @see http://forum.nette.org/cs/2634-nacteni-vice-konfiguracnich-souboru
 *
 */
class MultiConfig extends Object
{
	
	/**
	 * merges config files of each module imported via config.ini[modules] to one file and loads it
	 * considering current environment [dev, production, ...] - separate config file for each
	 *
	 * @param string|null  filepath
	 * @return Config
	 */
	public static function load($baseConfigFile = null)
	{
		if ($baseConfigFile === null) {
			$baseConfigFile = Environment::expand(Environment::getConfigurator()->defaultConfigFile);
		}
		
		$envName = Environment::getName();
		Environment::setVariable('tempDir', VAR_DIR . '/cache');
		
		$cache = Environment::getCache('config');
		$key = "config[$envName]";
        if (!isset($cache[$key])) {
        	// najviac casu zabera load, tak az tu, ked ho je treba
			$appConfig = Environment::loadConfig($baseConfigFile);
			$configs = array(Config::fromFile($baseConfigFile, $envName)->toArray());
			$configPaths = array($baseConfigFile);
			foreach ($appConfig->modules as $c) {
				$configPaths[] = $path = MODULES_DIR . "/{$c}Module/config.ini";
				if (file_exists($path)) {
					$configs[] = Config::fromFile($path, $envName)->toArray();
				}
			}
				
			$arrayConfig = call_user_func_array('array_merge_recursive', $configs);
        	
            $cache->save($key, $arrayConfig, array (
                'files' => $configPaths,
            ));
        }
		
		return Environment::loadConfig(new Config($cache[$key]));
	}
}