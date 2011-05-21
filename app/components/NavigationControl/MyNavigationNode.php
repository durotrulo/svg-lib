<?php

/**
 * Navigation node
 * 	pridana moznost nastavenia aktivneho linku pre cely presenter/modul cez ifCurrent pomocou 3.parametra metody add()
 * 
 * @author Jan Marek, Matus Matula
 * @license MIT
 */
class MyNavigationNode extends NavigationNode
{
	/**
	 * Add navigation node as a child
	 * @staticvar int $counter
	 * @param string $label
	 * @param string $url
	 * @param string $netteLink added - aby sa dalo testovat ifCurrent na cely presenter
	 * @return NavigationNode
	 */
	public function add($label, $url, $netteLink = null)
	{
		$navigationNode = new self;
		$navigationNode->label = $label;
		$navigationNode->url = $url;

		static $counter;
		$this->addComponent($navigationNode, ++$counter);

		/*added*/
		$uri = Environment::getHttpRequest()->getOriginalUri()->getPath();
		if ($netteLink) {
			$presenter = Environment::getApplication()->getPresenter();
			try {
				$presenter->link($netteLink);
			} catch (InvalidLinkException $e) {}; 
			
			$navigationNode->isCurrent = $presenter->getLastCreatedRequestFlag("current");
		} else {
			$navigationNode->isCurrent = ($url==$uri);
		}
		/*added end*/
		
		return $navigationNode;
	}
}