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
		Controller::requireFields("post", ["name"], "/acp/organization");

		if (count(OrganizationModel::get(null, $_POST["name"])) > 0) {
			Controller::addAlert(new Alert("danger", "Organization name is already used, please choose an alternative name and try again"));
			Controller::redirect("/acp/organization");
		}


		OrganizationModel::add($_POST["name"]);
		Controller::addAlert(new Alert("success", "Organization added successfully"));
		Controller::redirect("/acp/organization");
	}

	public static function delete() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminUsers"]);

		if (!array_key_exists("id", $_GET))
			Controller::redirect("/acp/organization");

		$orgs = OrganizationModel::get($_GET["id"]);

		if (!empty($orgs)) {
			current($orgs)->delete();
			Controller::addAlert(new Alert("success", "Organization deleted successfully"));
		}
		else {
			Controller::addAlert(new Alert("danger",
					"The organization you attempted to delete does not exist"));
		}

		Controller::redirect("/acp/organization");
	}
}