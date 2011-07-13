<?php

/**
 * common ancestor for Lightbox and ClientPackage Models due to dependencies on ownerships
 *
 */
class OwnedItemsModel extends BaseModel
{
	
	/**
	 * find owners of all existing items (lb or cp)
	 *
	 * @param string|null first letter of client for filtering
	 * @param bool consider only publicly visible items?
	 * @return DibiRow array
	 */
	public function findOwners($firstLetter = null, $onlyVisible = false)
	{
		$ret = dibi::select('DISTINCT u.id, CONCAT(u.firstname, " ", u.lastname) AS name')
					->from(self::USERS_TABLE)
						->as('u')
					->innerJoin(static::TABLE)
						->as('l');
//					->orderBy('u.id - %i ASC', $this->userId) // first should be logged user's lightboxes
		if ($onlyVisible) {
			$ret->on('l.owner_id = u.id AND l.is_visible = 1');
		} else {
			$ret->on('l.owner_id = u.id');
		}
		
		if (!empty($firstLetter)) {
			$ret->where('u.firstname LIKE %s OR u.lastname LIKE %s', "$firstLetter%", "$firstLetter%");
		}
		
		return $ret->where('u.id != %i', $this->userId) // except logged user
					->fetchAll();
	}
	
	
	/**
	 * find items owned by user
	 *
	 * @param int id of owner (#users.id)
	 * @param bool consider only publicly visible items?
	 * @return DibiRow array
	 */
	public function findByOwner($owner_id, $onlyVisible = false)
	{
		$ret = dibi::select('id, name')
					->from(static::TABLE)
					->where('owner_id = %i', $owner_id);
		
		if ($onlyVisible) {
			$ret->where('is_visible = 1');
		}

		return $ret->fetchAll();
	}
	
	
	/**
	 * update name of given item
	 *
	 * @param int
	 * @param string new name
	 * @return DibiResult
	 */
	public function updateName($id, $name)
	{
		return parent::update($id, array(
			'name' => $name,
		));
	}
}