<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\models;

use PDO;
use sma\Database;
use sma\exceptions\NoSuchObjectException;
use sma\query\InsertQuery;
use sma\query\SelectQuery;

/**
 * User group
 *
 * @package \sma\models
 */
class UserGroup {

	/**
	 * @var int group id
	 */
	public $id;

	/**
	 * @var string group name
	 */
	public $name;

	/**
	 * @var bool true if the group is special, i.e. cannot be deleted
	 */
	public $special;

	/**
	 * @var \sma\models\Permission[] granted permissions
	 */
	protected $grantedPermissions = [];

	/**
	 * Add a user group
	 *
	 * @param string $name group name
	 * @param array|boolean $permissionNames array of permission names to grant or boolean true to
	 * grant all available permissions
	 * @param bool $special set to true if this is a special group (i.e. not deletable)
	 * @return int group id
	 */
	public static function add($name, $permissionNames=[], $special=false) {
		$db = Database::getConnection();

		(new InsertQuery($db))
			->into("user_groups")
			->fields(["name", "special"])
			->values("(?,?)", [$name, $special])
			->prepare()
			->execute();

		$id = $db->lastInsertId();

		if ($permissionNames === true)
			$permissions = Permission::get();
		else if (!empty($permissionNames))
			$permissions = Permission::get(null, $permissionNames);
		else
			$permissions = false;

		if ((is_array($permissions) && (!empty($permissions)))) {
			$permissionIds = [];
			foreach($permissions as $permission)
				$permissionIds[] = $permission->id;

			Permission::grantToGroup($id, $permissionIds);
		}

		return $id;
	}

	/**
	 * Get user groups
	 *
	 * @param int $id group id
	 * @param string $name group name
	 * @return \sma\models\UserGroup[] user groups
	 * @throws \sma\exceptions\NoSuchObjectException if no groups found
	 */
	public static function get($id=null, $name=null) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("user_groups ug")
				->fields(["ug.id AS group_id", "ug.name AS group_name", "ug.special AS group_special"])
				->join("LEFT JOIN user_groups_permissions ugp ON ug.id = ugp.group_id")
				->join("LEFT JOIN permissions p ON p.id = ugp.permission_id")
				->fields(["p.id AS permission_id", "p.type AS permission_type",
						"p.name AS permission_name", "p.description AS permission_description"]);

		if ($id)
			$q->where("ug.id = ?", $id);
		if ($name)
			$q->where("ug.name = ?", $name);

		$stmt = $q->prepare();
		$stmt->execute();

		if (!$stmt->rowCount())
			throw new NoSuchObjectException();

		$groups = [];
		while($data = $stmt->fetchObject()) {
			if (!array_key_exists($data->group_id, $groups)) {
				$group = new self;
				$group->id = $data->group_id;
				$group->name = $data->group_name;
				$group->special = $data->group_special;
				$groups[$group->id] = $group;
			}

			if (isset($data->permission_id)) {
				$permission = new Permission;
				$permission->id = $data->permission_id;
				$permission->type = $data->permission_type;
				$permission->name = $data->permission_name;
				$permission->description = $data->permission_description;
				$groups[$data->group_id]->permissions[$permission->id] = $permission;
			}
		}

		return $groups;
	}

	/**
	 * Get the permissions of the group
	 *
	 * @return \sma\models\Permission[] granted permissions
	 */
	public function getGrantedPermissions() {
		if (!$this->grantedPermissions) {
			$q = (new SelectQuery(Database::getConnection()))
					->from("user_groups_permissions ugp")
					->where("group_id = ?", $this->id)
					->join("LEFT JOIN permissions p ON p.id=ugp.permission_id")
					->fields(["p.id", "p.type", "p.name", "p.description"]);
			$stmt = $q->prepare();
			$stmt->execute();

			while($row = $stmt->fetch(PDO::FETCH_NUM)) {
				$permission = new Permission();
				list($permission->id, $permission->type, $permission->name,
						$permission->description) = $row;
				$this->grantedPermissions[] = $permission;
			}
		}

		return $this->grantedPermissions;
	}
}