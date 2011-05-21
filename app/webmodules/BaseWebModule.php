<?php

/**
 * Base Web Module
 *
 * @author Matus Matula
 */
class BaseWebModule extends Object
{
	
//	public function init()
//	{
//		Basic::mkdir($this->buildDestPath() . '/css/');
//		Basic::mkdir($this->buildDestPath() . '/js/');
//	}
	
	protected function buildSrcPath()
	{
		return WEB_MODULES_DIR . '/' . static::ID . '/';
	}
	
	protected function buildDestPath()
	{
		return Environment::getVariable('webtempDir') . '/' . static::ID;
	}
	
	/**
	 * @param string $path without starting slash
	 * @param string|null $path without starting slash
	 */
	protected function addCssFile($path, $srcPathsuffix = null)
	{
		MyWebloader::addCssFile($this->buildSrcPath(), $path, $srcPathsuffix);
	}
	
	/**
	 * @param string $path without starting slash
	 * @param string|null $path without starting slash
	 */
	protected function addJsFile($path, $srcPathsuffix = null)
	{
		MyWebloader::addJsFile($this->buildSrcPath(), $path, $srcPathsuffix);
	}
	
	/**
	 * copy $path to webmodulePublicDir/$path
	 */
	protected function copy($path)
	{
		$dest = Environment::getVariable('webtempDir') . '/' . static::ID . '/' . $path;
		$src = $this->buildSrcPath() . $path;
		if (!file_exists($dest)) {
			if (file_exists($src)) {
				Basic::copyr($src, $dest);
			} else {
				throw new ArgumentOutOfRangeException("Source path '$src' does NOT exist");
			}
		}
	}
	
}