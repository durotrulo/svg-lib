<?php

/**
 * ClientPackagesModel
 *
 * @author	Matus Matula
 */
class ClientPackagesModel extends BaseModel
{
	const TABLE = self::CLIENT_PACKAGES_TABLE;
	
	private $ownPairs;

	public function getOwnPairs()
	{
		if ($this->ownPairs === null) {
			$this->ownPairs = dibi::select('id, name')
				->from(self::TABLE)
				->where('owner_id = %i', $this->userId)
				->fetchPairs('id', 'name');
		}
		
		return $this->ownPairs;
	}
	
	
	/**
	 * finds user by id
	 *
	 * @param int $id
	 * @return DibiRow
	 */
	public function find($id)
	{
		return dibi::select('l.*, u.firstname AS owner')
				->from(self::TABLE)
					->as('l')
				->leftJoin(self::USERS_TABLE)
					->as('u')
					->on('l.owner_id = u.id')
				->where('l.id = %i', $id)
				->fetch();
	}
	
	
	public function updateName($id, $name)
	{
		return dibi::update(self::TABLE, array(
					'name' => $name,
				))
					->where('id = %i', $id)
					->where('owner_id = %i', $this->userId)
					->execute();
	}
	
	
	public function findByClient($clientId)
	{
		return dibi::select('id, name')
					->from(self::TABLE)
					->where('client_id = %i', $clientId)
					->fetchAll();
//					->fetchPairs('id', 'name');
	}

	
	/**
	 * get user's own lightboxes for given fileId (=>LB in which file is not yet)
	 *
	 * @return unknown
	 */
	public function fetchUserLB4FilePairs($fileId)
	{
		return dibi::select('id, name')
			->from(self::TABLE)
			->innerJoin(self::FILES_2_LIGHTBOXES_TABLE)
				->as('f2l')
				->on('f2l.lightboxes_id != l.id')
			->where('owner_id = %i', $this->userId)
			->where('lightboxes_id = %i', $this->userId)
			->fetchPairs('id', 'name');
	}
	
	
	/**
	 * insert
	 *
	 * @param array $data
	 * @return int insertedId()
	 */
	public function insert(array $data)
	{
		$data['created_by'] = $this->userId;
		$data['created'] = dibi::datetime();
		return parent::insert($data);
	}
	
	
	/**
	 * insert files to client package
	 *
	 * @param array
	 * @param int
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