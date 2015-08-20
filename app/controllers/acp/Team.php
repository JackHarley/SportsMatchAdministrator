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
use sma\models\LeagueSection;
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

		if (!empty($_POST)) {
			TeamModel::update($_POST["id"], null, $_POST["designation"], $_POST["section"]);
			Controller::addAlert(new Alert("success", "Team details updated successfully"));
		}

		View::load("acp/team_manage.twig", [
				"team" => current(TeamModel::get($_GET["id"])),
				"sections" => LeagueSection::get()
		]);
	}
}