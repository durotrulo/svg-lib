<?php

/**
 * Description of BaseModel
 *
 * @author Matus Matula
 */
abstract class BaseModel extends Object
{
	const FILES_TABLE = 'files';
	const FILES_2_TAGS_TABLE = 'files_2_tags';
	const TAGS_TABLE = 'tags';
	const PROJECTS_TABLE = 'projects';
	const RELATED_PROJECTS_TABLE = 'related_projects';
	const ACTIVITY_LOG_TABLE = 'activity_log';
	const LIGHTBOXES_TABLE = 'lightboxes';
	const FILES_2_LIGHTBOXES_TABLE = 'files_2_lightboxes';
	
	/** @var LogsModel */
	private $logsModel;
	
	/** @var User */
	private $user;
	
	/** @var int */
	private $userId;
	
	
	/**
	 * @return LogsModel
	 */
	public function getLogsModel()
	{
		if ($this->logsModel === null) {
			$this->logsModel = new LogsModel();
		}
		
		return $this->logsModel;
	}
	
	
	/**
	 * @return User
	 */
	public function getUser()
	{
		if ($this->user === null) {
			$this->user = Environment::getUser();
		}
		
		return $this->user;
	}
	

	/**
	 * @return int|NULL
	 */
	public function getUserId()
	{
		if ($this->userId === null) {
			$this->userId = $this->getUser()->isLoggedIn() ? $this->getUser()->getIdentity()->data['id'] : NULL;
		}
		
		return $this->userId;
	}
	
	
//	const RECORD_NOT_FOUND = 'Pozadovany zaznam sa nenasiel'; // todo:remove?

	/** @var DibiConnection */
/*	protected $db;

	public function __construct()
	{
		$this->db = dibi::getConnection();
	}
	
	
	public function getInsertId() 
	{
		return $this->db->insertId();
	}
	

	public function affectedRows()
	{
		return $this->db->getAffectedRows();
	}
	
*/	

	/**
	 * validate sorting and set default if not valid
	 *
	 * @param string
	 * @param string
	 */
	public static function validateSorting(&$sorting, $default = dibi::DESC)
	{
		$sorting = strtoupper($sorting);
		if ($sorting !== dibi::DESC && $sorting !== dibi::ASC) {
			$sorting = $default;
		}
	}
	
	
	/**
	 * formats array for processing in sql [IN %sql, $arr]
	 *
	 * @param array
	 * @return string
	 */
	public static function formatInString($arr)
	{
		foreach ($arr as &$v) {
			$v = "'$v'";
		}
		
		return join(',', $arr);
	}
	
	
	/**
	 * prepends options with prompt option to be used in html select
	 *
	 * @param array
	 * @param string prompt option
	 * @param bool use $firstValue as whole phrase or just as first value?
	 * @return array
	 */
	public static function prepareSelect($options, $firstValue = '', $usePhrase = false)
	{
		$item = $usePhrase ? $firstValue : "Select $firstValue";
		return array($item) + $options;
	}
	
	
	/**
	 * formats tree returned by DibiResult_prototype_fetchTree into multidimensional array,
	 * optionally prepends options with prompt option to be used in html select
	 *
	 * @param array result from dibi method "DibiResult_prototype_fetchTree"
	 * @param int level/depth used internally
	 * @param string|NULL prompt option
	 * @return array
	 */
	public static function prepareSelectTree($options, $level = 1, $firstValue = NULL)
	{
		$ret = array();
		foreach ($options as $k => $item) {
			if (isset($item['children'])) {
				$ret[$item['name']] = self::prepareSelectTree($item['children'], $level+1);
			} else {
				$ret[$k] = $item['name'];
			}
		}
		
		if (!is_null($firstValue) && $level === 1) {
			return self::prepareSelect($ret, $firstValue);
		} else {
			return $ret;
		}
	}
	

	/**
	 * finds user by id
	 *
	 * @param int $id
	 * @return DibiRow
	 */
	public function find($id)
	{
		return dibi::select('*')
				->from(static::TABLE)
				->where('id = %i', $id)
				->fetch();
	}
	
	
	/**
	 * fetch pairs
	 *
	 * @param int $id
	 * @return DibiRow
	 */
	public function fetchPairs($key = 'id', $val = 'name')
	{
		return dibi::select("$key, $val")
					->from(static::TABLE)
					->orderBy($key)
					->fetchPairs();
	}
	

	/**
	 * basic update specified by $id
	 *
	 * @param int $id
	 * @param array $data
	 * @return DibiResult
	 */
	public function update($id, array $data)
	{
		return dibi::update(static::TABLE, $data)->where('id = %i', $id)->execute();
	}


	/**
	 * basic insert
	 *
	 * @param array $data
	 * @return int insertedId()
	 */
	public function insert(array $data)
	{
		return dibi::insert(static::TABLE, $data)->execute(dibi::IDENTIFIER);
	}


	/**
	 * basic delete specified by $id
	 *
	 * @param int $id
	 * @param bool [optional] erase dir on filesystem?
	 * @return DibiResult
	 */
	public function delete($id)
	{
		if (func_num_args() > 1) {
			$rmDir = func_get_arg(1);
			if (!empty($rmDir)) {
				Basic::rmdir(static::PATH . $id, true);
			}
		}

		return dibi::delete(static::TABLE)->where('id=%i', $id)->execute();
	}
	
	
	/**
	 * returns relative path from WWW_DIR to concat with $baseUri
	 *
	 * @param string|NULL $path
	 * @return string
	 */
	public static function getRelativePath($path = null)
	{
		if (!is_string($path)) {
			if (defined('static::PATH')) {
				$path = static::PATH;
			} else {
				return null;
			}
		}
		
		$pathSlashed = str_replace('\\', '/', $path);
		$relativePath = substr($pathSlashed, strpos($pathSlashed, WWW_DIR) + strlen(WWW_DIR) + 1);
		return $relativePath;
	}
	
	/**
	 * spoji viacero $multipleVar pre 1 zaznam do vysledneho 1 zaznamu
	 * 
	 * cize, ak mam:
	 * id	name	group
	 * 1	abc		g1
	 * 1	abc		g2
	 * 
	 * tak dostanem 
	 * id	name	group
	 * 1	abc		g1,g2
	 * 
	 * 
	 * @param DibiResult $res
	 * @param string $multipleVar stlpec z query, kt. je multiple [group v priklade]
	 * @param string $compareVar  stlpec, podla kt. sa zistuje, ci je zaznam unikatny [vacsinou id]
	 * @param string $sep oddelovac
	 * @return array
	 * 
	 * @see http://latrine.dgx.cz/temer-v-cili-dibi-0-9b
	 */
	public function mergeRecords($res, $multipleVar, $compareVar='id', $sep=', ')
	{
		throw new DeprecatedException('mergeRecords is deprecated. Use fetchAssoc() instead.');
		
		$ret = array();
		$last = false;
		$tmp = array();
		$i = 0;
		if (count($res) > 1) {
			foreach ($res as $v) {
				//	novy zaznam
				if ($last != $v->$compareVar) {
					$last = $v->$compareVar;
					//	ak to nie je 1.zaznam, tak mu doplnime vsetky $multipleVar
					if ($i > 0) {
						$ret[$i-1]->$multipleVar = implode($sep, $tmp);
					}
					$ret[$i++] = $v;
					
					//	ulozime 1.$multipleVar
					$tmp = array($v->$multipleVar);
				//	id je rovnake => ukladame $multipleVar
				} else {
					$tmp[] = $v->$multipleVar;
				}
			}
			
			$ret[$i-1]->$multipleVar = implode($sep, $tmp);
		} else {
			$ret = $res;
		}
		
		return $ret;
	}
	
	
	protected function downloadFile($srcFile, $publicFilename = null, $deleteAfterDownload = false)
	{
		if (empty($publicFilename)) {
			$publicFilename = basename($srcFile);
		}
		
		$filedownload = new FileDownload;
		$filedownload->sourceFile = $srcFile;
		$filedownload->transferFileName = $publicFilename;
		
		if ($deleteAfterDownload) {
			$filedownload->onComplete[] = function(FileDownload $download, IDownloader $downloader) use($srcFile) {
				unlink($srcFile);
			};
		}
		
		$filedownload->download();
		exit(0);
	}
}