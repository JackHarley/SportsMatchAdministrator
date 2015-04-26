<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\models;

use sma\Database;
use sma\exceptions\EmailAddressAlreadyRegisteredException;
use sma\exceptions\LoginCredentialsInvalidException;
use sma\exceptions\RequiredDataNotAvailableException;
use sma\query\InsertQuery;
use sma\query\SelectQuery;

/**
 * User
 *
 * @package \sma\models
 */
class User {

	/**
	 * @var int user id
	 */
	public $id;

	/**
	 * @var string full name
	 */
	public $fullName;

	/**
	 * @var string email
	 */
	public $email;

	/**
	 * @var string password hash
	 */
	public $passwordHash;
	const HASHING_ALGORITHM = PASSWORD_BCRYPT;
	const HASHING_COST = 10;

	/**
	 * @var \sma\models\UserGroup group
	 */
	public $group;

	/**
	 * @var int group id
	 */
	public $groupId;

	/**
	 * @var \sma\models\Permission[] permissions
	 */
	public $grantedPermissions = [];

	/**
	 * Quick Cache
	 *
	 * @var \sma\models\User current visitor
	 */
	protected static $visitor;

	/**
	 * Get the permissions of the user
	 *
	 * @throws \sma\exceptions\RequiredDataNotAvailableException user is not yet initialized
	 */
	public function constructGrantedPermissions() {
		if (!$this->group)
			throw new RequiredDataNotAvailableException();

		// get granted permissions
	}

	/**
	 * Check if the user satisfies a set of permissions
	 *
	 * @param \sma\models\Permission|\sma\models\Permission[] $permissions permissions to check
	 * @return bool true if the user satisfies the permissions, false otherwise
	 */
	public function checkPermissions($permissions) {
		if (!is_array($permissions))
			$permissions = [$permissions];

		foreach($permissions as $requiredPermission) {
			foreach($this->grantedPermissions as $grantedPermission) {
				if ($requiredPermission == $grantedPermission->name)
					continue 2;
			}
			return false;
		}
		return true;
	}


	/**
	 * Attempt to log in the current visitor
	 *
	 * @param string $email e-mail
	 * @param string $password password
	 * @param bool $remember remember login
	 * @throws \sma\exceptions\LoginCredentialsInvalidException
	 */
	public static function attemptLogin($email, $password, $remember=false) {
		if (!isset($usernameEmail) || (!isset($password)))
			throw new LoginCredentialsInvalidException();

		// do login
	}

	/**
	 * Logout the current visitor
	 */
	public static function logout() {
		ForumsFactory::getForumsInstance()->logoutVisitor();
	}

	/**
	 * Get the current visitor
	 *
	 * @return \sma\models\User visitor
	 */
	public static function getVisitor() {
		if (!static::$visitor) {
			// get active user
		}
		return static::$visitor;
	}

	/**
	 * The visitor is no longer up to date, rebuild it next time it is requested.
	 */
	public static function invalidateVisitorCache() {
		static::$visitor = null;
	}

	/**
	 * Check if an email address has been registered
	 *
	 * @param string $email email address to check
	 * @return boolean true if registered, otherwise false
	 */
	public static function checkIfEmailAddressExists($email) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("users")
				->where("email = ?", $email)
				->fields("id");
		$stmt = $q->prepare();
		$stmt->execute();

		return ($stmt->rowCount() > 0) ? true : false;
	}

	/**
	 * Get a user
	 *
	 * @param int $id user id
	 * @param string $username username
	 * @return \sma\models\User user
	 */
	public static function get($id, $username=null) {
		// get user
	}

	/**
	 * Add a new user
	 *
	 * @param string $email
	 * @param string $fullName
	 * @param string $password
	 * @param int $groupId initial user group id
	 * @return int new user id
	 * @throws EmailAddressAlreadyRegisteredException if the supplied email already exists as
	 * a user account
	 */
	public static function add($email, $fullName, $password, $groupId) {
		if (static::checkIfEmailAddressExists($email))
			throw new EmailAddressAlreadyRegisteredException();

		$passwordHash = password_hash($password, self::HASHING_ALGORITHM,
				["cost" => self::HASHING_COST]);

		(new InsertQuery(Database::getConnection()))
				->into("users")
				->fields(["email", "full_name", "password_hash", "group_id"])
				->values("(?,?,?,?)", [$email, $fullName, $passwordHash, $groupId])
				->prepare()
				->execute();

		return Database::getConnection()->lastInsertId();
	}
}