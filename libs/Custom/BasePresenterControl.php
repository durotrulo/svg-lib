<?php

class BasePresenterControl extends Object 
{
	
	/**
	 * posle [chybovu] hlasku userovi nezavisle ci je ajax alebo nie
	 *
	 * @param string $msg hlaska
	 * @param string $type pridava sa ako class k $flashes
	 */
	public static function sendMsg(&$_this, $msg, $type = self::FLASH_MESSAGE_ERROR, $destination = 'this', $plink = false, $backlink = null)
	{
		$presenter = $_this->getPresenter();
		
		if ($presenter->isAjax()) {
			$presenter->payload->actions = array(
				$type => $_this->translate($msg),
			);
			
			$presenter->sendPayload();
		} else {
			$presenter->flashMessage($msg, $type);
			//	ak mame ulozeny kluc, kam sa mame vratit, ideme tam
			if ($backlink) {
				Environment::getApplication()->restoreRequest($backlink);
			} elseif ($plink) {
				$presenter->redirect($destination);
			} else {
				$_this->redirect($destination);
			}
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
	public static function refresh(&$_this, $snippets = NULL, $destination = 'this', $args = array(), $forward = false)
	{
        if ($_this->getPresenter()->isAjax()) {
            if ($snippets) {
                foreach ((array) $snippets as $snippet) {
                    $_this->invalidateControl($snippet);
                }
            } else {
                $_this->invalidateControl();
            }
            if ($forward) {
            	$_this->forward($destination, $args);
            }
        } else if ($destination) {
            $_this->redirect($destination, $args);
        }
	}
}