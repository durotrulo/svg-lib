<?php
/**
 * Client role
 *
 */
class ClientRole extends BaseRole
{
	const ID = Acl::ROLE_CLIENT;
	
	public $superiorId;
	

	public function __construct($userId)
    {
    	parent::__construct($userId);

    	// set superior ID (client's id) if needed
    	// todo: possible use cache?
    	$cpModel = new ClientPackagesModel();
    	$this->superiorId = $cpModel->getClientIdOfLoggedUser();
    }
}