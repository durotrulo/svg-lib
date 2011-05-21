Navigation
==========

Control pro Nette Framework usnadňující tvorbu menu a drobečkové navigace
upravene o moznost nastavenia aktivneho linku pre cely presenter/modul cez ifCurrent pomocou 3.parametra metody add() -> pouzivat NavigationControl namiesto Navigation

Autor: Jan Marek
Licence: MIT

Použití
-------

Továrnička v presenteru:

	protected function createComponentNavigation($name) {
		$nav = new NavigationControl($this, $name);
		$nav->setupHomepage("Úvod", $this->link("Homepage:"));
		$sec = $nav->add("Sekce", $this->link("Category:", array("id" => 1)));
		$article = $sec->add("Článek", $this->link("Article:", array("id" => 1)));
		$nav->setCurrent($article);
	}


Menu v šabloně:

	{widget navigation}


Drobečková navigace v šabloně:

	{widget navigation:breadcrumbs}
