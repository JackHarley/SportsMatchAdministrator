<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\acp;

use sma\Controller;
use sma\models\Alert;
use sma\models\Permission;
use sma\models\UserGroup as UserGroupModel;
use sma\View;

class UserGroup {

	public static function index() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminUserGroups"]);
		View::load("acp/user_group.twig", [
				"objects" => UserGroupModel::get()
		]);
	}

	public static function add() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminUserGroups"]);
		Controller::requireFields("post", ["name"], "/acp/group");

		$id = UserGroupModel::add($_POST["name"]);

		Controller::addAlert(new Alert("success", "User group added successfully, you can now grant permissions to it below"));
		Controller::redirect("/acp/group/manage?id=" . $id);
	}

	public static function manage() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminUserGroups"]);

		$permissions = Permission::get();

		if (!empty($_POST)) {
			UserGroupModel::update($_POST["id"], $_POST["name"]);
			foreach($permissions as $permission) {
				if (isset($_POST["permission-" . $permission->id]))
					Permission::grantToGroup($_POST["id"], $permission->id);
				else
					Permission::revokeFromGroup($_POST["id"], $permission->id);
			}

			Controller::addAlert(new Alert("success", "User group updated successfully"));
		}

		View::load("acp/user_group_manage.twig", [
				"object" => current(UserGroupModel::get($_GET["id"])),
				"permissions" => $permissions
		]);
	}

	public static function delete() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminUserGroups",
				"PerformDeletionOperations"]);

		if (!array_key_exists("id", $_GET))
			Controller::redirect("/acp/group");

		$group = current(UserGroupModel::get($_GET["id"]));

		if (!$group)
			Controller::addAlert(new Alert("danger", "The specified group does not exist"));
		else if ($group->special)
			Controller::addAlert(new Alert("danger", "The specified group is a special group and cannot be deleted as it would break core functionality"));
		else if (($count = count($group->getUsers())) > 0)
			Controller::addAlert(new Alert("danger", "There are " . $count . " users currently in ".
					"the specified group, you must assign them to a different group before you can delete this group"));
		else {
			$group->delete();
			Controller::addAlert(new Alert("success", "User group deleted successfully"));
		}

		Controller::redirect("/acp/group");
	}
}