<?php

class TokenExpiredException extends Exception {};
class InvalidPasswordException extends Exception {};

/**
 * Users authenticator.
 *
 * @author	Matus Matula
 */
//class UsersModel extends Object implements IAuthenticator
class UsersModel extends BaseModel implements IAuthenticator
{
	const TABLE = 'users';
//	const USER_LEVELS_TABLE = 'user_levels';
	const USER_LEVELS_TABLE = 'gui_acl_roles';
	const EXPIRY_DAYS = 3; //how many days user has to confirm registration
	
	/** @dbsync (#user_levels.name)*/
	const UL_SUPERADMIN = 'superadmin';
	const UL_ADMIN = 'admin';
	const UL_PROJECT_MANAGER = 'projectManager';
	const UL_DESIGNER = 'designer';
	const UL_CLIENT = 'client';
	
	/** @dbsync (#user_levels.id)*/
	const UL_SUPERADMIN_ID = 3;
	const UL_ADMIN_ID = 4;
	const UL_PROJECT_MANAGER_ID = 5;
	const UL_DESIGNER_ID = 6;
	const UL_CLIENT_ID = 7;
	const UL_CLIENT_BASIC_USER_ID = 8;
	const UL_CLIENT_ADMIN_USER_ID = 9;

	/** @var array of roles suitable for tags' userlevel */
	protected $rolesForTags = array(self::UL_PROJECT_MANAGER, self::UL_DESIGNER, self::UL_CLIENT);
	
	protected $rolesModel;

	/** @var array */
	private $_internalUserRoles = array(
		self::UL_ADMIN,
		self::UL_SUPERADMIN,
		self::UL_DESIGNER,
		self::UL_PROJECT_MANAGER,
	);
	
	
	public function getRolesModel()
	{
		if (!$this->rolesModel) {
			$this->rolesModel = new RolesModel();
		}
		
		return $this->rolesModel;
	}
	
	
	/**
	 * return logged user's roles suitable for tag userlevel
	 *
	 */
	public function getRolesForTag()
	{
		foreach ($this->user->getRoles() as $role) {
			if (in_array($role, $this->rolesForTags)) {
				return $role;
			}
		}
		// return any role - e.g. last one
		return $role;
//		return NULL;
	}
	
	
	public function __construct()
	{
		$this->config['useAcl'] = Environment::getConfig('global')->useAcl;
	}
	
	
	/**
	 * Performs an authentication
	 * @param  array
	 * @return IIdentity
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		$username = $credentials[self::USERNAME];
		$password = $credentials[self::PASSWORD];

		$row = $this->findAll(false)
					->where('username=%s', $username)
					->fetch();

		// check only for valid combination of credentials for security reasons
		if (!$row or $row->password !== self::getHash($username, $password)) {
			throw new AuthenticationException("Invalid credentials.", self::IDENTITY_NOT_FOUND);
		}
		
		//	este nepotvrdil registraciu cez mail
		if (!$row->approved) {
//			 throw new AuthenticationException('Najskôr potvrďte registráciu na vašom emaili.', self::NOT_APPROVED);
			 throw new AuthenticationException('Confirm your registration via email first!', self::NOT_APPROVED);
		}
		
		$row->realname = $row->firstname . ' ' . $row->lastname;
		
		//	user sa uspesne prihlasil => update last login
		self::update($row->id, array('last_login' => dibi::datetime()), false, true);
//		dibi::update(self::TABLE, array('last_login' => dibi::datetime()))->where('id = %i', $row->id)->execute();

		
		// get roles
//		if (Environment::getConfig('global')->useAcl) {
		if ($this->config['useAcl']) {
			$roles = dibi::select('r.key_name')
						->from(self::ACL_ROLES_TABLE)
							->as('r')
						->rightJoin(self::ACL_USERS_2_ROLES_TABLE)
							->as('u2r')
							->on('r.id = u2r.role_id')
						->where('u2r.user_id = %i', $row->id)
						->fetchPairs();
//        	$sql = dibi::query('SELECT r.key_name
//                                FROM [' . TABLE_ROLES . '] AS r
//                                RIGHT JOIN [' . TABLE_USERS_ROLES . '] AS ur ON r.id = ur.role_id
//                                WHERE ur.user_id = %i;', $row->id);
//       	 	$roles = $sql->fetchPairs();
		} else {
			$roles = $row->role;
		}
		
		$row['isInternal'] = $this->isInternal($roles);
		$row['isClient'] = !$row['isInternal'];


		unset($row->password);
		return new Identity($row->username, $roles, $row);
	}
	
	
	public function isInternal($userRoles)
	{
		return count(array_intersect($userRoles, $this->_internalUserRoles)) > 0;
	}
	
	
	public static function getHash($username, $password)
	{
		return sha1($username . $password);
//		return hash_hmac('sha256', $password, $username);
	}
	
	
	public static function findByEmail($email)
	{
		return dibi::select('id, username')
					->from(self::TABLE)
					->where('email = %s', $email)
					->fetch();
	}
	
	
	public function find($id)
	{
		$user = parent::find($id);
		$this->bindRoles($user, false);
		return $user;
	}
	
	
	/**
	 * bind user roles to users
	 *
	 * @param DibiRow | DibiRow array
	 * @param bool fetch pairs or just return keys?
	 */
	public function bindRoles(&$users, $fetchPairs = true)
	{
		if (!$this->config['useAcl']) {
			return;
		}
		
		// if not array, cast to array and at the end cast back
		$arrayApplied = false;
		if (!is_array($users)) {
			$users = array($users);
			$arrayApplied = true;
		}
		
		// bind roles for each user
		foreach ($users as &$user) {
			$roles = dibi::query('SELECT r.id, r.name
	                            FROM %n AS r
	                            JOIN %n AS u2r ON r.id=u2r.role_id
	                            WHERE u2r.user_id = %i
	                            ORDER BY r.name;', self::ACL_ROLES_TABLE, 
	    											self::ACL_USERS_2_ROLES_TABLE, 
	    											$user->id
	    					)->fetchPairs();
	    		
			// fetch keys only (mainly for edit user)		
	    	if (!$fetchPairs) {
	    		$roles = array_keys($roles);
	    	}
	   	 	$user['roles'] = $roles;
		}
		
		// cast back if necessary
		if ($arrayApplied) {
			$users = $users[0];
		}
	}
	
	
	/**
	 * find all users
	 *  (optionally limited to roles weaker than given $userLevelId)
	 * 
	 * @param bool fetch results (and roles consequently) ?
	 * @return DibiRow array
	 */
	public function findAll($fetch = true)
	{
		$users = dibi::select('*')
				->from(self::TABLE);

		// append roles
		if ($fetch) {
			$users = $users->fetchAll();
            $this->bindRoles($users);
		}

		return $users;
	}
	
	
	/**
	 * find users created by client with $clientId
	 *
	 * @param int
	 * @return DibiFluent
	 */
	public function findClientUsers($clientId)
	{
		return $this->findAll(false)
					->where('supervisor_id = %i', $clientId);
	}

	
	public function insert(array $data)
	{
		if ($this->config['useAcl']) {
			// check rights
			if (!$this->user->isAllowed(Acl::RESOURCE_USER, Acl::PRIVILEGE_ADD)) {
				throw new OperationNotAllowedException();
			}
		}

		$data['token'] = md5($data['email'] . $data['username']);
		$data['password'] = self::getHash($data['username'], $data['password']);
		$data['registered'] = dibi::datetime();

		if (isset($data['roles'])) {
			$roles = $data['roles'];
			unset($data['roles']);
		}

		$userId = parent::insert($data);
		
		if (isset($roles)) {
			$this->getRolesModel()->updateUserRoles($userId, (array) $roles);
		}
		
		return $userId;
	}

	
	/**
	 * delete user
	 *
	 * @param int
	 */
	public function delete($id)
	{
		// check rights - client can delete only users who he created
		if (!$this->user->isAllowed(new UserResource($id), Acl::PRIVILEGE_DELETE)) {
			throw new OperationNotAllowedException();
		}
		parent::delete($id);
	}
	

	/**
	 * update logged in user's data
	 *
	 * @param array
	 * @param bool
	 */
	public function updateLoggedUser(array $data, $updateIdentity = true)
	{
		$this->update($this->getUserId(), $data, $updateIdentity);
	}
	
	
	/**
	 * update user's data
	 *
	 * @param int user id
	 * @param array
	 * @param bool update identity of logged user?
	 * @param bool skip checking ACL? - used internally while logging in
	 */
	public function update($id, array $data, $updateIdentity = false, $skipCheckingAcl = false)
	{
		if ($this->config['useAcl'] and !$skipCheckingAcl) {
			// check rights - client can update only users who he created
			if (!$this->user->isAllowed(new UserResource($id), Acl::PRIVILEGE_EDIT)) {
				throw new OperationNotAllowedException();
			}
		}
		
		//	if we come from userEdit form
    	if (isset($data['password'])) {
    		// user did not enter new password
    		if (empty($data['password'])) {
	    		unset($data['password']);
	    	} else {
	    		// if current password is required (typically when calling from updateLoggedUser(); admin does not have to enter currentPassword)
	    		if (isset($data['currentPassword'])) {
					$dbData = $this->find($this->userId);
	    			// check if it's correct
		    		if (self::getHash($dbData->username, $data['currentPassword']) !== $dbData->password) {
//		    			throw new InvalidPasswordException('Zadali ste nesprávne stávajúce heslo.');
		    			throw new InvalidPasswordException('You entered invalid current password. Try again please!');
		    		}
	    		}
				$data['password'] = self::getHash($data['username'], $data['password']);
	    	}

    		unset($data['currentPassword']);
	    	unset($data['password2']);
    	}
    	
    	// update roles
    	if (isset($data['roles'])) {
			$this->getRolesModel()->updateUserRoles($id, $data['roles']);
			unset($data['roles']);
    	}
    	    	
    	// update user
		parent::update($id, $data);
		
		if ($updateIdentity) {
	        self::updateIdentity($data);
		}
	}

	/**
	 * updates identity data, call after each possible data change
	 *
	 * @param array $data
	 */
	public static function updateIdentity($data)
	{
		foreach($data as $col => $value)
        {
           	Environment::getUser()->getIdentity()->$col = $value;
        }
	}
	
	
	/**
	 * activate user identified by token
	 *
	 * @param string $token
	 * @return bool false => expired, true => approved
	 * @throws TokenExpiredException
	 */
	public static function activate($token)
	{
		$row = dibi::select("id, DATEDIFF(NOW(), registered) <= %i AS not_expired", self::EXPIRY_DAYS)
				->from(self::TABLE)
				->where("token = %s", $token)
//				->where("DATEDIFF(NOW(), registered) <= 3")
				->fetch();

		// invalid token
		if (!$row) {
			throw new TokenExpiredException();
		} else {
			// all good => approve user
			if ($row->not_expired) {
				dibi::update(self::TABLE, array(
					'approved' => 1,
				))
				->where("id = %i", $row->id)
				->execute();
			// expired => delete him
			} else {
				dibi::delete(self::TABLE)
					->where("token = %s", $token)
					->execute();
					
				return false;
			}
		}

		return true;
	}
	
	
	/**
	 * checks if there is already user with defined item
	 *
	 * @param string [email | username]
	 * @param mixed 
	 */
	public static function userWithItemExists($item, $value)
	{
		return dibi::select('COUNT(*)')
			->from(self::TABLE)
			->where('%n = %s', $item, $value)
			->fetchSingle();
	}
	
	
	public static function findByRole($userLevelId)
	{
		return dibi::select('id, CONCAT(firstname, " ", lastname) AS name')
					->from(self::TABLE)
						->as('u')
					->rightJoin(TABLE_USERS_ROLES)
						->as('ur')
						->on('u.id = ur.user_id')
					->where('ur.role_id = %i', $userLevelId)
					->fetchPairs('id', 'name');
	}
	
	
//	public function findClients()
//	{
//		return self::findByRole(self::UL_CLIENT_ID);
//	}
	
	
	/**
	 * find user roles that admin can set
	 *
	 * @param int [optional]
	 * @return array
	 */
	public function findRoles($onlyClientRoles = false)
	{
		$ret = dibi::select('id, name')
					->from(self::ACL_ROLES_TABLE)
					->where('is_public = 1');
					
		if ($onlyClientRoles) {
			$ret->where('id IN (%iN)', array(
				self::UL_CLIENT_BASIC_USER_ID, 
				self::UL_CLIENT_ADMIN_USER_ID
			));
		}
		
		return $ret->fetchPairs();
	}
	
	
	
	/**
	 * check if username is available
	 *
	 * @param string
	 * @return bool
	 */
	public function isAvailable($val, $col = 'username')
	{
		return parent::isAvailable($val, $col);
//		return !(bool) dibi::select('COUNT(*)')
//							->from(self::TABLE)
//							->where('username = %s', $name)
//							->fetchSingle();
	}
	
		
	/**
	 * filter users having %username%
	 * @param DibiFluent
	 * @param string
	 */
	public function filterByUsername(&$items, $name)
	{
		if (!empty($name)) {
			$items->where('username LIKE %s', "%$name%");
		}
		
		return $this;
	}
}
