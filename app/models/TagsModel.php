<?php

class TagNotFound extends Exception {};

/**
 * Tags model
 *
 * @author	Matus Matula
 */
class TagsModel extends BaseModel
{
	/** @var string db table name */
	const TABLE = self::TAGS_TABLE;

//	const CK_ALL_PAIRS = 'all-pairs'; // CK - cache key - vsetky tagy ako id=>tag



	/**
	 * insert tag
	 *
	 * @param array $data
	 * @return int insertedId()
	 */
	public function insert(array $data)
	{
		return parent::insert(array(
			'name' => $data['name'],
			'users_id' => $this->userId,
			'created' => dibi::datetime()
		));
	}
	
	/*
	public function getAllPairs()
	{
		$section = self::CK_ALL_PAIRS;
		$cache = Environment::getCache('tags');
		if (!isset($cache[$section])) {
			$cache->save($section, function() {
				return $this->fetchPairs();
			}, array(
			    'tags' => array('tags'),
			));
			   	
			   	// zoradime podla tagu
//			   	$tagsModel->sortByTag($tagsTmp);
			   	
			$cache->save($section, $tags, array(
			    'tags' => array('tags'),
			));
		}
		
		return $cache[$section];
	}
	
	public function invalidateAllPairs()
	{
		CacheTools::invalidate('tags', false, self::CK_ALL_PAIRS);
	}
	*/
	
}