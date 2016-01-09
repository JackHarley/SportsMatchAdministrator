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

		View::load("acp/match_manage.twig", [
			"match" => $match
		]);
	}

	public static function delete() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminMatches"]);

		$match = current(MatchModel::get($_GET["id"]));

		if ($match->status == MatchModel::STATUS_RECONCILED) {
			Controller::addAlert(new Alert("danger", "You cannot delete a match that has already " .
					"been reconciled and added to the league tables."));
			Controller::redirect("/acp/match");
		}
		else {
			$match->delete();

			Controller::addAlert(new Alert("success",
					"Match, match reports and participating player records deleted"));
			Controller::redirect("/acp/match");
		}
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
}