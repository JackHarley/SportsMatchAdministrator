<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\acp;

use sma\Controller;
use sma\models\Match as MatchModel;
use sma\models\MatchReport;
use sma\models\User;
use sma\View;

class Match {

	public static function index() {
		Controller::requirePermissions(["AdminAccessDashboard"]);
		View::load("acp/match.twig", [
			"objects" => MatchReport::get()
		]);
	}

	public static function manage() {
		Controller::requirePermissions(["AdminAccessDashboard"]);
		$match = current(MatchModel::get($_GET["id"]));

		View::load("acp/match_manage.twig", [
			"match" => $match
		]);
	}
}