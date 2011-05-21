<?php

/**
 * Navigation
 *
 * @author Jan Marek, Matus Matula
 * @license MIT
 */
class NavigationControl extends Navigation
{
	/**
	 * Add navigation node as a child
	 * @param string $label
	 * @param string $url
	 * @param string $netteLink added - aby sa dalo testovat ifCurrent na cely presenter
	 * @return NavigationNode
	 */
	public function add($label, $url, $netteLink = null)
	{
		return $this->getComponent("homepage")->add($label, $url, $netteLink);
	}

	
	/**
	 * Homepage factory
	 * @param string $name
	 */
	protected function createComponentHomepage($name)
	{
		new MyNavigationNode($this, $name);
	}

}