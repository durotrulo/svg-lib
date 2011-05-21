<?php

/**
 * Handles app translation using database
 *
 * @author Ciki
 */
class DBTranslator extends Object implements ITranslator {

	/** @var string table name in database in which we store phrases */
	const TABLE = 'translations';
	
	/** @var DBTranslator holds instance */
	static $instance = NULL;

	/** @var array supported languages */
	public $langs;
	
	/** @var array of translated phrases */
	private $dictionary;
	
	/** @var bool debug mode */
//	private $debug = false;
	private $debug = true;

    private $paramsRegexp = '#\%([0-9]+\$)*[fsd]#';
    
	/** @var array of dirs in which we look for phrases to be translated */
	public static $dirs = array('templates', 'presenters', 'models', 'modules', 'components');

	public static function getInstance()
	{
		if(!self::$instance)
			self::$instance = new DBTranslator();

		return self::$instance;
	}

	/**
	 * nacita prelozene stringy do slovnika
	 * 
	 * @param array $supportedLangs pole podporovanych jazykov
	 * @param bool $rebuild rebuild dictionary by pulling phrases out of .php and .phtml and updating database
	 * @return void
	 *
	 */
	public function buildDictionary($supportedLangs = NULL, $rebuild = false)
	{
		if (!isset($this->langs) && !$supportedLangs) {
			throw new Exception('nastav podporovane jazyky');
		} elseif ($supportedLangs) {
			$this->langs = $supportedLangs;
		}
		
		if ($rebuild) {
			$this->findAllStrings(); // calls buildDictionary() so return
			return;
		}
		
		$lang = Environment::getVariable('lang');
		$dictionary = Environment::getCache('dictionary');
		if ($dictionary['data'] === null) {
			$data = array();
			foreach ($this->langs as $lang) {
				$data[$lang] = dibi::select("msg_id, IF(msg_$lang = '', msg_id, msg_$lang) AS msg")
									->from(self::TABLE)
									->fetchPairs('msg_id', 'msg');
			}
			
			$dictionary['data'] = $data;
		}
		$this->dictionary = $dictionary['data'][$lang];
//		dump($this->dictionary);
	}

	
	/**
	 * translates string with key $msg_id
	 *
	 * @param string $msg_id
	 * @param int $count NOT USED YET, only for ITranslator needs
	 * @return string translated string
	 */
	public function translate($msg_id, $count = NULL)
//	public function translate($msg_id)
	{
		if ($msg_id == "") {
			return NULL;
		}

        ///**************
		
		if (!isset($this->dictionary[$msg_id])) {
			dibi::insert(self::TABLE, array(
				'msg_id' => $msg_id,
//				'msg_en' => $message,
//				'msg_de' => $message,
//				'msg_sk' => $message,
			))->execute();
			$this->dictionary[$msg_id] = $msg_id;
			CacheTools::invalidate('dictionary');
		}

		/**	applying parameters **/
		$args = func_get_args();
        $argsCount = count($args);
        $requiredArgsCount = preg_match_all($this->paramsRegexp, $this->dictionary[$msg_id], $matches);
       
        if ($requiredArgsCount > $argsCount - 1) {
         	throw new InvalidArgumentException("Insufficient number of arguments in translate function. Provided string '$msg_id'");
       	}   
       	 
//        if ($argsCount > 1) {
		//	volane z Rules.php, aby sa mi to nesexovalo s '%label' a tym percentom a nemusel ho zdvojovat
        if ($argsCount > 1 && !is_null($args[1])) {
        	//	vola sa to aj s parametrami z Rules.php napr. pre MIN_LENGTH a pod., treba zdvojit percento
        	$msg_id = str_replace(
        		array(
	        		'%label',
	        		'%name',
	        		'%value',
        		),
        		array(
	        		'%%label',
	        		'%%name',
	        		'%%value',
        		),
        		$this->dictionary[$msg_id]);
//		dump($args);
            array_shift($args);
//		dump($args);
            return vsprintf($msg_id, $args);
        } else {
 			return $this->dictionary[$msg_id];
        }
		/**	applying parameters END **/
	}
	
	
	/**
	 * vrati pole suborov s pozadovanymi priponami
	 *
	 * @param string $path
	 * @param array $suffixes
	 * @return array
	 */
	private function getFiles($path, $suffixes)
	{
		$path = Basic::addLastSlash($path, '\\');
		$ret = array();
		if (($dp = @opendir($path)) != false) { 
	   		while (false !== ($item = readdir($dp))) {
	   			$newPath = $path.$item;
	   			if (is_dir($newPath) and !in_array($item, array('.','..') )) {
		   			$ret = array_merge($ret, $this->getFiles($newPath, $suffixes));
		   		} elseif (is_file($newPath) and is_readable($newPath) and preg_match('/^.+\.('.implode('|', $suffixes).')$/i', $item)) {
//		   			echo $newPath . '<br>';
		   			array_push($ret, $newPath);
		   		}
	   		}
		   	closedir($dp);
		} elseif ($this->debug) {
			echo "directory '$path' not opened";
		}
	
		return $ret;
	}
	
	/**
	 * prejde vsetky adresare v $dir, nacita subory s koncovkou .php a .phtml a vysosa odtial retazce na prelozenie
	 * zoberie vsetko z
	 * 		{_} 
	 * 		$this->translate()
	 * 		_()
	 * 	ci uz s apostrofmi alebo uvodzovkami
	 * 
	 * NOTICE:
	 * 		formulare treba spravit vzdy rucne - popreklikavat aplikaciu, automaticky sa doplnia preklady do DB v metode translate()
	 */
	// 
	public function findAllStrings()
	{
		$files = array();
		foreach (self::$dirs as $dir) {
			$path = realpath(APP_DIR . "\\$dir\\");
			$files = array_merge($files, $this->getFiles($path, array('php', 'phtml')));
		}
		
		//	z tohto suoru nechceme sosat
		$files = array_diff($files, array(__FILE__));
		
		$phrases = array();
		$patterns = array(
//			"#{_[\'|\"](.*)[\'|\"]}#U", // sablony bez params
			"#{_[\"\']((?:\\\\?+.)*?)[\"\']#s", // sablony + params vsetko medzi {_', resp; {_" a ', resp " .. mozu tam byt aj \' a \", #s modifikator pridava newline k bodke
			"#\\s+(?:\\\$this->translate|_)\([\'|\"](.*)[\'|\"]\)#Us", // $this->translate() a _() v .php .. musi byt pred nimi medzera .. znaky ?: znamenaju, aby sa () nepouzili ako backreference
			"#flashMessage\([\'|\"](.*)[\'|\"][,|\)|(\\s+\.\\s+)]#Us", // vsetky flash messages sa automaticky prekladaju - koncit sa to moze zatvorkou, ciarkou alebo medzerou nasledovanou bodkou, ktore nasleduje zatvorka
//			"#_\([\'|\"](.*)[\'|\"]\)#U", // _() v .php
//			"#this->translate\([\'|\"](.*)[\'|\"]\)#U", // $this->translate() v .php
		);
		foreach ($files as $file) {
			foreach ($patterns as $pattern) {
			    preg_match_all($pattern, file_get_contents($file), $matches);
				foreach ($matches[1] as $val) {
//			    	$phrases[] = $val;
			    	$phrases[$val] = substr($file, strlen(WWW_DIR)+1);
			    }
			}
		}

		$phrasesArray = $phrases;
	    $phrases = array_unique(array_keys($phrases));
	    if (!isset($this->dictionary)) {
	    	$this->buildDictionary();
	    }
	    
	    $loadedPhrases = array_keys($this->dictionary);
	    $allPhrases = array_unique(array_merge($phrases, $loadedPhrases));
	    $oldOrForm = array_diff($loadedPhrases, $phrases);
	    $newPhrases = array_diff($phrases, $loadedPhrases);
//	    dump($phrasesArray);
//	    dump($loadedPhrases);
//	    dump($allPhrases);
//	    dump($oldOrForm);
//	    dump($newPhrases);
//	    $oldOrForm
//	    $newPhrases = array_diff_key(1)
//	    dump($newPhrases);
//	    dump();
	    //	insert new phrases in 1 query
	    if (count($newPhrases) > 0) {
	    	$files = $this->filterArray($newPhrases, $phrasesArray);
//	    	dump($files);die();
			$data = array(
			    'msg_id' => array_keys($files),
			    'file' => array_values($files),
			);
			dibi::query('INSERT INTO %n %m', self::TABLE, $data);
	    }

	    if (count($oldOrForm) > 0) {
	    	foreach ($oldOrForm as $phrase) {
	    		dibi::update(self::TABLE, array('oldOrForm' => 1,))
	    			->where('msg_id = %s', $phrase)
	    			->execute();
	    	}
	    }

		if ($this->debug) {
//	  		dump($phrases);
			echo '<b>';
		    echo count($allPhrases) . " phrases together<br>";
		    echo count($files) . " files matched<br>";
		    echo count($phrases) . " phrases found to be translated<br>";
		    echo count($newPhrases) . " new phrases found:</b><br>" . join('<br>', $newPhrases);
		    echo '<b>' . count($oldOrForm) . " phrases not found but exist in dictionary => possibly unused or from form:</b><br>" . join('<br>', $oldOrForm);
	    }

	    // aby sa refresol slovnik
	    $cache = Environment::getCache('dictionary');
		unset($cache['data']);

		$this->buildDictionary();
	}
	
	/**
	 * as
	 *
	 * @param array $arr1 $newPhrases
	 * @param array $arr2 $phrasesArray
	 */
	private function filterArray(&$arr1, $arr2)
	{
		$ret = array();
		
		foreach ($arr1 as $v) {
			$ret[$v] = $arr2[$v];
		}
		
//		ksort($ret);
//		sort($arr1);
		
		return $ret;
	}
}
