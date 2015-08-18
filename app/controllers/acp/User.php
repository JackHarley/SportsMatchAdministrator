<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\acp;

use sma\Controller;
use sma\exceptions\EmailAddressAlreadyRegisteredException;
use sma\models\Alert;
use sma\models\Organization;
use sma\models\User as UserModel;
use sma\models\UserGroup;
use sma\View;

class User {

	public static function index() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminUsers"]);
		View::load("acp/user.twig", [
				"objects" => UserModel::get(),
				"groups" => UserGroup::get(),
				"organizations" => Organization::get()
		]);
	}

	public static function add() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminUsers"]);
		Controller::requireFields("post", ["email", "password", "full-name", "phone-number", "group", "organization"], "/acp/user");

		if (count(UserModel::get(null, $_POST["email"])) > 0) {
			Controller::addAlert(new Alert("danger", "Email is already registered, please use a different one and try again."));
			Controller::redirect("/acp/user");
		}

		try {
			UserModel::add($_POST["email"], $_POST["full-name"], $_POST["phone-number"], $_POST["password"], $_POST["group"], $_POST["organization"]);
		}
		catch (EmailAddressAlreadyRegisteredException $e) {
			Controller::addAlert(new Alert("danger", "Email is already registered, please use a different one and try again."));
			Controller::redirect("/acp/user");
		}

		Controller::addAlert(new Alert("success", "User added successfully"));
		Controller::redirect("/acp/user");
	}

	public static function edit() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminUsers"]);

		if (empty($_POST)) {
			View::load("acp/user_edit.twig", [
					"object" => current(UserModel::get($_GET["id"])),
					"groups" => UserGroup::get(),
					"organizations" => Organization::get()
			]);
		}
		else {
			UserModel::update($_POST["id"], $_POST["email"], $_POST["full-name"], $_POST["phone-number"],
					$_POST["password"], $_POST["group"], $_POST["organization"]);
		}

		Controller::addAlert(new Alert("success", "User updated successfully"));
		Controller::redirect("/acp/user");
	}

	public static function delete() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminUsers"]);

		if (!array_key_exists("id", $_GET))
			Controller::redirect("/acp/user");

		$users = UserModel::get($_GET["id"]);

		if (!empty($users)) {
			current($users)->delete();
			Controller::addAlert(new Alert("success", "User deleted successfully"));
		}
		else {
			Controller::addAlert(new Alert("danger",
					"The user you attempted to delete does not exist"));
		}

		Controller::redirect("/acp/user");
	}
}