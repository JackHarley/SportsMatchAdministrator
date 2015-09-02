<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\models;

use PDO;
use sma\controllers\acp\Organization;
use sma\Database;
use sma\exceptions\EmailAddressAlreadyRegisteredException;
use sma\exceptions\LoginCredentialsInvalidException;
use sma\exceptions\RequiredDataNotAvailableException;
use sma\query\DeleteQuery;
use sma\query\InsertQuery;
use sma\query\SelectQuery;
use sma\query\UpdateQuery;

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
	 * @var string phone number
	 */
	public $phoneNumber;

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
	 * @var \sma\models\Organization organization
	 */
	public $organization;

	/**
	 * @var int organization id
	 */
	public $organizationId;

	/**
	 * @var \sma\models\Permission[] permissions
	 */
	protected $grantedPermissions = [];

	/**
	 * Quick Cache
	 *
	 * @var \sma\models\User current visitor
	 */
	protected static $visitor;

	/**
	 * Delete the user
	 */
	public function delete() {
		(new DeleteQuery(Database::getConnection()))
				->from("users")
				->where("id = ?", $this->id)
				->limit(1)
				->prepare()
				->execute();
	}

	/**
	 * Get the permissions of the user
	 *
	 * @throws \sma\exceptions\RequiredDataNotAvailableException user is not yet initialized
	 * @return \sma\models\Permission[] granted permissions
	 */
	public function getGrantedPermissions() {
		if (!$this->grantedPermissions) {
			if (!$this->group)
				throw new RequiredDataNotAvailableException();

			return $this->group->getGrantedPermissions();
		}

		return $this->grantedPermissions;
	}

	/**
	 * Check if the user satisfies a set of permissions
	 *
	 * @param \sma\models\Permission|\sma\models\Permission[] $permissions permissions to check
	 * @param string $requirement 'all' to require all permissions listed, 'any' to require at least
	 * one of them
	 * @return bool true if the user satisfies the permissions, false otherwise
	 */
	public function checkPermissions($permissions, $requirement="all") {
		if (!is_array($permissions))
			$permissions = [$permissions];

		foreach($permissions as $requiredPermission) {
			foreach($this->getGrantedPermissions() as $grantedPermission) {
				if ($requiredPermission == $grantedPermission->name) {
					if ($requirement == "any")
						return true;
					else
						continue 2;
				}
			}
			return false;
		}
		return true;
	}

	/**
	 * Rehash a user's password if necessary
	 *
	 * @param string $password **correct** password for user
	 */
	public function rehashUserPasswordIfNecessary($password) {
		if (password_needs_rehash($this->passwordHash, self::HASHING_ALGORITHM,
				["cost" => self::HASHING_COST])) {

			$newPasswordHash = password_hash($password, self::HASHING_ALGORITHM,
					["cost" => self::HASHING_COST]);

			(new UpdateQuery(Database::getConnection()))
					->table("users")
					->set("password_hash = ?", $newPasswordHash)
					->where("id = ?", $this->id)
					->limit(1)
					->prepare()
					->execute();
		}
	}

	/**
	 * Set a cookie based auto-login up for the user on the current browser
	 */
	public function createAutologin() {
		$this->trimAutologins(2); // max=3, so we need max=2 since we're adding a new one

		$parametersHash = static::generateBrowserParametersHash();
		$key = hash("sha256", mt_rand());
		$keyHash = password_hash($key, self::HASHING_ALGORITHM, ["cost" => self::HASHING_COST]);

		$stmt = (new InsertQuery(Database::getConnection()))
				->into("autologins")
				->fields(["user_id", "browser_parameters_hash", "key_hash", "epoch_created"])
				->values("(?,?,?,?)", [$this->id, $parametersHash, $keyHash, time()])
				->prepare();

		$stmt->execute();

		if ($stmt->rowCount() == 1) {
			$id = Database::getConnection()->lastInsertId();
			setcookie("autologin_id", $id, time()+60*60*24*365, "/", COOKIE_DOMAIN, SSL);
			setcookie("autologin_key", $key, time()+60*60*24*365, "/", COOKIE_DOMAIN, SSL);
		}
	}

	/**
	 * Trim user autologins to specified number, deleting the ones with the oldest last used
	 * time if necessary
	 *
	 * @param int $maximum maximum number of autologins user should have
	 */
	public function trimAutologins($maximum) {
		$stmt = (new SelectQuery(Database::getConnection()))
				->from("autologins")
				->fields(["epoch_last_used"])
				->where("user_id = ?", $this->id)
				->orderby("epoch_last_used", "DESC") // most recently used first
				->prepare();
		$stmt->execute();

		$rows = $stmt->fetchAll(PDO::FETCH_OBJ);

		if (count($rows) >= $maximum) {
			(new DeleteQuery(Database::getConnection()))
					->from("autologins")
					->where("epoch_last_used <= ?", $rows[$maximum-1]->epoch_last_used)
					->prepare()
					->execute();
		}
	}

	/**
	 * Delete autologins for user based on specific criteria, providing no criteria will delete
	 * all of the user's autologins
	 *
	 * @param string $browserParametersHash delete only autologins matching the provided parameter
	 * hash
	 */
	public function deleteAutologins($browserParametersHash=null) {
		$q = (new DeleteQuery(Database::getConnection()))
				->from("autologins")
				->where("user_id = ?", $this->id);

		if ($browserParametersHash)
			$q->where("browser_parameters_hash = ?", $browserParametersHash);

		$q->prepare()->execute();
	}

	/**
	 * Generate the browser parameters hash for the current browser
	 *
	 * @return string browser parameters hash
	 */
	public static function generateBrowserParametersHash() {
		return hash("sha256", $_SERVER["HTTP_USER_AGENT"]);
	}

	/**
	 * Attempt to log in the current visitor using an autologin stored in the browser
	 */
	public static function attemptAutologin() {
		if ((!array_key_exists("autologin_id", $_COOKIE)) || (!array_key_exists("autologin_key", $_COOKIE)))
			return;

		$stmt = (new SelectQuery(Database::getConnection()))
				->fields(["user_id", "browser_parameters_hash", "key_hash"])
				->from("autologins")
				->where("id = ?", $_COOKIE["autologin_id"])
				->limit(1)
				->prepare();
		$stmt->execute();

		$row = $stmt->fetchObject();
		if (!$row) {
			setcookie("autologin_id", false, time()-60*60*24*265, "/", COOKIE_DOMAIN, SSL);
			setcookie("autologin_key", false, time()-60*60*24*265, "/", COOKIE_DOMAIN, SSL);
			return;
		}

		// verify key
		if (!password_verify($_COOKIE["autologin_key"], $row->key_hash))
			return;

		// verify browser parameters
		if ($row->browser_parameters_hash != static::generateBrowserParametersHash())
			return;

		// login
		$_SESSION["user_id"] = $row->user_id;
		(new UpdateQuery(Database::getConnection()))
				->table("autologins")
				->set("epoch_last_used = ?", time())
				->where("id = ?", $_COOKIE["autologin_id"])
				->prepare()
				->execute();
	}

	/**
	 * Attempt to log in the current visitor
	 *
	 * @param string $email e-mail
	 * @param string $password password
	 * @param bool $autologin create autologin
	 * @throws \sma\exceptions\LoginCredentialsInvalidException
	 */
	public static function attemptLogin($email, $password, $autologin=false) {
		if (!isset($email) || (!isset($password)))
			throw new LoginCredentialsInvalidException();

		$users = static::get(null, $email);
		if (empty($users))
			throw new LoginCredentialsInvalidException();

		$user = $users[0];

		if (!password_verify($password, $user->passwordHash))
			throw new LoginCredentialsInvalidException();

		$user->rehashUserPasswordIfNecessary($password);
		$_SESSION["user_id"] = $user->id;
		if ($autologin)
			$user->createAutologin();
	}

	/**
	 * Logout the current visitor
	 */
	public static function logout() {
		$user = static::getVisitor();
		$user->deleteAutologins(static::generateBrowserParametersHash()); // only current browser
		unset($_SESSION["user_id"]);
	}

	/**
	 * Logout the current visitor from all devices (clear autologins)
	 */
	public static function logoutAll() {
		$user = static::getVisitor();
		$user->deleteAutologins();
		unset($_SESSION["user_id"]);
	}

	/**
	 * Get the current visitor
	 *
	 * @return \sma\models\User visitor
	 */
	public static function getVisitor() {
		if (!static::$visitor) {
			if (!array_key_exists("user_id", $_SESSION))
				self::attemptAutologin();

			if (array_key_exists("user_id", $_SESSION)) {
				static::$visitor = self::get($_SESSION["user_id"])[0];
			}
			else {
				$guestGroup = current(UserGroup::get(null, "Guest"));

				$user = new User();
				$user->id = 0;
				$user->group = $guestGroup;
				$user->groupId = $guestGroup->id;

				static::$visitor = $user;
			}
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
	 * Get objects
	 *
	 * @param int $id id
	 * @param string $email email
	 * @param int $groupId user group id
	 * @return \sma\models\User[] users
	 */
	public static function get($id=null, $email=null, $groupId=null) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("users u")
				->fields(["u.id", "u.email", "u.password_hash", "u.full_name", "u.phone_number",
						"u.group_id", "u.organization_id"])
				->join("LEFT JOIN user_groups ug ON ug.id = u.group_id")
				->fields(["ug.id AS user_group_id", "ug.name AS group_name", "ug.special AS group_special"])
				->join("LEFT JOIN organizations o ON o.id = u.organization_id")
				->fields(["o.id AS org_id", "o.name AS organization_name"])
				->orderby("u.full_name");

		if ($id)
			$q->where("u.id = ?", $id);
		if ($email)
			$q->where("u.email = ?", $email);
		if ($groupId)
			$q->where("u.group_id = ?", $groupId);

		$stmt = $q->prepare();
		$stmt->execute();

		$users = [];
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$user = new self;
			$user->group = new UserGroup();
			$user->organization = new Organization();
			list($user->id, $user->email, $user->passwordHash, $user->fullName, $user->phoneNumber,
					$user->groupId, $user->organizationId, $user->group->id, $user->group->name,
					$user->group->special, $user->organization->id, $user->organization->name) = $row;
			$users[] = $user;
		}

		return $users;
	}

	/**
	 * Add a new user
	 *
	 * @param string $email
	 * @param string $fullName
	 * @param string $phoneNumber
	 * @param string $password
	 * @param int $groupId initial user group id
	 * @param int $organizationId organization user is from
	 * @return int new user id
	 * @throws EmailAddressAlreadyRegisteredException if the supplied email already exists as
	 * a user account
	 */
	public static function add($email, $fullName, $phoneNumber, $password, $groupId,
			$organizationId=null) {
		if (static::checkIfEmailAddressExists($email))
			throw new EmailAddressAlreadyRegisteredException();

		$passwordHash = password_hash($password, self::HASHING_ALGORITHM,
				["cost" => self::HASHING_COST]);

		(new InsertQuery(Database::getConnection()))
				->into("users")
				->fields(["email", "full_name", "phone_number", "password_hash", "group_id",
						"organization_id"])
				->values("(?,?,?,?,?,?)", [$email, $fullName, $phoneNumber, $passwordHash, $groupId,
						$organizationId])
				->prepare()
				->execute();

		return Database::getConnection()->lastInsertId();
	}

	/**
	 * Update a user
	 *
	 * @param int $id user id
	 * @param string $email
	 * @param string $fullName
	 * @param string $phoneNumber
	 * @param string $password
	 * @param int $groupId initial user group id
	 * @param int $organizationId organization user is from
	 */
	public static function update($id, $email=null, $fullName=null, $phoneNumber=null, $password=null,
			$groupId=null, $organizationId=null) {

		$q = (new UpdateQuery(Database::getConnection()))
				->table("users")
				->where("id = ?", $id)
				->limit(1);

		if ($password) {
			$passwordHash = password_hash($password, self::HASHING_ALGORITHM,
					["cost" => self::HASHING_COST]);
			$q->set("password_hash = ?", $passwordHash);
		}
		if ($email)
			$q->set("email = ?", $email);
		if ($fullName)
			$q->set("full_name = ?", $fullName);
		if ($phoneNumber)
			$q->set("phone_number = ?", $phoneNumber);
		if ($groupId)
			$q->set("group_id = ?", $groupId);
		if ($organizationId !== null) {
			if ($organizationId === 0)
				$q->set("organization_id = NULL");
			else
				$q->set("organization_id = ?", $organizationId);
		}

		$q->prepare()->execute();
	}
}