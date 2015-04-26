<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\models;

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
	 * @var \sma\models\Permission[] granted permissions
	 */
	public $permissions = [];

	/**
	 * Add a user group
	 *
	 * @param string $name group name
	 * @param array|boolean $permissionNames array of permission names to grant or boolean true to
	 * grant all available permissions
	 * @return int group id
	 */
	public static function add($name, $permissionNames=[]) {
		$db = Database::getConnection();

		(new InsertQuery($db))
			->into("user_groups")
			->fields("name")
			->values("(?)", $name)
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
				->fields(["ug.id AS group_id", "ug.name AS group_name"])
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
}