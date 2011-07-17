<?php
define('CLIENT_PACKAGES_PATH', PUBLIC_DATA_DIR . '/packages');

/**
 * ClientPackagesModel
 *
 * @author	Matus Matula
 */
class ClientPackagesModel extends OwnedItemsModel
{
	const TABLE = self::CLIENT_PACKAGES_TABLE;
	const PATH = CLIENT_PACKAGES_PATH;

	const FILTER_FIRST_LETTER = 'first-letter';
	
	const ORDER_BY_NAME = 'name';
	const ORDER_BY_DATE = 'date';
	
	
	/**
	 * find all client packages
	 *
	 * @param bool return only packages that logged user can view?
	 */
	public function findAll($restrict2UserRole = false)
	{
		$ret = dibi::select('cp.*, 
			COUNT(f.id) AS vectorFilesCount,
			COUNT(f2.id) AS bitmapFilesCount,
			CONCAT(u.firstname, " ", u.lastname) AS owner
			')
				->from(self::TABLE)
					->as('cp')
				->leftJoin(self::FILES_2_CLIENT_PACKAGES_TABLE)
					->as('f2cp')
					->on('f2cp.client_packages_id = cp.id')
				->leftJoin(self::FILES_TABLE)
					->as('f')
					->on('f.type = "vector" AND f.id = f2cp.files_id')
				->leftJoin(self::FILES_TABLE)
					->as('f2')
					->on('f2.type = "bitmap" AND f2.id = f2cp.files_id')
				->leftJoin(self::USERS_TABLE)
					->as('u')
					->on('u.id = cp.owner_id')
				->where('is_visible = 1')
				->groupBy('cp.id')
				->orderBy('name ASC');
				
		if ($restrict2UserRole) {
			//todo: ak logged user bol vytvoreny clientom, najdi client ID, kt. ho vytvoril
			if (!$this->userIdentity->isInternal and $this->userId !== UsersModel::UL_CLIENT_ID) {
				$clientId = $this->getClientIdOfLoggedUser();
				$ret->where('owner_id = %i', $clientId);
			}
		}
		
		return $ret;
//					->fetchAll();
	}
	
	
	public function getClientIdOfLoggedUser()
	{
		if ($this->userIdentity->isInternal) {
			throw new InvalidStateException('Method can be called only if logged user is client or user created by client');
		}
		
		// if it's client return his id
		if (in_array(UsersModel::UL_CLIENT, $this->user->getRoles())) {
			return $this->userId;
		}
		
		// otherwise select supervisor id
		return 23;
		return dibi::select('supervisor_id')
					->from(self::USERS_TABLE)
					->where('id = %i', $this->userId)
					->fetchSingle();
	}
	
//	public function findAll()
//	{
//		return dibi::select('cp.*, 
//			COUNT(f.id) AS vectorFilesCount,
//			COUNT(f2.id) AS bitmapFilesCount,
//			CONCAT(u.firstname, " ", u.lastname) AS owner
//			')
//				->from(self::TABLE)
//					->as('cp')
//				->leftJoin(self::FILES_TABLE)
//					->as('f')
//					->on('f.projects_id = cp.id AND f.type = "vector"')
//				->leftJoin(self::FILES_TABLE)
//					->as('f2')
//					->on('f2.projects_id = cp.id AND f2.type = "bitmap" AND f2.id = f.id')
//				->leftJoin(self::USERS_TABLE)
//					->as('u')
//					->on('u.id = cp.owner_id')
//				->where('is_visible = 1')
//				->groupBy('cp.id')
//				->orderBy('name ASC');
////					->fetchAll();
//	}
	
	
	/**
	 * find by id
	 *
	 * @param int project id
	 * @return DibiRow
	 */
	public function find($id)
	{
		return $this->findAll()
					->where('cp.id = %i', $id)
					->where('is_visible = 1')
					->fetch();
	}
	
	
	/**
	 * get top files of given package
	 *
	 * @param int clientPackageId
	 * @return DibiRow array
	 */
	public function getTopFiles($cpId)
	{
		return dibi::select('id, filename, suffix, description, projects_id')
					->from(self::FILES_TABLE)
					->where('is_top_file = 1')
					->where('id IN (%sql)', 
						dibi::select('files_id')
							->from(self::FILES_2_CLIENT_PACKAGES_TABLE)
							->where('client_packages_id = %i', $cpId)
							->__toString()
					)
					->orderBy('top_file_order ASC')
					->fetchAll();
	}
	
	
//	/**
//	 * get CP's name by id
//	 *
//	 * @param int project id
//	 * @return string
//	 */
//	private function getNameById($id)
//	{
//		return dibi::select('name')
//					->from(self::TABLE)
//					->where('id = %i', $id)
//					->fetchSingle();
//	}
	
	
//	/**
//	 * output whole project to be downloaded (and increment downloadCount for each file and log action 'project download')
//	 * @todo overit, ze user moze project stiahnut ?
//	 * @todo dorobit
//	 *
//	 * @param int project id
//	 * @return void
//	 */
//	public function download($id)
//	{
//		// create ZIP
//		$zipper = new ZipArchive();
//		$zipPath = PUBLIC_DATA_DIR . '/projects-zip/' . uniqid() . '.zip';
//		Basic::mkdir(dirname($zipPath));
//		if ($zipper->open($zipPath, ZIPARCHIVE::CREATE) !== true) {
//		    exit("cannot open <$zipPath>\n");
//		}
//
//		// get project's files
//		$filesModel = new FilesModel();
//		$files = $filesModel->findAll();
//		$filesModel->filterByProject($files, $id);
//
//		// add them to zip archive
//		$fileIds = array();
//		foreach ($files as $file) {
//			$path = $filesModel->getOriginalPath($file);
//			$zipper->addFile($path, basename($path));
//			
//			$fileIds[] = $file->id;
//		}
//		
//		$zipper->close();
//
//		// increment downloadCount for each file
//		dibi::update(self::FILES_TABLE, array(
//				'downloads%sql' => 'downloads + 1',
//			))
//			->where('id IN (%iN)', $fileIds)
//			->execute();
//		
//
//		// log project download
//		$this->logsModel->insert(
//			array(
//				'users_id' => $this->userId,
//				'projects_id' => $id,
//				'action' => LogsModel::ACTION_DOWNLOAD,
//			)
//		);
//		
//		$projectName = $this->getNameById($id);
//		parent::downloadFile($zipPath, $projectName . '.zip', true);
//	}
	
	
	/**
	 * get preview path for project's thumb image
	 *
	 * @param int project id
	 * @return string
	 */
	public function getPreviewPath($id)
	{
		return file_exists(self::PATH . "/$id/main.jpg") ? $this->getRelativePath() . "/$id/main.jpg" : ProjectsModel::DEFAULT_IMAGE_PATH;
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
	 * delete client package
	 *
	 * @param int
	 * @return DibiResult
	 */
	public function delete($id)
	{
		// check rights
		if (!$this->user->isAllowed(Acl::RESOURCE_CLIENT_PACKAGE, Acl::PRIVILEGE_DELETE)) {
			throw new OperationNotAllowedException();
		}
		
		$res = parent::delete($id);
		
		if ($res) {
			// log
			$this->logsModel->insert(
				array(
					'users_id' => $this->userId,
					'client_packages_id' => $id,
					'action' => LogsModel::ACTION_DELETE,
				)
			);
		}
		
		return $res;
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
		// check rights
		if (!$this->user->isAllowed(Acl::RESOURCE_CLIENT_PACKAGE, Acl::PRIVILEGE_EDIT)) {
			throw new OperationNotAllowedException();
		}
		
//		$main_img = $data['main_img'];
//		unset($data['main_img']);
//		$this->saveImage($main_img, $id);
		
		return parent::update($id, $data);
	}


//	private function saveImage($file, $id)
//	{
//		return ImageUploadModel::savePreview(self::PATH . '/' . $id, $file, self::IMAGE_W, self::IMAGE_H, false, 'main.jpg');
//	}
	
	
	/**
	 * find owners of all existing cp's
	 * 
	 * @param string|null first letter of client for filtering
	 * @return DibiRow array
	 */
	public function findOwners($firstLetter = null, $mock = false)
	{
		return parent::findOwners($firstLetter, true);
	}
	
	
	/**
	 * find items owned by user
	 *
	 * @param int id of owner (#users.id)
	 * @return DibiRow array
	 */
	public function findByOwner($owner_id, $mock = false)
	{
		return parent::findByOwner($owner_id, true);
	}
	
	
	/**
	 * insert
	 *
	 * @param array
	 * @return int insertedId()
	 */
	public function insert(array $data)
	{
		// check rights
		if (!$this->user->isAllowed(Acl::RESOURCE_CLIENT_PACKAGE, Acl::PRIVILEGE_ADD)) {
			throw new OperationNotAllowedException();
		}
		
		$data['created_by'] = $this->userId;
		$data['created'] = dibi::datetime();
		return parent::insert($data);
	}
	
	
	/**
	 * copy all project files to client package
	 *
	 * @param int
	 * @param int
	 * @return void
	 */
	public function copyProject2CP($projectId, $cpId)
	{
		$fileIds = dibi::select('id')
						->from(self::FILES_TABLE)
						->where('projects_id = %i', $projectId)
						->fetchAll();
					
		$this->insertFiles($fileIds, $cpId);

		$this->copyProjectImage2CP($projectId, $cpId);
		
		$this->logsModel->insert(
			array(
				'users_id' => $this->userId,
				'projects_id' => $projectId,
				'client_packages_id' => $cpId,
				'action' => LogsModel::ACTION_ASSOCIATE,
			)
		);
	}
	
	
	/**
	 * copy project image 2 package
	 *
	 * @param int
	 * @param int
	 * @return void
	 */
	private function copyProjectImage2CP($projectId, $cpId)
	{
		$projectsModel = $this->projectsModel;
		$projectImagePath = $this->projectsModel->getPreviewPath($projectId, true);
		$dest = self::PATH . "/$cpId/main.jpg";
		Basic::mkdir(dirname($dest));
		copy($projectImagePath, $dest);
	}
	
	
	/**
	 * insert files to client package
	 *
	 * @param array
	 * @param int
	 * @return void
	 */
	public function insertFiles(array $fileIds, $cpId)
	{
		foreach ($fileIds as $fileId) {
			try {
				dibi::insert(self::FILES_2_CLIENT_PACKAGES_TABLE, array(
					'files_id' => $fileId,
					'client_packages_id' => $cpId,
				))->execute();
			} catch (DibiDriverException $e) {
				// silently continue
				if ($e->getCode() === BaseModel::DUPLICATE_ENTRY_CODE) {
					continue;
				} else {
					throw $e;
				}
			}

			$this->logsModel->insert(
				array(
					'users_id' => $this->userId,
					'files_id' => $fileId,
					'client_packages_id' => $cpId,
					'action' => LogsModel::ACTION_ASSOCIATE,
				)
			);
		}
	}

}