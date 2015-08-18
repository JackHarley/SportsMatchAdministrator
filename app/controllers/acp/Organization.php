<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\acp;

use sma\Controller;
use sma\exceptions\DuplicateException;
use sma\models\Alert;
use sma\models\Organization as OrganizationModel;
use sma\View;

class Organization {

	public static function index() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminOrganizations"]);
		View::load("acp/organization.twig", [
			"objects" => OrganizationModel::get()
		]);
	}

	public static function add() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminOrganizations"]);
		Controller::requireFields("post", ["name"], "/acp/organization");

		try {
			OrganizationModel::add($_POST["name"]);

			Controller::addAlert(new Alert("success", "Organization added successfully"));
			Controller::redirect("/acp/organization");
		}
		catch (DuplicateException $e) {
			Controller::addAlert(new Alert("danger", "Organization name is already used, please choose an alternative name and try again"));
			Controller::redirect("/acp/organization");
		}
	}

	public static function delete() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminOrganizations"]);
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