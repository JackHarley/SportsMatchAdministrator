<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\admin;

use sma\Controller;
use sma\ErrorHandler;
use sma\exceptions\ArgumentsInvalidException;
use sma\exceptions\NoSuchObjectException;
use sma\models\Alert;
use sma\models\UserGroup;
use sma\models\GroupPermission;
use sma\models\Permission;
use sma\View;

class UserGroupPermissions {

	public static function index() {
		Controller::requirePermissions(["AdminAccessUserGroupPermissions"]);

		View::load("admin/user_group_permissions/list.twig", [
			"groups" => UserGroup::getUserGroups()
		]);
	}

	public static function group($id) {
		Controller::requirePermissions([
			"AdminAccessUserGroupPermissions",
			"AdminManageUserGroupPermissions"
		]);

		if (!$id)
			ErrorHandler::notFound();

		try {
			$group = UserGroup::getUserGroup($id);
		}
		catch (NoSuchObjectException $e) {
			ErrorHandler::notFound();
		}

		if (empty($_POST)) {
			View::load("admin/user_group_permissions/edit.twig", [
				"group" => $group,
				"permissions" => Permission::getPermissions()
			]);
		}
		else {
			foreach($_POST as $permissionId => $granted) {
				try {
					GroupPermission::alterGroupPermission($group->id, $permissionId, (int) $granted);
				}
				catch (ArgumentsInvalidException $e) {
					Controller::addAlert(new Alert("error", "The form was filled out incorrectly, please try again"));
				}
			}

			Controller::addAlert(new Alert("success", "Permissions for this group have been updated successfully"));
			Controller::redirect("/admin/permissions/group/" . $group->id);
		}
	}
}