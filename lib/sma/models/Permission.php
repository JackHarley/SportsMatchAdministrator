<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\models;

use sma\Database;
use sma\query\DeleteQuery;
use sma\query\InsertQuery;
use sma\query\SelectQuery;

/**
 * Permission
 *
 * @package \sma\models
 */
class Permission {

	/**
	 * @var int permission id
	 */
	public $id;

	/**
	 * @var string permission type
	 */
	public $type;

	/**
	 * @var string permission name
	 */
	public $name;

	/**
	 * @var string permission description
	 */
	public $description;

	/**
	 * Add a new permission in the database
	 *
	 * @param string $type permission type string, sort of a namespace, e.g. Tools, Admin, etc.
	 * (for display purposes only)
	 * @param string $name permission name as it will be referenced in the code when required
	 * @param string $description permission description (for display purposes only)
	 * @return int id of the newly created permission
	 */
	public static function add($type, $name, $description) {
		$db = Database::getConnection();

		(new InsertQuery($db))
			->into("permissions")
			->fields(["type", "name", "description"])
			->values("(?,?,?)", [$type, $name, $description])
			->prepare()
			->execute();

		return $db->lastInsertId();
	}

	/**
	 * Gets permissions
	 *
	 * @param int $id permission id or null to not restrict by id
	 * @param array|string $name permission name(s) or null to not restrict by name
	 * @param int $limit number of records to fetch or null for no limit
	 * @return Permission[] permissions
	 */
	public static function get($id=null, $name=null, $limit=null) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("permissions")
				->fields(["id", "type", "name", "description"])
				->orderby("name");

		if ($id !== null)
			$q->where("id = ?", $id);

		if ($name !== null) {
			if (is_array($name))
				$q->whereInArray("name", $name);
			else
				$q->where("name = ?", $name);
		}

		if ($limit !== null)
			$q->limit($limit);

		$stmt = $q->prepare();
		$stmt->execute();

		$permissions = [];
		while($permissionData = $stmt->fetchObject()) {
			$permission = new self;
			$permission->id = $permissionData->id;
			$permission->type = $permissionData->type;
			$permission->name = $permissionData->name;
			$permission->description = $permissionData->description;
			$permissions[$permission->id] = $permission;
		}

		return $permissions;
	}

	/**
	 * Grants permission(s) to a group
	 *
	 * @param int $groupId group id
	 * @param int|array $permissionIds permission id(s)
	 */
	public static function grantToGroup($groupId, $permissionIds) {
		$q = (new InsertQuery(Database::getConnection()))
				->into("user_groups_permissions")
				->fields(["group_id", "permission_id"])
				->extraClause("ON DUPLICATE KEY UPDATE group_id=group_id");

		if (!is_array($permissionIds))
			$permissionIds = [$permissionIds];

		foreach($permissionIds as $permissionId) {
			$q->values("(?,?)", [$groupId, $permissionId]);
		}

		$stmt = $q->prepare();
		$stmt->execute();
	}

	/**
	 * Revokes permission(s) from a group
	 *
	 * @param int $groupId group id
	 * @param int|array $permissionIds permission id(s)
	 */
	public static function revokeFromGroup($groupId, $permissionIds) {
		if (!is_array($permissionIds))
			$permissionIds = [$permissionIds];

		(new DeleteQuery(Database::getConnection()))
			->from("user_groups_permissions")
			->where("group_id = ?", $groupId)
			->whereInArray("permission_id", $permissionIds)
			->limit(1)
			->prepare()
			->execute();
	}
}