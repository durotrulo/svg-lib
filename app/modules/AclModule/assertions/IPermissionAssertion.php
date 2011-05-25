<?php

/**
 * interface for all ACL (Permission) Assertions
 *
 */
interface IPermissionAssertion
{
	/**
	 * assert
	 *
	 * @param Permission $acl
	 * @param string|NULL|IRole
	 * @param string|NULL|IResource
	 * @param string|NULL
	 * @return bool
	 */
//    public function assert(Permission $acl, $role, $resource, $privilege);
    public function __invoke(Permission $acl, $role, $resource, $privilege);
}