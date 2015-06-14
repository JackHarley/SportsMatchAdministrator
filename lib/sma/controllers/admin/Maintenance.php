<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\admin;

use sma\Controller;
use sma\models\Alert;
use sma\models\Division;
use sma\View;

class Maintenance {

	public static function index() {
		Controller::requirePermissions(["AdminAccessMaintenance"]);

		View::load("admin/maintenance.twig", [
			"divisionUserGroupInconsistencies" => Division::syncForumUserGroups(),
			"inGameClanDivisionInconsistencies" => Division::syncInGameClans()
		]);
	}

	public static function resolveDivisionUserGroupInconsistencies() {
		Controller::requirePermissions(["AdminAccessMaintenance"]);

		$actions = Division::syncForumUserGroups(true);

		Controller::addAlert(new Alert("success", "The detected division user group " .
				"inconsistencies have been resolved successfully"));

		View::load("admin/maintenance_complete.twig", [
			"completedActions" => $actions
		]);
	}

	public static function resolveInGameClanDivisionInconsistencies() {
		Controller::requirePermissions(["AdminAccessMaintenance"]);

		$actions = Division::syncInGameClans(true);

		Controller::addAlert(new Alert("success", "The detected in-game clan division " .
				"inconsistencies have been resolved successfully"));

		View::load("admin/maintenance_complete.twig", [
				"completedActions" => $actions
		]);
	}
}