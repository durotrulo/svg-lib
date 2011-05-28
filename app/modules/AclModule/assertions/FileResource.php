<?php

class FileResource implements IResource
{
    public $ownerId;

    
    public function __construct($id)
    {
    	$this->ownerId = dibi::select('users_id')
    						->from(BaseModel::FILES_TABLE)
    						->where('id = %i', $id)
    						->fetchSingle();
    }
    
    
    public function getResourceId()
    {
        return 'file';
    }
}