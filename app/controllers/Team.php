<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers;

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
				Controller::addAlert(new Alert("danger", "You cannot register more than one team with the same name. To edit an existing team please use the edit button beside the team in the Registered Teams box."));
				Controller::redirect("/team/register");
			}

			// add the players
			for ($i = 1; array_key_exists("player" . $i, $_POST); $i++) {
				if ($_POST["player" . $i]) {
					try {
						Player::add($_POST["player" . $i], $teamId, false);
					}
					catch (DuplicateException $e) {
						Controller::addAlert(new Alert("info", "You entered the name " .
								$_POST["player" . $i] . " more than once, only the first entry was" .
								"added to the database"));
					}
				}
			}

			View::load("team/register_success.twig", [
					"team" => TeamModel::get($teamId)[0]
			]);
		}
	}
}