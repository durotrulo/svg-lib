<?php

/**
 * ClientPackage resource
 *
 */
class ClientPackageResource extends OwnedResource
{
	const ID = Acl::RESOURCE_CLIENT_PACKAGE;
    
	
	/**
	 * get ownerId from DB
	 *
	 * @param int resourceId
	 * @return int ownerId
	 */
    protected function getDependencies($id)
    {
    	return dibi::select('owner_id')
					->from(BaseModel::CLIENT_PACKAGES_TABLE)
					->where('id = %i', $id)
					->fetchSingle();
	}
    
}