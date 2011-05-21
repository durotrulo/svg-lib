<?php
/**
 * Nella
 *
 * @copyright  Copyright (c) 2006, 2010 Patrik Votoček
 * @license    http://nellacms.com/license  New BSD License
 * @link       http://nellacms.com
 * @category   Nella
 * @package    Nella
 */

/**
 * Callback panel for nette
 *
 * @copyright	Copyright (c) 2008, 2010 Patrik Votoček
 * @author		Patrik Votoček
 * @package		Nette
 */
class CallbackPanel extends Object implements IDebugPanel
{
	const VERSION = "1.4";
	/** @var array */
	private $items = array();
	/** @var bool */
	private static $registered = FALSE;
	
	public function __construct(array $items = NULL)
	{
		$this->items = array(
			'--temp' => array('callback' => callback($this, 'clearDir'), 'name' => "Clear Temp", 'args' => array(Environment::getVariable('tempDir'))),
			'--log' => array('callback' => callback($this, 'clearDir'), 'name' => "Clear Logs", 'args' => array(Environment::getVariable('logDir'))),
//			'--sessions' => array('callback' => callback($this, 'clearDir'), 'name' => "Clear Sessions", 'args' => array(ini_get('session.save_path')))
			'--sessions' => array('callback' => callback($this, 'clearDir'), 'name' => "Clear Sessions", 'args' => array(Environment::getVariable('sessionDir')))
		);
		if (!empty($items))
			$this->items = array_merge($this->items, $items);
			
		$this->processRequest();
	}

	/**
	 * Returns panel ID.
	 * @return string
	 * @see IDebugPanel::getId()
	 */
	public function getId()
	{
		return "callback-panel";
	}

	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 * @see IDebugPanel::getTab()
	 */
	public function getTab()
	{
		return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAK8AAACvABQqw0mAAAABh0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzT7MfTgAAAY9JREFUOI2lkj1rVUEQhp93d49XjYiCUUFtgiBpFLyWFhKxEAsbGy0ErQQrG/EHCII/QMTGSrQ3hY1FijS5lQp2guBHCiFRSaLnnN0di3Pu9Rpy0IsDCwsz8+w776zMjP+J0JV48nrufMwrc2AUbt/CleMv5ycClHH1UZWWD4MRva4CByYDpHqjSgKEETcmHiHmItW5STuF/FfAg8HZvghHDDMpkKzYXScPgFcx9XBw4WImApITn26cejEAkJlxf7F/MOYfy8K3OJGtJlscKsCpAJqNGRknd+jO6TefA8B6WU1lMrBZ6fiE1R8Zs7hzVJHSjvJnNMb/hMSmht93IYIP5Qhw99zSx1vP+5eSxZmhzpzttmHTbcOKk+413Sav4v3J6ZsfRh5sFdefnnhr2Gz75rvHl18d3aquc43f1/BjaN9V1wn4tq6eta4LtnUCQuPWHmAv0AOKDNXstZln2/f3zgCUX8oFJx1zDagGSmA1mn2VmREk36pxw5NgzVqDhOTFLhjtOgMxmqVOE/81fgFilqPyaom5BAAAAABJRU5ErkJggg==">callback';
	}

	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 * @see IDebugPanel::getPanel()
	 */
	public function getPanel()
	{
		$items = $this->items;
		ob_start();
		require_once __DIR__ . "/callback.panel.phtml";
		return ob_get_clean();
	}

	/**
	 * Handles an incomuing request and saves the data if necessary.
	 */
	public function processRequest()
	{
		$request = Environment::getHttpRequest();
		if ($request->isPost() && $request->isAjax() && $request->getHeader('X-Callback-Client')) {
			$data = json_decode(file_get_contents('php://input'), TRUE);
			if (count($data) > 0) {
				foreach ($data as $key => $value) {
					if (isset($this->items[$key]) && isset($this->items[$key]['callback']) && $value === TRUE) {
						callback($this->items[$key]['callback'])->invokeArgs($this->items[$key]['args']);
					}
				}
			}
			exit;
		}
	}

	/**
	 * Clean dir
	 *
	 * @param  $dir
	 */
	public function clearDir($dir)
	{
		foreach (glob($dir."/*") as $path) {
			if (is_dir($path)) {
				$this->clearDir($path);
				@rmdir($path);
			}
			else
				@unlink($path);
		}
	}

	/**
	 * Register this panel
	 *
	 * @param array $items items for add to pannel
	 */
	public static function register(array $items = NULL)
	{
		if (self::$registered)
			throw new InvalidStateException("Callback panel is already registered");
		
		Debug::addPanel(new static($items));
//		Debug::addPanel(new self($items));
//		Debug::addPanel(new self);
		self::$registered = TRUE;
	}
}