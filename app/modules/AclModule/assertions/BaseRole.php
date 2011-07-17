<?php

/**
 * ancestor for all Roles
 * 	each descendant must define const ID
 */
abstract class BaseRole implements IRole
{
	/** @var int automatically set in Acl->isAllowed() */
    public $id;

    
    public function __construct($userId)
    {
    	$this->id = $userId;
    }
    
    
    /**
     * get role ID
     */
    public function getRoleId()
    {
        return static::ID;
    }
}