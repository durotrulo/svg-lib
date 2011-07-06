<?php

/**
 * ancestor for all Resources
 * 	implements cache to save SQL requests
 * 	each descendant must define const ID
 *
 */
abstract class BaseResource implements IResource
{
    
    /** @var Cache */
    protected $cache;


    /**
     * returns ID of given Resource
     */
    public function getResourceId()
    {
        return static::ID;
    }

}