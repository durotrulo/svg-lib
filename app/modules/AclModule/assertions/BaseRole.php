<?php

/**
 * ancestor for all Roles
 * 	each descendant must define const ID
 */
abstract class BaseRole implements IRole
{
	/** @var int automatically set in Acl->isAllowed() */
    public $id;

    
    /**
     * get role ID
     */
    public function getRoleId()
    {
        return static::ID;
    }
}