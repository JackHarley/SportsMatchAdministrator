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
use sma\models\League;
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
			if (!array_key_exists("section", $_POST))
				$_POST["section"] = null;

			TeamModel::update($_POST["id"], null, $_POST["designation"], $_POST["section"], $_POST["league"]);
			Controller::addAlert(new Alert("success", "Team details updated successfully"));
		}

		$team = current(TeamModel::get($_GET["id"]));
		$sections = ($team->leagueId) ? LeagueSection::get(null, $team->leagueId) : null;

		View::load("acp/team_manage.twig", [
				"team" => $team,
				"leagues" => League::get(),
				"sections" => $sections
		]);
	}

	public static function assign() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminTeams"]);
		Controller::requireFields("get", ["id", "section"], "/acp/league");

		$section = current(LeagueSection::get($_GET["section"]));
		TeamModel::update($_GET["id"], null, null, $section->id, $section->leagueId);

		Controller::addAlert(new Alert("success", "Team assigned to section successfully"));
		Controller::redirect("/acp/league/manage?id=" . $section->leagueId);
	}

	public static function delete() {
		Controller::requireFields("get", ["id"], "/acp/team");
		Controller::requirePermissions(["AdminAccessDashboard", "AdminTeams", "AdminPlayers",
				"PerformDeletionOperations"]);

		$team = current(TeamModel::get($_GET["id"]));
		$team->delete();

		Controller::addAlert(new Alert("success", "Team deleted successfully"));
		Controller::redirect("/acp/team");
	}
}