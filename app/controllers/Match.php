<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers;

use sma\models\League;
use sma\models\MatchReport;
use sma\models\Team;
use sma\models\User;
use sma\models\Alert;
use sma\Controller;
use sma\View;
use sma\models\Match as MatchModel;

class Match {

	public static function submit() {
		Controller::requirePermissions(["SubmitMatchReports"]);

		if (empty($_POST)) {
			View::load("match_report.twig", [
					"leagues" => League::get()
			]);
		}
		else {
			// check authorization of user to file reports on behalf of reporting team
			$reporterTeam = current(Team::get($_POST["reporter-team"]));
			$visitor = User::getVisitor();
			if ($visitor->organizationId != $reporterTeam->organizationId)
				Controller::requirePermissions(["SubmitMatchReportsForAnyTeam"]);

			if ($_POST["location"] == "home") { // reporting team is home
				$homeTeamId = $_POST["reporter-team"];
				$homeScore = $_POST["reporter-score"];
				$awayTeamId = $_POST["opposing-team"];
				$awayScore = $_POST["opposing-score"];
			}
			else {
				$awayTeamId = $_POST["reporter-team"];
				$awayScore = $_POST["reporter-score"];
				$homeTeamId = $_POST["opposing-team"];
				$homeScore = $_POST["opposing-score"];
			}

			$matchId = MatchModel::add($_POST["date"], $_POST["league"], $homeTeamId, $awayTeamId);
			MatchReport::add($matchId, $_POST["reporter-team"], $visitor->id, $homeScore, $awayScore);

			$match = current(MatchModel::get($matchId));
			$players = $reporterTeam->getPlayers();
			foreach($players as $player) {
				if (array_key_exists("player" . $player->id, $_POST))
					$match->addParticipatingPlayer($reporterTeam->id, $player->id);
			}

			for($i=1;$i<=5;$i++) {
				if ((array_key_exists("additional-player" . $i, $_POST)) && ($_POST["additional-player" . $i]))
					$match->addParticipatingPlayer($reporterTeam->id, null, $_POST["additional-player" . $i]);
			}

			Controller::addAlert(new Alert("success", "Match report submitted successfully!"));
			Controller::redirect("");
		}
	}
}