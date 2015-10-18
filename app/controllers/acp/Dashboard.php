<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\acp;

use sma\Controller;
use sma\models\Match;
use sma\models\Organization;
use sma\models\Setting;
use sma\models\Team;
use sma\View;

class Dashboard {

	public static function index() {
		Controller::requirePermissions(["AdminAccessDashboard"]);

		if (!empty($_POST)) {
			Setting::set("info_box_content", $_POST["info"]);
		}

		View::load("acp/index.twig", [
			"organizationCount" => count(Organization::get()),
			"teamCount" => count(Team::get()),
			"unassignedTeams" => Team::get(null, null, null, false, false),
			"info" => Setting::get("info_box_content"),
			"mismatches" => Match::get(null, null, null, null, null, Match::STATUS_MISMATCH)
		]);
	}
}