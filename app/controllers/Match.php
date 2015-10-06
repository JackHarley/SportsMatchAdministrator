<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers;

use DateTime;
use sma\Database;
use sma\exceptions\DuplicateException;
use sma\models\League;
use sma\models\MatchReport;
use sma\models\Player;
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
			View::load("match/submit.twig", [
					"leagues" => League::get(),
					"players" => Player::get()
			]);
		}
		else {
			// basic input validation
			Controller::requireFields("post", ["date", "league", "reporter-team", "reporter-score",
					"opposing-team", "opposing-score"], "/match/submit");
			$datetime = DateTime::createFromFormat("Y-m-d", $_POST["date"]);
			if (($datetime === false) || (array_sum($datetime->getLastErrors()))) {
				Controller::addAlert(new Alert("danger", "You did not enter a valid date, please try again."));
				Controller::redirect("/match/submit");
			}

			// check authorization of user to file reports on behalf of reporting team
			$reporterTeam = current(Team::get($_POST["reporter-team"]));
			$visitor = User::getVisitor();
			if ($visitor->organizationId != $reporterTeam->organizationId)
				Controller::requirePermissions(["SubmitMatchReportsForAnyTeam"]);

			// start determining the data for insertion
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


			// transaction
			Database::getConnection()->beginTransaction();

			// attempt to pull an existing match record or add a new one
			$match = current(MatchModel::get(null, $_POST["date"], $_POST["league"], $homeTeamId, $awayTeamId));
			if ($match)
				$matchId = $match->id;
			else
				$matchId = MatchModel::add($_POST["date"], $_POST["league"], $homeTeamId, $awayTeamId);

			try {
				MatchReport::add($matchId, $_POST["reporter-team"], $visitor->id, $homeScore,
						$awayScore);
			}
			catch (DuplicateException $e) {
				Database::getConnection()->rollBack();
				Controller::addAlert(new Alert("danger", "You have already submitted a report for that match!"));
				Controller::redirect("/match/submit");
			}

			if (!$match)
				$match = current(MatchModel::get($matchId));
			$players = $reporterTeam->getPlayers();
			foreach($players as $player) {
				if (array_key_exists("player" . $player->id, $_POST))
					$match->addParticipatingPlayer($reporterTeam->id, $player->id);
			}

			for($i=1;$i<=8;$i++) {
				if ((array_key_exists("additional-player" . $i, $_POST)) && ($_POST["additional-player" . $i]))
					$match->addParticipatingPlayer($reporterTeam->id, null, $_POST["additional-player" . $i]);
			}

			// commit
			Database::getConnection()->commit();

			// attempt reconciliation
			$matches = MatchModel::get($matchId);
			current($matches)->attemptReportReconciliation();

			Controller::addAlert(new Alert("success", "Match report submitted successfully!"));
			Controller::redirect("");
		}
	}

	public static function submitted() {
		Controller::requirePermissions(["SubmitMatchReports"]);

		$visitor = User::getVisitor();
		$teams = Team::get(null, $visitor->organizationId);
		$teamIds = [];
		foreach($teams as $team)
			$teamIds[] = $team->id;
		$reports = MatchReport::get(null, null, null, $teamIds, 25);

		View::load("match/submitted.twig", [
			"organizationReports" => $reports,
			"userReports" => MatchReport::get(null, null, $visitor->id, null, 25)
		]);
	}

	public static function record() {
		$match = current(MatchModel::get($_GET["id"]));

		View::load("match/record.twig", [
			"match" => $match
		]);
	}
}