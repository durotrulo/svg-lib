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
	
	
	public function generateShareLink($id)
	{
		$link = Basic::randomizer(15);
		dibi::insert(self::SHARED_ITEMS_TABLE, array(
			'lightboxes_id' => $id,
			'valid_until' => dibi::datetime(time() + 3600*24),
			'link' => $link,
		))->execute();
		
		// todo: generovat link ked spravim share sekciu
//		$link = Environment::getApplication()->getPresenter()->link('\\Share:Front:default', array($link));

		return $link;
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
	
	
	public function findOwners()
	{
		return dibi::select('DISTINCT u.id, u.firstname AS name')
					->from(self::USERS_TABLE)
						->as('u')
					->innerJoin(self::TABLE)
						->as('l')
						->on('l.owner_id = u.id')
					->fetchAll();
	}
	
	
	public function findByOwner($owner_id)
	{
		return dibi::select('id, name')
					->from(self::TABLE)
					->where('owner_id = %i', $owner_id)
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
		$data['owner_id'] = $this->userId;
		$data['created'] = dibi::datetime();
		return parent::insert($data);
	}

	
}