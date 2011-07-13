<?php

/**
 * LightboxesModel
 *
 * @author	Matus Matula
 */
class LightboxesModel extends OwnedItemsModel
{
	const TABLE = self::LIGHTBOXES_TABLE;
	
	private $ownPairs;

	
	
	/**
	 * finds user by id
	 *
	 * @param int
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
	
	
	/**
	 * fetch latest lb of logged user
	 *
	 * @return DibiRow
	public function findUserLatest()
	{
		return dibi::select('l.*, u.firstname AS owner')
				->from(self::TABLE)
					->as('l')
				->leftJoin(self::USERS_TABLE)
					->as('u')
					->on('l.owner_id = u.id')
				->where('l.owner_id = %i', $this->getUserId())
				->orderBy('created DESC')
				->fetch();
	}
	 */
	
	
	/**
	 * fetch latest lb of logged user
	 *
	 * @return DibiRow
	 */
	public function findUserLatestId()
	{
		return dibi::select('id')
				->from(self::TABLE)
				->where('owner_id = %i', $this->getUserId())
				->orderBy('created DESC')
				->fetchSingle();
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
	 * @param array
	 * @return int insertedId()
	 */
	public function insert(array $data)
	{
		$data['owner_id'] = $this->userId;
		$data['created'] = dibi::datetime();
		return parent::insert($data);
	}

	
}