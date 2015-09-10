<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers;

use sma\ErrorHandler;
use sma\exceptions\DuplicateException;
use sma\models\League;
use sma\models\Player;
use sma\models\User;
use sma\models\Alert;
use sma\Controller;
use sma\View;
use sma\models\Team as TeamModel;
use sma\models\Organization;

class Team {

	public static function register() {
		Controller::requirePermissions(["RegisterTeamsForOwnOrganization",
				"RegisterTeamsForAnyOrganization"], "any");

		if (empty($_POST)) {
			$teams = (User::getVisitor()->organizationId) ? TeamModel::get(null, User::getVisitor()->organizationId) : null;

			View::load("team/register_form.twig", [
					"organizations" => Organization::get(),
					"designations" => TeamModel::getValidDesignations(),
					"leagues" => League::get(),
					"teams" => $teams
			]);
		}
		else {
			// add the team
			if (User::getVisitor()->checkPermissions(["RegisterTeamsForAnyOrganization"]))
				$organizationId = $_POST["organization-id"];
			else
				$organizationId = User::getVisitor()->organizationId;

			try {
				if (ALLOW_TEAM_REGISTRANTS_TO_SELECT_LEAGUE)
					$teamId = TeamModel::add($organizationId, $_POST["designation"], User::getVisitor()->id, $_POST["league-id"]);
				else
					$teamId = TeamModel::add($organizationId, $_POST["designation"], User::getVisitor()->id);
			}
			catch (DuplicateException $e) {
				Controller::addAlert(new Alert("danger", "You cannot register more than one team with the same name. " .
						"To edit an existing team please use the edit button beside the team in the Registered Teams box."));
				Controller::redirect("/team/register");
			}

			// add the players
			$exemptsAdded = 0;
			for ($i = 1; array_key_exists("player" . $i, $_POST); $i++) {
				if ($_POST["player" . $i]) {
					if (isset($_POST["player" . $i . "e"])) {
						if ($exemptsAdded < MAX_EXEMPTS) {
							$makeExempt = true;
							$exemptsAdded++;
						}
						else {
							$makeExempt = false;
							Controller::addAlert(new Alert("warning", "You attempted to star " . $_POST["player" . $i] . " but you had already starred " .
									MAX_EXEMPTS . " other players, which is the maxmimum allowed, thus " . $_POST["player" . $i] . "was not starred"));
						}
					}
					else {
						$makeExempt = false;
					}

					try {
						Player::add($_POST["player" . $i], $teamId, $makeExempt);
					}
					catch (DuplicateException $e) {
						Controller::addAlert(new Alert("info", "You entered the name " .
								$_POST["player" . $i] . " more than once, only the first entry was" .
								"added to the database"));
					}
				}
			}

			Controller::addAlert(new Alert("success",
					"You have successfully registered your team and its details are shown below. You can come back to this area up until the freeze date and make changes."));
			Controller::redirect("/team/edit?id=" . $teamId);
		}
	}

	public static function edit() {
		$team = current(TeamModel::get($_GET["id"]));

		if (!User::getVisitor()->checkPermissions(["RegisterTeamsForAnyOrganization"])) {
			Controller::requirePermissions(["RegisterTeamsForOwnOrganization"]);
			if ($team->organizationId != User::getVisitor()->organizationId)
				ErrorHandler::forbidden();
		}

		if (!empty($_POST)) {
			TeamModel::update($_POST["id"], null, $_POST["designation"]);
			Controller::addAlert(new Alert("success", "Team details updated successfully"));
			$team = current(TeamModel::get($_GET["id"]));
		}

		View::load("team/edit.twig", [
				"team" => $team
		]);
	}

	public static function addplayer() {
		Controller::requireFields("post", ["name", "team"], "/acp/team");

		$team = current(TeamModel::get($_POST["team"]));

		if (!User::getVisitor()->checkPermissions(["RegisterTeamsForAnyOrganization"])) {
			Controller::requirePermissions(["RegisterTeamsForOwnOrganization"]);
			if ($team->organizationId != User::getVisitor()->organizationId)
				ErrorHandler::forbidden();
		}

		Player::add($_POST["name"], $team->id, false);

		Controller::addAlert(new Alert("success", "Player added successfully"));
		Controller::redirect("/team/edit?id=" . $team->id);
	}

	public static function updateplayer() {
		Controller::requireFields("get", ["id"], "/acp/team");

		$player = current(Player::get($_GET["id"]));

		if (!User::getVisitor()->checkPermissions(["RegisterTeamsForAnyOrganization"])) {
			Controller::requirePermissions(["RegisterTeamsForOwnOrganization"]);
			if ($player->getTeam()->organizationId != User::getVisitor()->organizationId)
				ErrorHandler::forbidden();
		}

		if (($_GET["exempt"] == 1) && (!$player->exempt)) {
			if ($player->getTeam()->getNumberOfExemptPlayers() >= MAX_EXEMPTS) {
				Controller::addAlert(new Alert("danger", "You have already starred the maximum number of players"));
				Controller::redirect("/team/edit?id=" . $player->getTeam()->id);
			}
		}

		Player::update($player->id, null, (bool) $_GET["exempt"]);

		Controller::addAlert(new Alert("success", "Player updated successfully"));
		Controller::redirect("/team/edit?id=" . $player->getTeam()->id);
	}
}