<?php

/**
 * assertion based on ownership of given resource
 *
 */
class OwnerAssertion implements IPermissionAssertion
{
    public function __invoke(Permission $acl, $role, $resource, $privilege)
    {
       	return $acl->getQueriedRole()->id === $acl->getQueriedResource()->ownerId;
    }
}