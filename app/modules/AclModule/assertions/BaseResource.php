<?php

/**
 * ancestor for all Resources
 * 	implements cache to save SQL requests
 * 	defines getDependencies($id) which must be implemented in descendants
 * 	each descendant must define const ID
 *
 */
abstract class BaseResource implements IResource
{
	/** @var int id of owner of given resource */
    public $ownerId;
    
    /** @var Cache */
    protected $cache;


    /**
     * set $this->ownerId, optionally using cache
     *
     * @param int Resource_id
     */
    public function __construct($id)
    {
    	if (defined('ACL_CACHING') and ACL_CACHING) {
            $this->cache = Environment::getCache();
			$key = static::ID . '-' . $id;
            if (!isset($this->cache[$key])) {
                $this->cache->save($key, static::getDependencies($id));
            }
        	$this->ownerId = $this->cache[$key];
        } else {
        	$this->ownerId = static::getDependencies($id);
        }
    }
    
    
    /**
     * returns ID of given Resource
     */
    public function getResourceId()
    {
        return static::ID;
    }

    
    /**
     * must be implemented in descendant
     *
     * @param int Resource_id
     * @return mixed dependency (e.g. ownerId from DB)
     */
    abstract protected function getDependencies($id);
}