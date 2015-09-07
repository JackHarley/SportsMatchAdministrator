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
use sma\models\Fixture as FixtureModel;
use sma\models\User as UserModel;
use sma\models\League as LeagueModel;

class Fixture {

	public static function add() {
		Controller::requireFields("post", ["date", "type"], "/acp/league/manage?id=" . $_POST["league"]);
		Controller::requirePermissions(["AdminAccessDashboard"]);

		// check permissions
		$visitor = UserModel::getVisitor();
		if ($visitor->id != current(LeagueModel::get($_POST["league"]))->managerId)
			Controller::requirePermissions(["AdminAllLeagues"]);

		// check date
		$dt = \DateTime::createFromFormat("Y-m-d", $_POST["date"]);
		if (($dt === false) || (array_sum($dt->getLastErrors()))) {
			Controller::addAlert(new Alert("danger", "The provided date was invalid"));
			Controller::redirect("/acp/league/manage?id=" . $_POST["league"]);
		}

		FixtureModel::add($_POST["type"], $_POST["date"], $_POST["league"], $_POST["home-team-id"],
				$_POST["away-team-id"], $_POST["home-team-number"], $_POST["away-team-number"]);

		Controller::addAlert(new Alert("success", "Fixture added successfully"));
		Controller::redirect("/acp/league/manage?id=" . $_POST["league"]);
	}

	public static function delete() {
		Controller::requirePermissions(["AdminAccessDashboard"]);

		$fixture = current(FixtureModel::get($_GET["id"]));

		if (!$fixture) {
			Controller::addAlert(new Alert("success", "The specified fixture does not exist"));
			Controller::redirect("/acp/league");
		}

		$league = $fixture->getLeague();

		// check permissions
		$visitor = UserModel::getVisitor();
		if ($visitor->id != $league->managerId)
			Controller::requirePermissions(["AdminAllLeagues"]);

		$fixture->delete();

		Controller::addAlert(new Alert("success", "Fixture deleted successfully"));
		Controller::redirect("/acp/league/manage?id=" . $league->id);
	}
}