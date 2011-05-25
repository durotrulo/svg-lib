<?php
class OwnerAssertion implements IPermissionAssertion
{
//    public function assert(Permission $acl, $role, $resource, $privilege)
    public function __invoke(Permission $acl, $role, $resource, $privilege)
    {
//    	dump($acl->getQueriedRole()->id, $acl->getQueriedResource()->ownerId);
       	return $acl->getQueriedRole()->id === $acl->getQueriedResource()->ownerId;
    }
}