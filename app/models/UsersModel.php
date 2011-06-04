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

	/** @var array of roles suitable for tags' userlevel */
	protected $rolesForTags = array(self::UL_PROJECT_MANAGER, self::UL_DESIGNER, self::UL_CLIENT);
	
	protected $rolesModel;
	
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
		self::update($row->id, array('last_login' => dibi::datetime()), false);
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


		unset($row->password);
		return new Identity($row->username, $roles, $row);
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

	
	public function insert(array $data)
	{
		$data['token'] = md5($data['email'] . $data['username']);
		$data['password'] = self::getHash($data['username'], $data['password']);
		$data['registered'] = dibi::datetime();

		if (isset($data['roles'])) {
			$roles = $data['roles'];
			unset($data['roles']);
		}

		$userId = parent::insert($data);
		
		if (isset($roles)) {
			$this->getRolesModel()->updateUserRoles($userId, $roles);
		}
		
		return $userId;
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
	 */
	public function update($id, array $data, $updateIdentity = false)
	{
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
		    			throw new InvalidPasswordException('Zadali ste nesprávne stávajúce heslo.');
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
	
	
	/**
	 * find user roles that admin can set
	 *
	 * @return array
	 */
	public function findRoles()
	{
		return dibi::select('id, name')
					->from(self::ACL_ROLES_TABLE)
					->where('is_public = 1')
					->fetchPairs();
	}
	
	
	
	/**
	 * check if username is available
	 *
	 * @param string
	 * @return bool
	 */
	public function isAvailable($name)
	{
		return !(bool) dibi::select('COUNT(*)')
							->from(self::TABLE)
							->where('username = %s', $name)
							->fetchSingle();
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
