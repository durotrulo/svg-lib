<?php

/**
 * Nette Framework Extras
 *
 * This source file is subject to the New BSD License.
 *
 * For more information please see http://extras.nettephp.com
 *
 * @copyright  Copyright (c) 2009 David Grudl
 * @license    New BSD License
 * @link       http://extras.nettephp.com
 * @package    Nette Extras
 * @version    $Id: VisualPaginator.php 4 2009-07-14 15:22:02Z david@grudl.com $
 */

/*use Nette\Application\Control;*/

/*use Nette\Paginator;*/

/**
 * EXAMPLE:
 * 
	$vp = $this['itemPaginator'];
	$vp->selectItemsPerPage = array(8, 16, 24);
	$vp->setDefaultItemsPerPage(5);
    $vp->paginator->itemCount = $itemsCount;
	$this->template->items = $this->items
									->toDataSource()
									->applyLimit($vp->paginator->itemsPerPage, $vp->paginator->offset)
									->fetchAll();
									
	// when paging refresh only items
	if ($vp->paginated && !$vp->itemsPerPageChanged) {
		$this->invalidateControl('items');
	} else {
		$this->invalidateControl();
	}
 */

/**
 * Visual paginator control.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2009 David Grudl
 * @package    Nette Extras
 */
class VisualPaginator extends BaseControl
{
	/** @var Paginator */
	private $paginator;

	/** @persistent */
	public $page = 1;
//	public $page;
//	private $page;

	
	/** @var bool render itemsPerPage as select? */
	private $itemsPerPageAsSelect = false;
	
	/** @persistent */
	public $itemsPerPage;

	/** string clankov, poloziek, komentarov..pouzite vo vypise */
	public $itemString = 'príspevkov';
	
	/** @var bool */
	public $useAjax = true;

	public $selectItemsPerPage = array(5, 10, 20);
	
	/** @var bool ci sa previedlo strankovanie .. kvoli invalidacii USE: if pg->paginated => invalidateControl	*/
	public $paginated = false;

	/** @var bool ci sa zmenil pocet items na stranku .. kvoli invalidacii USE: if pg->itemsPerPageChanged => invalidateControl	*/
	public $itemsPerPageChanged = false;
	
	public function getPaginator()
	{
		if (!isset($this->paginator)) {
			$this->paginator = new Paginator();
		}
		return $this->paginator;
	}

	
	public function getPage()
	{
		return $this->page;
	}
	

	/**
	 * @param bool
	 */
	public function setItemsPerPageAsSelect($v)
	{
		$this->itemsPerPageAsSelect = (bool) $v;
	}
	
	/**
	 * @return bool
	 */
	public function getItemsPerPageAsSelect()
	{
		return $this->itemsPerPageAsSelect;
	}
	

	/**
	 */
	public function __construct(IComponentContainer $parent = NULL, $name = NULL)
	{
		parent::__construct($parent, $name);

		$this->paginator = $this->getPaginator();
	}

	public function setDefaultItemsPerPage($i)
	{
		// defaultny pocet zobrazenych prispevkov
		if (!($this->itemsPerPage)) {
			$this->paginator->itemsPerPage = $i;
		}
	}

	
//	public function setPage($page)
	/**
	 * paginate items and sets pagination flags
	 *
	 * @param int page to go
	 * @param bool is paging caused by change of itemsCount per page?
	 */
	public function handleGoto($page, $itemsPerPageChanged = false)
	{
		$this->paginator->page = $this->page = $page;
		$this->paginated = true;
		$this->itemsPerPageChanged = $itemsPerPageChanged;

		if (!$this->getPresenter()->isAjax()) {
			$this->redirect('this');
		}
	}
	
	
	/**
	 * Renders paginator.
	 * @param bool|enum [up | down] $renderDots ci vykreslit okolo paginatora aj bodky
	 * @return void
	 */
	public function render($renderDots = false, $skin = 'default')
	{
		$this->setWebloaderPaths(); // kvoli tomu, ze sa vola komponenta viac krat, tak sa cesta k webloaderu nastavena v construct moze prepisat, treba to znova nastavit
		
		$skinSrc = 'skins/' . $skin;
		
		// copy skin images to webtemp
		$this->copy("$skinSrc/images", false);

		// not every skin has to have css file
	    if (file_exists(__DIR__ . "/$skinSrc/skin.css")) {
			$this->addCssFiles(array('skin.css', $skinSrc));
	    }
	    
	    
		$paginator = $this->paginator;
		$page = $paginator->page;
		if ($paginator->pageCount < 2) {
			$steps = array($page);

		} else {
			$arr = range(max($paginator->firstPage, $page - 3), min($paginator->lastPage, $page + 3));
			$count = 4;
			$quotient = ($paginator->pageCount - 1) / $count;
			for ($i = 0; $i <= $count; $i++) {
				$arr[] = round($quotient * $i) + $paginator->firstPage;
			}
			sort($arr);
			$steps = array_values(array_unique($arr));
		}

		$tpl = $this->getTemplate();
		$tpl->renderDotsUp = ($renderDots === true or $renderDots == 'top');
		$tpl->renderDotsDown = ($renderDots === true or $renderDots == 'bottom');
		$tpl->steps = $steps;
		$tpl->paginator = $paginator;
		$tpl->useAjax = $this->useAjax;
		$tpl->itemString = $this->itemString;
		$tpl->itemsPerPage = $this->selectItemsPerPage;
		$tpl->setFile(__DIR__ . "/$skinSrc/template.phtml");
		$tpl->render();
		
	}


	/**
	 * Loads state informations.
	 * @param  array
	 * @return void
	 */
	public function loadState(array $params)
	{
		parent::loadState($params);
		
		$this->getPaginator()->page = $this->page;
		$this->paginator->itemsPerPage = $this->itemsPerPage;
	}

}