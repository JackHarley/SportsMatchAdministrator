<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\acp;

use sma\Controller;
use sma\models\Team;
use sma\exceptions\DuplicateException;
use sma\models\Alert;
use sma\models\League;
use sma\models\Match as MatchModel;
use sma\models\MatchReport;
use sma\View;

class Match {

	public static function index() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminMatches"]);

		$reports = MatchReport::get();

		if ((array_key_exists("league", $_GET)) && ($_GET["league"] != 0)) {
			foreach($reports as $key => $report) {
				if ($reports[$key]->getMatch()->leagueId != $_GET["league"])
					unset($reports[$key]);
			}
		}
		if ((array_key_exists("status", $_GET)) && ($_GET["status"] !== "")) {
			foreach($reports as $key => $report) {
				if ($reports[$key]->getMatch()->status != $_GET["status"])
					unset($reports[$key]);
			}
		}

		View::load("acp/match.twig", [
			"objects" => $reports,
			"leagues" => League::get(),
			"selectedLeagueId" => (array_key_exists("league", $_GET)) ? $_GET["league"] : 0,
			"selectedStatus" => ((array_key_exists("status", $_GET)) && ($_GET["status"] !== '')) ? $_GET["status"] : -1,
		]);
	}

	public static function manage() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminMatches"]);
		$match = current(MatchModel::get($_GET["id"]));

		if (!$match) {
			Controller::addAlert(new Alert("danger", "The match you specified could not be found."));
			Controller::redirect("/acp/match");
		}

		View::load("acp/match_manage.twig", [
			"match" => $match,
			"teams" => Team::get(null, null, null, null, $match->leagueId)
		]);
	}

	public static function delete() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminMatches"]);

		$match = current(MatchModel::get($_GET["id"]));
		$match->delete();

		Controller::addAlert(new Alert("success", "Match, match reports and participating player records deleted"));
		Controller::redirect("/acp/match");
	}

	public static function correct() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminMatches"]);

		$report = MatchReport::get($_GET["report"])[0];
		$match = $report->getMatch();

		if ($match->status == MatchModel::STATUS_MISMATCH) {
			$match->correctReports($report->id);
			Controller::addAlert(new Alert("success", "Correction completed"));
		}
		else {
			Controller::addAlert(new Alert("danger", "This match cannot be corrected as it is not currently in a mismatched state"));
		}

		Controller::redirect("/acp/match/manage?id=" . $match->id);
	}

	public static function alter() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminMatches"]);

		$id = $_POST["id"];
		$match = current(MatchModel::get($_POST["id"]));

		if (array_key_exists("date", $_POST)) {
			try {
				$id = $match->correctDate($match->id, $_POST["date"]);
				Controller::addAlert(new Alert("success", "Correction completed"));
			}
			catch (DuplicateException $e) {
				Controller::addAlert(new Alert("danger",
					"The report cannot be moved to the specified date as there is already another report filed for the team for the match on that date"));
			}
		}
		else if (array_key_exists("home_team_id", $_POST)) {
			try {
				$homeTeamId = ($_POST["home_team_id"] != 0) ? $_POST["home_team_id"] : null;
				$awayTeamId = ($_POST["away_team_id"] != 0) ? $_POST["away_team_id"] : null;

				$id = $match->correctTeams($match->id, $homeTeamId, $awayTeamId);
				Controller::addAlert(new Alert("success", "Correction completed"));
			}
			catch (DuplicateException $e) {
				Controller::addAlert(new Alert("danger",
					"The report cannot be updated with those team(s) as there is already another report filed for the team for the match on that date"));
			}
		}

		Controller::redirect("/acp/match/manage?id=" . $id);
	}
}