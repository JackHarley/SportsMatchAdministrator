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
use sma\models\Match as MatchModel;
use sma\models\MatchReport;
use sma\View;

class Match {

	public static function index() {
		Controller::requirePermissions(["AdminAccessDashboard", "AdminMatches"]);
		View::load("acp/match.twig", [
			"objects" => MatchReport::get()
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