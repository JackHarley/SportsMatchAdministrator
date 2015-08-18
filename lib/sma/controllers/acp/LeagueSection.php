<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\acp;

use sma\Controller;
use sma\exceptions\ObjectCannotBeDeletedException;
use sma\models\Alert;
use sma\models\LeagueSection as LeagueSectionModel;
use sma\models\League;
use sma\models\User;

class LeagueSection {

	public static function add() {
		Controller::requirePermissions(["AdminAccessDashboard"]);

		if (empty($_POST))
			Controller::redirect("/acp/league");

		$league = current(League::get($_POST["league_id"]));

		// check permissions
		$visitor = User::getVisitor();
		if ($visitor->id != $league->managerId)
			Controller::requirePermissions(["AdminAllLeagues"]);

		LeagueSectionModel::add($_POST["league_id"]);

		Controller::addAlert(new Alert("success", "League section added successfully"));
		Controller::redirect("/acp/league/manage?id=" . $_POST["league_id"]);
	}

	public static function delete() {
		Controller::requirePermissions(["AdminAccessDashboard"]);

		if (!array_key_exists("id", $_GET))
			Controller::redirect("/acp/league");

		$section = current(LeagueSectionModel::get($_GET["id"]));

		// check permissions
		$visitor = User::getVisitor();
		if ($visitor->id != $section->getLeague()->managerId)
			Controller::requirePermissions(["AdminAllLeagues"]);

		try {
			$section->delete();
			Controller::addAlert(new Alert("success", "League section deleted successfully"));
		}
		catch (ObjectCannotBeDeletedException $e) {
			Controller::addAlert(new Alert("danger", "You cannot delete a section which has teams assigned to it. Please reassign the teams to an alternative section first"));
		}

		Controller::redirect("/acp/league/manage?id=" . $section->getLeague()->id);
	}
}