<?php

/**
 * Lightbox resource
 *
 */
class LightboxResource extends OwnedResource
{
	const ID = Acl::RESOURCE_LIGHTBOX;
    
	
	/**
	 * get ownerId from DB
	 *
	 * @param int resourceId
	 * @return int ownerId
	 */
    protected function getDependencies($id)
    {
    	return dibi::select('owner_id')
					->from(BaseModel::LIGHTBOXES_TABLE)
					->where('id = %i', $id)
					->fetchSingle();
	}
    
}