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
	const USER_LEVELS_TABLE = 'user_levels';
	const EXPIRY_DAYS = 3; //how many days user has to confirm registration
	
	/** @dbsync (#user_levels.name)*/
	const UL_SUPERADMIN = 'superadmin';
	const UL_ADMIN = 'admin';
	const UL_PROJECT_MANAGER = 'project-manager';
	const UL_DESIGNER = 'designer';
	const UL_CLIENT = 'client';
	
	/** @dbsync (#user_levels.id)*/
	const UL_SUPERADMIN_ID = 1;
	const UL_ADMIN_ID = 2;
	const UL_PROJECT_MANAGER_ID = 3;
	const UL_DESIGNER_ID = 4;
	const UL_CLIENT_ID = 5;
	
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

		unset($row->password);
		return new Identity($row->username, $row->role, $row);
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
	
	
	/**
	 * find all users
	 *  (optionally limited to roles weaker than given $userLevelId)
	 * 
	 * @return DibiRow array
	 */
	public function findAll($fetch = true, $userLevelId = 0)
	{
		$ret = dibi::select('u.*, ul.name AS role, ul.public_name AS publicRole')
				->from(self::TABLE)
					->as('u')
				->leftJoin(self::USER_LEVELS_TABLE)
					->as('ul')
					->on('u.user_levels_id = ul.id')
				->where('u.user_levels_id > %i', $userLevelId); // limit to weaker roles only!

		if ($fetch) {
			$ret = $ret->fetchAll();
		}

		return $ret;
	}

	
	public function insert(array $data)
	{
		$data['token'] = md5($data['email'] . $data['username']);
		$data['password'] = self::getHash($data['username'], $data['password']);
		$data['registered'] = dibi::datetime();

		return parent::insert($data);
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
					->where('user_levels_id = %i', $userLevelId)
					->fetchPairs('id', 'name');
	}
	
	
	/**
	 * find user roles (optionally limited to roles weaker than given $userLevelId)
	 *
	 * @param int #ul.id
	 * @return array
	 */
//	public static function findRoles()
	public function findRoles($userLevelId = 0)
	{
		return dibi::select('id, public_name')
					->from(self::USER_LEVELS_TABLE)
					->where('id > %i', $userLevelId) // limit to weaker roles only!
					->fetchPairs('id', 'public_name');
	}
}
