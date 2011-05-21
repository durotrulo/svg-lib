<?php

/**
 * Navigation node
 *
 * @author Jan Marek
 * @license MIT
 */
class NavigationNode extends ComponentContainer {

	/** @var string */
	public $label;

	/** @var string */
	public $url;

	/** @var bool */
	public $isCurrent = false;

	
	/**
	 * Add navigation node as a child
	 * @staticvar int $counter
	 * @param string $label
	 * @param string $url
	 * @param string $netteLink added - aby sa dalo testovat ifCurrent na cely presenter
	 * @return NavigationNode
	 */
	public function add($label, $url, $netteLink = null) {
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