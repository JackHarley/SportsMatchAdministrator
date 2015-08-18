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
use sma\models\Team as TeamModel;
use sma\View;

class Team {

	public static function index() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminTeams"]);
		View::load("acp/team.twig", [
			"objects" => TeamModel::get()
		]);
	}

	public static function manage() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminTeams"]);
		View::load("acp/team_manage.twig", [
				"object" => TeamModel::get($_GET["id"])
		]);
	}
}