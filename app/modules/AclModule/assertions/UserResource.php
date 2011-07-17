<?php

/**
 * User resource - used only for users created by client
 *
 */
class UserResource extends OwnedResource
{
	const ID = Acl::RESOURCE_USER;
    
	
	/**
	 * get supervisorId from DB
	 *
	 * @param int resourceId
	 * @return int supervisorId
	 */
    protected function getDependencies($id)
    {
    	return dibi::select('supervisor_id')
					->from(BaseModel::USERS_TABLE)
					->where('id = %i', $id)
					->fetchSingle();
	}
    
}