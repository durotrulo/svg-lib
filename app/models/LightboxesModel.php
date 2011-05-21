<?php

/**
 * LightboxesModel
 *
 * @author	Matus Matula
 */
class LightboxesModel extends BaseModel
{
	const TABLE = self::LIGHTBOXES_TABLE;
	
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
		$data['created'] = dibi::datetime();
		return parent::insert($data);
	}

	
}