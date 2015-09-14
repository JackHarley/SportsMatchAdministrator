<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers;

use sma\models\Fixture;
use sma\models\LeagueSection;
use sma\View;
use sma\models\League as LeagueModel;

class League {

	public static function index() {
		$league = current(LeagueModel::get($_GET["id"]));

		View::load("league.twig", [
			"league" => $league,
			"fixtures" => $league->constructFixtures()
		]);
	}
}