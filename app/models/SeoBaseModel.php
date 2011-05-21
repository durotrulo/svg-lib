<?php

/**
 * performs operations related to seolinks
 * 
 * @author Matus Matula
 *
 */
class SeoBaseModel extends BaseModel
{

	/** @var array [static::$cacheKey => array (id => uri)] */
	protected static $seolinks;

	/** @var array of boolean [key == static::$cacheKey] - have seolinks been loaded? */
	protected static $seolinksLoaded = array();

	/** @var string db column name representing path/uri */
	protected static $uriKey = 'uri';

	/** @var string db column name representing unique identifier of page */
	protected static $idKey = 'id';
	
	/**
	 * preloads links saving them to static::$seolinks, also caching them if cache is invalid
	 *
	 * @return void
	 */
	protected static function preloadLinks()
	{
		// if already loaded, return
        if (
        	isset(static::$seolinksLoaded[static::$cacheKey])
        	&& static::$seolinksLoaded[static::$cacheKey] === true
        ) {
        	return false;
        }
        
		$seolinks = Environment::getCache('Seolinks.' . static::$cacheKey);
		if ($seolinks['data'] === null) {
//        	$data = dibi::select('id, uri')
        	$data = dibi::select(static::$idKey)
        				->select(static::$uriKey)
                		->from(static::TABLE)
		        		->fetchPairs(static::$idKey, static::$uriKey);
//		        		->fetchPairs('id', 'uri');
        		
        	$seolinks['data'] = $data;
        }
        
        static::$seolinks[static::$cacheKey] = $seolinks['data'];
        static::$seolinksLoaded[static::$cacheKey] = true;
	}
	
	
	/**
	 * gets uri by provided id either from cache or directly queried against DB
	 */
	public static function findUriById($id)
	{
		static::preloadLinks();
		
		if (isset(static::$seolinks[static::$cacheKey][$id])) {
			return static::$seolinks[static::$cacheKey][$id];
		} else {
			//	if not found, invalidate cache so cache will be rebuilt on next request
			static::invalidateCache();
			return self::findUriByIdDB($id, static::TABLE);
		}
	}
	
	
	/**
	 * gets id by provided uri either from cache or directly queried against DB
	 */
	public static function findIdByUri($uri)
	{
		static::preloadLinks();
		
		$keys = array_keys(static::$seolinks[static::$cacheKey], $uri);
		if (isset($keys[0])) {
			return $keys[0];
		} else {
			//	if not found, invalidate cache so cache will be rebuilt on next request
			static::invalidateCache();
			return self::findIdByUriDB($uri, static::TABLE);
		}
	}
	
	public static function invalidateCache($type = NULL)
	{
		if (is_null($type)) {
			$type = static::$cacheKey;
		}
		
		$cache = Environment::getCache("Seolinks." . $type);
		unset($cache['data']);
	}

	
	public static function findUriByIdDB($id, $table)
	{
//		$uri = dibi::select('uri')
		$uri = dibi::select(static::$uriKey)
					->from($table)
					->where('%n = %i', static::$idKey, $id)
//					->where('id=%i', $id)
					->fetchSingle();
				
		return $uri === false ? null : $uri;
	}
	
	
	public static function findIdByUriDB($uri, $table)
	{
//		$id = dibi::select('id')
		$id = dibi::select(static::$idKey)
					->from($table)
//					->where('uri = %s', $uri)
					->where('%n = %s', static::$uriKey, $uri)
					->fetchSingle();

		return $id === false ? null : $id;
	}
}