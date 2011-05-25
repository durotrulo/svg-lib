<?php

class LightboxResource implements IResource
{
    public $ownerId;

    
    public function __construct($id)
    {
    	$this->ownerId = dibi::select('owner_id')
    						->from(BaseModel::LIGHTBOXES_TABLE)
    						->where('id = %i', $id)
    						->fetchSingle();
    }
    
    
    public function getResourceId()
    {
        return 'lightbox';
    }
}