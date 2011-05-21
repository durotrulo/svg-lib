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
	
	const ORDER_BY_NAME = 'name';
	const ORDER_BY_DATE = 'date';
	
	
	public function findAll()
	{
		return dibi::select('p.*, 
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
					->groupBy('p.id');
//					->fetchAll();
	}
	
	
	public function getPreviewPath($id)
	{
		return file_exists(self::PATH . "/$id/main.jpg") ? $this->getRelativePath() . "/$id/main.jpg" : 'images/default-project.jpg';
	}
	
	
	/**
	 * filter out items that do NOT meet $filter
	 *
	 * @param DibiFluent
	 * @param string|null
	 * @return $this
	 */
	public function filter(&$items, $filter)
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
				
			default:
				throw new ArgumentOutOfRangeException("Unknown \$filter $filter.");
				break;
		}
		
		return $this;
	}

	
	/**
	 * filters items with given name (or part of it)
	 * @param DibiFluent
	 * @param string
	 */
	public function filterByName(&$items, $name)
	{
		if (!empty($name)) {
			$items->where('name LIKE %s', "%$name%");
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
			SELECT projects_id
			FROM %n
			WHERE projects_id2 = %i
			
			UNION
			
			SELECT projects_id2
			FROM %n
			WHERE projects_id = %i
		', self::RELATED_PROJECTS_TABLE,
			$id,
			self::RELATED_PROJECTS_TABLE,
			$id
		);
			
			select('id, name')
			->from(self::TABLE)
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