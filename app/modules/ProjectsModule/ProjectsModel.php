<?php
define('PROJECTS_PATH', PUBLIC_DATA_DIR . '/projects');

/**
 * ProjectsModel
 *
 * @author	Matus Matula
 */
class ProjectsModel extends BaseModel
{
	const TABLE = self::PROJECTS_TABLE;
	const PATH = PROJECTS_PATH;

	const IMAGE_W = 390;
	const IMAGE_H = 260;
	
	const FILTER_COMPLETED = 'completed';
	const FILTER_IN_PROGRESS = 'in-progress';
	const FILTER_FIRST_LETTER = 'first-letter';
	
	const ORDER_BY_NAME = 'name';
	const ORDER_BY_DATE = 'date';
	
	/** @var int @dbsync */
	const GENERAL_PROJECT_ID = 0;
	
	
	public function findAll()
	{
		return dibi::select('p.*, 
			IF(p.completed, DATE_FORMAT(p.completed, "%m/%d/%Y"), "In Progress") as completedFormatted,
			CONCAT(u.firstname, " ", u.lastname) AS manager,
			COUNT(f.id) AS vectorFilesCount,
			COUNT(f2.id) AS bitmapFilesCount
			')
					->from(self::TABLE)
						->as('p')
					->leftJoin(self::FILES_TABLE)
						->as('f')
						->on('f.projects_id = p.id AND f.type = "vector"')
					->leftJoin(self::FILES_TABLE)
						->as('f2')
						->on('f2.projects_id = p.id AND f2.type = "bitmap" AND f2.id = f.id')
					->leftJoin(UsersModel::TABLE)
						->as('u')
						->on('p.manager_id = u.id')
					->groupBy('p.id')
					->orderBy('completed DESC');
//					->fetchAll();
	}
	
	
	/**
	 * find by id
	 *
	 * @param int project id
	 * @return DibiRow
	 */
	public function find($id)
	{
		return $this->findAll()
					->where('p.id = %i', $id)
					->fetch();
	}
	
	
	
	public function getTopFiles($projectId)
	{
		return dibi::select('id, filename, description')
					->from(self::FILES_TABLE)
					->where('projects_id = %i', $projectId)
					->where('is_top_file = 1')
					->orderBy('top_file_order ASC')
					->fetchAll();
	}
	
	
	
	/**
	 * check if projectname is available
	 *
	 * @param string
	 * @return bool
	 */
	public function isAvailable($name)
	{
		return !(bool) dibi::select('COUNT(*)')
							->from(self::TABLE)
							->where('name = %s', $name)
							->fetchSingle();
	}
	
	
	/**
	 * get project name by id
	 *
	 * @param int project id
	 * @return string
	 */
	private function getNameById($id)
	{
		return dibi::select('name')
					->from(self::TABLE)
					->where('id = %i', $id)
					->fetchSingle();
	}
	
	
	/**
	 * output whole project to be downloaded (and increment downloadCount for each file and log action 'project download')
	 * @todo overit, ze user moze project stiahnut ?
	 *
	 * @param int project id
	 * @return void
	 */
	public function download($id)
	{
		// create ZIP
		$zipper = new ZipArchive();
		$zipPath = PUBLIC_DATA_DIR . '/projects-zip/' . uniqid() . '.zip';
		Basic::mkdir(dirname($zipPath));
		if ($zipper->open($zipPath, ZIPARCHIVE::CREATE) !== true) {
		    exit("cannot open <$zipPath>\n");
		}

		// get project's files
		$filesModel = new FilesModel();
		$files = $filesModel->findAll();
		$filesModel->filterByProject($files, $id);

		// add them to zip archive
		$fileIds = array();
		foreach ($files as $file) {
			$path = $filesModel->getOriginalPath($file);
			$zipper->addFile($path, basename($path));
			
			$fileIds[] = $file->id;
		}
		
		$zipper->close();

		// increment downloadCount for each file
		dibi::update(self::FILES_TABLE, array(
				'downloads%sql' => 'downloads + 1',
			))
			->where('id IN (%iN)', $fileIds)
			->execute();
		

		// log project download
		$this->logsModel->insert(
			array(
				'users_id' => $this->userId,
				'projects_id' => $id,
				'action' => LogsModel::ACTION_DOWNLOAD,
			)
		);
		
		$projectName = $this->getNameById($id);
		parent::downloadFile($zipPath, $projectName . '.zip', true);
	}
	
	
	/**
	 * get preview path for project's thumb image
	 *
	 * @param int project id
	 * @return string
	 */
	public function getPreviewPath($id)
	{
		return file_exists(self::PATH . "/$id/main.jpg") ? $this->getRelativePath() . "/$id/main.jpg" : 'images/default-project.jpg';
	}
	
	
	/**
	 * filter out items that do NOT meet $filter
	 *
	 * @param DibiFluent
	 * @param string|null
	 * @param mixed
	 * @return $this
	 */
	public function filter(&$items, $filter, $filterVal = null)
	{
		switch ($filter) {
			case null:
				break;
				
			case self::FILTER_COMPLETED:
				$items->where('completed IS NOT NULL');
				break;
		
			case self::FILTER_IN_PROGRESS:
				$items->where('completed IS NULL');
				break;
				
			case self::FILTER_FIRST_LETTER:
				if (!empty($filterVal)) {
					$items->where('name LIKE %s', "$filterVal%");
				}
				break;
				
			default:
				throw new ArgumentOutOfRangeException("Unknown \$filter $filter.");
				break;
		}
		
		return $this;
	}

	
	/**
	 * filters items with given name or subtitle (or part of it)
	 * @param DibiFluent
	 * @param string
	 */
	public function filterByNameOrSubtitle(&$items, $name)
	{
		if (!empty($name)) {
			$items->where('name LIKE %s OR subtitle LIKE %s', "%$name%", "%$name%");
		}
		
		return $this;
	}
	

	/**
	 * order items
	 *
	 * @param DibiFluent
	 * @param string
	 * @param string
	 * @return $this
	 */
	public function order(&$items, $orderBy, $sorting)
	{
		BaseModel::validateSorting($sorting, dibi::DESC);
		switch ($orderBy) {
			case self::ORDER_BY_DATE:
				$items->orderBy('created', $sorting);
				break;

			case self::ORDER_BY_NAME:
				$items->orderBy('name', $sorting);
				break;
				
			default:
				throw new ArgumentOutOfRangeException("Unknown \$orderby value $orderBy.");
				break;
		}
		
		return $this;
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
		if ($id === self::GENERAL_PROJECT_ID) {
			throw new ArgumentOutOfRangeException('Project GENERAL cannot be deleted');
		}
		
		
		/**
		 * todo: vymazat subory s duplicitnym nazvom v projekte GENERAL? alebo hodit warning? alebo?
		 */
		
		try {
			// delete duplicate files?
			// ...
			

			// move files to project GENERAL
			dibi::update(self::FILES_TABLE, array(
				'projects_id' => self::GENERAL_PROJECT_ID,
			))
			->where('projects_id = %i', $id)
			->execute();
			
			// rename project's dirname
			rename(self::PATH . "/$id", self::PATH . '/' . self::GENERAL_PROJECT_ID);
			
			// delete project
			$res = parent::delete($id);
			
			// log
			$this->logsModel->insert(
				array(
					'users_id' => $this->userId,
					'projects_id' => $id,
					'action' => LogsModel::ACTION_DELETE,
				)
			);
			
			return $res;
		} catch (DibiDriverException $e) {
			throw $e;
		}
		
//		if (func_num_args() > 1) {
//			$rmDir = func_get_arg(1);
//			if (!empty($rmDir)) {
//				Basic::rmdir(static::PATH . $id, true);
//			}
//		}
//
//		return dibi::delete(static::TABLE)->where('id=%i', $id)->execute();
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
		$this->deleteRelatedProjects($id);
		$this->insertRelatedProjects($id, $data['related_projects']);
		unset($data['related_projects']);

		$main_img = $data['main_img'];
		unset($data['main_img']);
		$this->saveImage($main_img, $id);
		
		return parent::update($id, $data);
	}


	/**
	 * basic insert
	 *
	 * @param array $data
	 * @return int insertedId()
	 */
	public function insert(array $data)
	{
		$relatedProjects = $data['related_projects'];
		unset($data['related_projects']);
		
		$main_img = $data['main_img'];
		unset($data['main_img']);

		$id = parent::insert($data);
		$this->insertRelatedProjects($id, $relatedProjects);

		$this->saveImage($main_img, $id);
		return $id;
	}
	
	

	private function saveImage($file, $id)
	{
		return ImageUploadModel::savePreview(self::PATH . '/' . $id, $file, self::IMAGE_W, self::IMAGE_H, true, 'main.jpg');
	}
	
	
	
	
	/**
	 * RELATED PROJECTS
	 */
	

	public function getRelatedProjects($id)
	{
		return dibi::query('
			SELECT projects_id AS id, name
			FROM %n AS rp
			LEFT JOIN %n AS p
				ON rp.projects_id = p.id
			WHERE projects_id2 = %i
			
			UNION
			
			SELECT projects_id2 AS id, name
			FROM %n AS rp
			LEFT JOIN %n AS p
				ON rp.projects_id2 = p.id
			WHERE projects_id = %i
		', self::RELATED_PROJECTS_TABLE,
			self::PROJECTS_TABLE,
			$id,
			self::RELATED_PROJECTS_TABLE,
			self::PROJECTS_TABLE,
			$id
		)
		->fetchPairs('id', 'name');
	}
	
	
	public function insertRelatedProjects($projectId, $relatedProjects)
	{
		foreach ($relatedProjects as $v) {
			dibi::insert(self::RELATED_PROJECTS_TABLE, array('projects_id' => $projectId, 'projects_id2' => $v))->execute();
		}
	}
	
	
	public function deleteRelatedProjects($projectId)
	{
		return dibi::delete(self::RELATED_PROJECTS_TABLE)
					->where('projects_id = %i OR projects_id2 = %i', $projectId, $projectId)
					->execute();
	}

}