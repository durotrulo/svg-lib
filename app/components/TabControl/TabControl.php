<?php

/**
 * This source file is subject to the "New BSD License".
 *
 * For more information please see http://nettephp.com
 *
 * @author     Jan Kuchař
 * @copyright  Copyright (c) 2009 Jan Kuchař (http://mujserver.net)
 * @license    New BSD License
 * @link       http://nettephp.com/extras/tabcontrol
 */

/* Vyžaduje nette >= rev. 465 */

/**
 * TabControl class
 *
  * show off @property, @property-read, @property-write
  * @property-read   array           $tabsForDraw    Tabs for redraw
 * @property-read   ArrayIterator   $tabs           Tabs
 */
class TabControl extends Control {

	/**************************************************************************/
	/*                               Variables                                */
	/**************************************************************************/

	/**
	 * Container of tabs
	 * @var Html
	 */
	public $tabContainer;



	/**
	 * Active tab name
	 * @var string
	 * @persistent
	 */
	public $tab;



	/**
	 * SELECT: First tab (default)
	 */
	const SELECT_FIRST = -1;



	/**
	 * Redraw all
	 */
	const REDRAW_ALL = -1;



	/**
	 * Redraw current
	 */
	const REDRAW_CURRENT = null;



	/**
	 * Mode: do not use ajax
	 */
	const MODE_NO_AJAX=0;



	/**
	 * Mode: preload all tabs when page loads
	 */
	const MODE_PRELOAD=1;



	/**
	 * Mode: load tab when tab is clicked
	 */
	const MODE_LAZY=2;



	/**
	 * Reload tab content every time, when tab is clicked
	 */
	const MODE_RELOAD=3;



	/**
	 * Mode: detects the best mode
	 */
	const MODE_AUTO=null;



	/**
	 * Mode (see MODE_xxx constants)
	 * @var int
	 */
	public $mode=self::MODE_AUTO;



	/**
	 * Text what is displayed while loading content of the tab
	 * @var string|null
	 */
	public $loaderText="Načítám...";



	/**
	 * jQuery Tabs options
	 * @var String
	 * @link http://jqueryui.com/demos/tabs/#options
	 */
	public $jQueryTabsOptions = "{}";



	/**
	 * Component where you have handlers
	 * @var PresenterComponent
	 */
	public $handlerComponent;



	/**
	 * Internal buffer for JavaScript
	 * @var array
	 */
	private $JavaScript = array();



	/**
	 * Tab object identificator (JavaScript)
	 * @var string
	 */
	public $DOMtabsID;



	/**
	 * Tabs to be drawed
	 * @var array|null
	 */
	private $tabsForDraw;



	/**
	 * Add support for sorting tabs (requires jQuery UI Sortable)
	 * @var bool
	 */
	public $sortable = false;



	/**
	 * When tabs order is chaged
	 * @var array
	 */
	public $onOrderChange = array();



	/**
	 * Callback - save tabs order
	 * @var array
	 */
	public $saveTabsOrder;



	/**
	 * Callback - load tabs order
	 * @var array
	 */
	public $loadTabsOrder;



	static function getPersistentParams() {
		return array("tab");
	}



	public function  __construct($parent, $name) {
		parent::__construct($parent, $name);
		$this->tabContainer = Html::el("div")->class("tabs");
		$this->handlerComponent = $parent;
		$this->DOMtabsID = $this->getSnippetId("jQueryUITabs");
		$this->saveTabsOrder = array($this,"saveTabsOrder");
		$this->loadTabsOrder = array($this,"loadTabsOrder");
	}



	/**************************************************************************/
	/*                            Main methods                                */
	/**************************************************************************/

	/**
	 * Adds tab
	 * @param string $name
	 * @return Tab
	 */
	public function addTab($name) {
		return new Tab($this,$name); // It will be registered automaticly
	}



	/**
	 * Render tab control
	 */
	public function render() {
		if(count($this->tabs)==0) throw new InvalidStateException("There are no registered tabs!");

		// Reorder tabs
		if(($order = $this->getTabsOrder()) !== null) {
			// Uložíme si taby do $tabs a odstraníme a odregistrujeme je od stromu komponent
			$tabs = array();
			foreach($this->tabs AS $tabName => $tab) {
				$tabs[$tabName] = $tab;
				$this->removeComponent($tab);
			}

			// Zaregistrujeme taby v novém pořadí
			foreach($order AS $tabName) {
				$this->addComponent($tabs[$tabName], $tabName);
			}
		}

		// Mode: auto
		if($this->mode===self::MODE_AUTO) {
			if(count($this->tabs)<=3)
				$this->mode = self::MODE_PRELOAD; // Málo tabů -> načtem vše dopředu
			else
				$this->mode = self::MODE_LAZY; // Hodně tabů, načteme to, až to bude potřeba
		}

		if($this->presenter->isAjax()) {
			foreach($this->tabs AS $tab) {
				if($tab->hasSnippets === true) {
					$this->redraw($tab->name,FALSE);
				}
			}
		}

		if(!isSet($this->tabs[$this->getTab()]))
			throw new InvalidStateException("Active tab is not registered!");

		// Pokud je NEajaxový požadavek, tak se bude renderovat pouze aktivní tab
		if(!$this->presenter->isAjax())
			$this->tabsForDraw = array($this->getTab()=>true);

		$template = $this->createTemplate();
		//$template->registerFilter('Nette\Templates\CurlyBracketsFilter::invoke');
		$template->setFile(DIRNAME(__FILE__)."/TabControl.phtml");
		$template->activeTab = $this->tabs[$this->getTab()];
		$template->render();
	}



	/**
	 * Selects tab
	 * @param string $tab         Tab name
	 * @param bool   $invalidate  (internal) Invalidate tab?
	 * @return TabControl
	 */
	function select($tab,$invalidate=true,$JSSelect=true) {
		if($tab===self::SELECT_FIRST) {
			reset($this->tabs);
			if(($firstTab = current($this->tabs))===false)
				throw new InvalidStateException("There is no tabs in tabControl!");
			$tab = $firstTab->name;
		}
		if($this->tabs[(string)$tab] instanceof Tab) {
			$this->tab  = $tab;
			if($JSSelect)
				$this->javaScript = "tabs.tabs('select','".$this->getSnippetId($tab)."')";
		}
		$this->redraw(self::REDRAW_CURRENT,$invalidate);
		return $this;
	}



	/**
	 * Redraws tab
	 * @param mixed $tab        Accepts constants TabControl::REDRAW_CURRENT and TabControl::REDRAW_ALL or tab name
	 * @param bool $invalidate  (internal) Invalidate tab
	 * @return TabControl
	 */
	function redraw($tab=self::REDRAW_CURRENT,$invalidate=true) {
		if($tab===self::REDRAW_CURRENT OR $tab === self::REDRAW_ALL)
			$tabName = $this->getTab();
		else
			$tabName = $tab;

		$this->tabExists($tabName, TRUE);

		if($invalidate === true) {
			if($tab === self::REDRAW_ALL) {
				$this->invalidateControl();
			}
			else {
				$this->invalidateControl($tabName);
			}
		}

		$this->tabsForDraw[$tabName]=true;

		return $this;
	}



	/**
	 * Tab exists?
	 * @param string $tab   Tab name
	 * @param bool   $need
	 * @return bool
	 */
	function tabExists($tab,$need=false) {
		$exists = isSet($this->tabs[(string)$tab]);
		if($need and !$exists) throw new InvalidStateException("Tab ".(string)$tab." not exists!");
		return $exists;
	}



	/**
	 * Genereates link with onclick JavaScript (fastest way to change tab)
	 * @param string $tab   Tab name
	 * @return Html
	 */
	function selectAnchor($tab,$caption="") {
		$this->tabExists($tab,TRUE);
		return Html::el("a")
			->setHtml($caption)
			->href($this->generateSelectLink($tab))
			->onclick("$('#".$this->DOMtabsID."').tabs('select','".$this->getSnippetId($tab)."');return false;");
	}



	/**
	 * Generates link for selecting tab
	 * @param  string $tab  Tab name
	 * @return string       Generated link
	 */
	function generateSelectLink($tab) {
		$this->tabExists($tab,TRUE);
		return $this->link("select!", array("tab"=>$tab));
	}



	/**************************************************************************/
	/*                        Getters and setters                             */
	/**************************************************************************/

	/**
	 * Tabs for redraw
	 * @return array
	 */
	function getTabsForDraw() {
		return $this->tabsForDraw;
	}



	/**
	 * Returns sippets of JavaScript code
	 * @return array
	 */
	function getJavaScript() {
		return $this->JavaScript;
	}



	/**
	 * Adds line of JavaScript code
	 * @param string $code
	 * @return TabControl
	 */
	function setJavaScript($code) {
		$this->invalidateControl("JavaScript");
		$this->JavaScript[] = $code;
		return $this;
	}



	/**
	 * Returns active tab name
	 * @return Tab
	 */
	function getTab() {
		if($this->tab === null)
			$this->select(self::SELECT_FIRST,FALSE,FALSE);
		return $this->tab;
	}



	/**
	 * Returns all registered tabs
	 * @return ArrayIterator
	 */
	function getTabs() {
		return $this->components;
	}



	/**
	 * Returns tabs order - there are ALL tabs
	 * @return array|null
	 */
	function getTabsOrder() {
		$order = call_user_func_array($this->loadTabsOrder, array());
		if($order === false) {
			throw new InvalidStateException("TabControl::loadTabsOrder is not callable!");
		}
		if($order===array()) return null;

		$tabs = array();

		foreach($order AS $tabName) {
			if($this->tabExists($tabName))
				$tabs[] = $tabName;
		}

		foreach($this->tabs AS $tabName => $tab) {
			if(array_search($tabName,$tabs) === false)
				$tabs[] = $tabName;
		}
		return $tabs;
	}

	/**************************************************************************/
	/*                              Handlers                                  */
	/**************************************************************************/

	/**
	 * What to do when tab is preloading
	 */
	function handlePreload() {
		$this->redraw(self::REDRAW_CURRENT);
	}



	/**
	 * (internal) Activates tab
	 * Do not call directly!
	 */
	function handleSelect() {
		$this->select($this->getTab());
		if(!$this->presenter->isAjax() and $this->isSignalReceiver($this, "select"))
			$this->redirect("this");
	}


	/**
	 * Saves new order of tabs
	 *
	 * $order must be sent throw POST, because GET is reordered in Nette
	 *
	 * @param array $order
	 */
	function handleSaveTabsOrder($order) {
		$newOrder = array();
		foreach($order AS $tmp) {
			$tmp2 = explode("__", $tmp);
			$newOrder[] = $tmp2[count($tmp2)-1];
		}

		if(!$this->saveTabsOrder[0]->tryCall($this->saveTabsOrder[1], array("order"=>$newOrder))) {
			throw new InvalidStateException("TabControl::saveTabsOrder is not callable!");
		}
		$this->onOrderChange($newOrder,$this);

		// Workaround for: http://forum.nettephp.com/cs/2834-podivne-chovani-snippetu-tech-starych
		$this->presenter->terminate(new JsonResponse($this->presenter->payload));
	}



	/**
	 * Saves tabs order to session (default implemetation od TabControl::saveTabsOrder)
	 * @param array $order
	 */
	function saveTabsOrder(array $order) {
		$session = Environment::getSession("TabControl\\".$this->getUniqueId());
		$session["order"] = $order;
	}



	/**
	 * Loads tabs order from session (default implemetation od TabControl::loadTabsOrder)
	 * @return array
	 */
	function loadTabsOrder() {
		$session = Environment::getSession("TabControl\\".$this->getUniqueId());
		return (array)$session["order"];
	}



	/**************************************************************************/
	/*                         Extension methods                              */
	/**************************************************************************/

	/**
	 * Is $component receiver of $signal?
	 *
	 * @param PresenterComponent $component
	 * @param string $signal
	 * @return bool
	 */
	function isSignalReceiver(PresenterComponent $component,$signal) {
		$_signal = $this->presenter->getSignal();
		if($_signal[0]==$component->getUniqueId() and $_signal[1]==$signal)
			return true;
		else
			return false;
	}



	/**************************************************************************/
	/*                              Others                                    */
	/**************************************************************************/

	/**
	 * Descendant can override this method to disallow insert a child by throwing an \InvalidStateException.
	 * @param  IComponent
	 * @return void
	 * @throws \InvalidStateException
	 */
	protected function validateChildComponent(IComponent $child) {
		if(!$child instanceof Tab)
			throw new InvalidStateException("In 'TabControl' you can add only 'Tab's!");
		parent::validateChildComponent($child);
	}


}
