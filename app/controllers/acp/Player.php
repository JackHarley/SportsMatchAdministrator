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
use sma\models\Player as PlayerModel;

class Player {

	public static function add() {
		Controller::requireFields("post", ["name", "team"], "/acp/team");
		Controller::requirePermissions(["AdminAccessDashboard", "AdminTeams"]);

		PlayerModel::add($_POST["name"], $_POST["team"], false);

		Controller::addAlert(new Alert("success", "Player added successfully"));
		Controller::redirect("/acp/team/manage?id=" . $_POST["team"]);
	}

	public static function update() {
		Controller::requireFields("post", ["name", "id"], "/acp/team");
		Controller::requirePermissions(["AdminAccessDashboard", "AdminTeams"]);

		$player = current(PlayerModel::get($_POST["id"]));
		PlayerModel::update($_POST["id"], $_POST["name"]);

		Controller::addAlert(new Alert("success", "Player updated successfully"));
		Controller::redirect("/acp/team/manage?id=" . $player->teamId);
	}

	public static function delete() {
		Controller::requireFields("get", ["id"], "/acp/team");
		Controller::requirePermissions(["AdminAccessDashboard", "AdminTeams"]);

		$player = current(PlayerModel::get($_GET["id"]));
		$player->delete();

		Controller::addAlert(new Alert("success", "Player deleted successfully"));
		Controller::redirect("/acp/team/manage?id=" . $player->teamId);
	}
}