<?php

/**
 * File resource
 *
 */
class FileResource extends OwnedResource
{
	const ID = Acl::RESOURCE_FILE;
    
	
	/**
	 * get ownerId from DB
	 *
	 * @param int resourceId
	 * @return int ownerId
	 */
    protected function getDependencies($id)
    {
    	return dibi::select('users_id')
					->from(BaseModel::FILES_TABLE)
					->where('id = %i', $id)
					->fetchSingle();
	}
    
}