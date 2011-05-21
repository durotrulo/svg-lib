<?php
/**
 * Sets File Permissions on FTP
 *
 * @author Matúš Matula
 * @license MIT
 */

class FtpPermissionPanel extends Object implements IDebugPanel
{

	/** @var array files or dirs to change permissions for */
	private $items = array();


	/**
	 * @param string|path $basedir
	 * @param array $ignoreMask
	 */
	public function __construct()
	{
		// "/*" means recursion
		$this->items = array(
			0777 => array(
				'/www/webtemp/*',
				'/var/*',
			),
		);
		$this->processRequest();
	}

	
	

	public function setPermissions($items)
	{
		$this->items = $items;
	}


	/**
	 * Handles an incomuing request and saves the data if necessary.
	 */
	public function processRequest()
	{
		$request = Environment::getHttpRequest();
		if ($request->isPost() && $request->isAjax() && $request->getHeader('X-FTP-Permission-Client')) {
			foreach ($this->items as $mode => $items) {
				foreach ($items as $item) {
					$path = ROOT_DIR . $item;
					if (!file_exists($path)) {
						throw new FileNotFoundException('File or Directory "' . $path . '" does NOT exist!');
					}
//					dump($item);
//					dump($mode);
					chmod($path, $mode);
				}
			}
			echo 'chmod set';
			exit();
		}
	}


	/**
	 * Renders HTML code for custom tab.
	 * IDebugPanel
	 * @return void
	 */
	public function getTab()
	{
		return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAAB3RJTUUH2wMTFiE3nairwwAAAkVJ
REFUeJxdUs9LVFEYPd9373tvxl9pZVhDaAWR/RaNNkW0yIiiRUgILcNVixa5Cdr2F7Rs0SKoXRRu
JBEX/sAIlIqZEnT8MYmmhuM4vpk37937tXiTlGd3v++ej8M5h6QKEEEEzCQi+AsiEhERw6zjiRYR
IiKK14h/x28REbEAbfmLCbcpNH6t16KtFWYMjMyMTuUe3Dl38VSLtbZ6X0QITLTwe+jH8lvXabh9
4TUzU6EYZLIbd6+fHJ6cDyqRsVWVFhEB33KvZlffJ9wDQbhJRExEWnOxVPk4kV1ayT97MZLNbTIz
gRW7RLywPmRhIltKus0AyFpLwE45DCqRVioyNunp0Ow42pnfeEdwC+UlP1gD4Oq6S8f7NREViuXB
8bn7N8/EygfHZsbSb65dbiyaYUUNVip5f5agPaehs+2xBsBEY1NL2dymVlwKop7u9qtd/QCGMxOu
rqtEW4pdglbsEEEDiIxNHarvvXVWAAIE9lP6a1uKI1N2NaoxIbYCGkB9rdfX07l/XzKWlJ5b+ZyZ
dpJKYEQssyZiCJgUxUEWiuXJLz9bjzT65bCj/fBuzBvbGSvRSOZJqbKmVQ2Tutc1oAEEoUln1/0g
KhSDY6kmz1Oeo0TswfrTAM4ffTj760Pen29tvqHYYwB1Ne52sTI+nfueXX/+cnRxOU9EILY2MjZs
T/W2NXcbW+pofeToGg0g4eqnfVfiyomI1hxbBygRa8UQKcUJK5GI/NfNPdhdhcbfCVabak9UCf9y
Ymd3u72HT0R/AEsJPV5RmFMAAAAAAElFTkSuQmCC">' .
			'Ftp Permission';
	}



	/**
	 * Renders HTML code for custom panel.
	 * IDebugPanel
	 * @return void
	 */
	public function getPanel()
	{
		ob_start();
		$template = new Template(dirname(__FILE__) . '/ftpPermission.panel.phtml');
//		$template->registerFilter(new LatteFilter());
		$template->render();
		return $cache['output'] = ob_get_clean();
	}



	/**
	 * Returns panel ID.
	 * IDebugPanel
	 * @return string
	 */
	public function getId()
	{
		return __CLASS__;
	}



	/**
	 * Registeres panel to Debug bar
	 */
	public static function register()
	{
		Debug::addPanel(new self);
	}

}
