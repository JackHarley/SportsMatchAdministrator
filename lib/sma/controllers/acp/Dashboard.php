<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\acp;

use sma\Controller;
use sma\models\Organization;
use sma\models\Team;
use sma\View;

class Dashboard {

	public static function index() {
		Controller::requirePermissions(["AdminAccessDashboard"]);

		View::load("acp/index.twig", [
			"organizationCount" => count(Organization::get()),
			"teamCount" => count(Team::get()),
			"unassignedTeams" => Team::get(null, null, null, false)
		]);
	}
}