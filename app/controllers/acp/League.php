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
use sma\models\Fixture;
use sma\models\League as LeagueModel;
use sma\models\User;
use sma\models\Team;
use sma\View;

class League {

	public static function index() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminAllLeagues"]);
		View::load("acp/league.twig", [
			"objects" => LeagueModel::get(),
			"users" => User::get()
		]);
	}

	public static function add() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminAllLeagues"]);
		Controller::requireFields("post", ["name"], "/acp/league");

		LeagueModel::add($_POST["name"], $_POST["manager"]);

		Controller::addAlert(new Alert("success", "League added successfully"));
		Controller::redirect("/acp/league");
	}

	public static function manage() {
		$league = current(LeagueModel::get($_GET["id"]));

		// check permissions
		$visitor = User::getVisitor();
		if ($visitor->id != $league->managerId)
			Controller::requirePermissions(["AdminAllLeagues"]);

		if (!empty($_POST)) {
			if (array_key_exists("update-team-numbers", $_POST)) {
				$teams = $league->getAssignedTeams();

				foreach ($teams as $team) {
					if (array_key_exists("team" . $team->id . "number", $_POST))
						Team::update($team->id, null, null, null, null, $_POST["team" . $team->id . "number"]);
				}

				Controller::addAlert(new Alert("success", "Team assigned numbers updated successfully"));
			}
			else if (array_key_exists("update-league-details", $_POST)) {
				LeagueModel::update($_POST["id"], $_POST["name"], $_POST["manager"]);
				Controller::addAlert(new Alert("success", "League details updated successfully"));
				$league = current(LeagueModel::get($_POST["id"]));
			}
		}

		// construct fixtures
		$fixtures = Fixture::get(null, $league->id);

		View::load("acp/league_manage.twig", [
			"users" => User::get(),
			"league" => $league,
			"fixtures" => $fixtures,
			"unassignedTeams" => Team::get(null, null, null, false, $_GET["id"])
		]);
	}

	public static function delete() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminAllLeagues",
				"PerformDeletionOperations"]);

		if (!array_key_exists("id", $_GET))
			Controller::redirect("/acp/league");

		$leagues = LeagueModel::get($_GET["id"]);

		if (!empty($leagues)) {
			current($leagues)->delete();
			Controller::addAlert(new Alert("success", "League deleted successfully"));
		}
		else {
			Controller::addAlert(new Alert("danger",
					"The league you attempted to delete does not exist"));
		}

		Controller::redirect("/acp/league");
	}
}