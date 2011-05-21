<?php

/**
 * Allows inserting head [css,js] files into html page
 *
 * @author Matus Matula
 */
class MyWebloader extends Object {

	/** @var array associative array of js files to be inserted; $key = path to file, $value = filename */
	private static $js = array();

	/** @var array associative array of css files to be inserted; $key = path to file, $value = filename */
	private static $css = array();
	
	
	
	/**
     * adds js files to $this->controlsJs array
    *
     * @param string $key
     * @param string $path cesta k includovanemu suboru
     * @param string $path cesta k includovanemu suboru v ramci daneho modulu/komponenty
     */
    public static function addJsFile($key, $path, $srcPathsuffix = null)
    {
     	$key = Basic::addLastSlash($key) . '||' . $srcPathsuffix;
		if (!isset(self::$js[$key])) {
			self::$js[$key] = array();
		}
		if (!in_array($path, self::$js[$key])) {
			self::$js[$key][] = $path;
		}
    }
    

    /**
     * adds css files to self::$css
     *
     * @param string $key
     * @param string $path cesta k includovanemu suboru
     * @param string $path cesta k includovanemu suboru v ramci daneho modulu/komponenty
     */
    public static function addCssFile($key, $path, $srcPathsuffix = null)
    {
    	$key = Basic::addLastSlash($key) . '||' . $srcPathsuffix;
		if (!isset(self::$css[$key])) {
			self::$css[$key] = array();
		}
		if (!in_array($path, self::$css[$key])) {
			self::$css[$key][] = $path;
		}
    }
    
     
    public static function getCss()
    {
    	return self::$css;
    }
    
    
    public static function getJs()
    {
    	return self::$js;
    }
	
    
    /**
     * renders html tag <link> 
     *
     * @param CssLoader $cssLoader
     * @return string
     */
	public static function renderCss($cssLoader)
	{
		$cssLoader->setMedia('screen,projection,tv');
		$origTempUri = $cssLoader->tempUri;
		$origTempPath = $cssLoader->tempPath;
		ob_start();
		foreach (self::getCss() as $k=>$v) 
		{
			self::renderFile($cssLoader, $origTempUri, $origTempPath, $k, $v);
		}

		return ob_get_clean();
	}
	
	
	/**
     * renders html tag <script> 
     *
     * @param JsLoader $jsLoader
     * @return string
     */
	public static function renderJs($jsLoader)
	{
		$origTempUri = $jsLoader->tempUri;	
		$origTempPath = $jsLoader->tempPath;	
		ob_start();
		foreach (self::getJs() as $k=>$v) 
		{
			self::renderFile($jsLoader, $origTempUri, $origTempPath, $k, $v);
		}
		
		return ob_get_clean();
	}

	
	private static function renderFile(&$loader, $origTempUri, $origTempPath, $key, $files)
	{
		$controlId = basename(substr($key, 0, strpos($key, '||')));
		$srcPath = str_replace('||', '', $key);
		$dirname = $controlId . '/' . substr($key, strpos($key, '||') + 2);
		$dirname = str_replace('//', '/', $dirname);
//		dump($dirname);

		//todo: overit, ci to nebude robit adresare zase inde..zistit, cim to je, ze to na locale ide a na ostrom serveri nie
		// pouzit ked tak Environment::getVariable('webtempDir')
		Basic::mkdir($origTempPath . '/' . $dirname);

		$loader->sourcePath = $srcPath;
		$loader->tempUri = $origTempUri . '/' . $dirname;
		$loader->tempPath = $origTempPath . '/' . $dirname;
		$loader->render($files);
	}
}