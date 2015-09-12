<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers;

use sma\models\Player;
use sma\models\Team;

class Ajax {

	public static function teams() {
		$leagueId = array_key_exists("league", $_GET) ? $_GET["league"] : null;
		$organizationId = array_key_exists("organization", $_GET) ? $_GET["organization"] : null;

		$teams = Team::get(null, $organizationId, null, null, $leagueId);

		$return = [];
		foreach($teams as $team) {
			$data = new \stdClass();
			$data->id = $team->id;
			$data->string = $team->organization->name . " " . $team->designation;
			$return[] = $data;
		}

		echo json_encode($return);
	}

	public static function players() {
		$teamId = array_key_exists("team", $_GET) ? $_GET["team"] : null;

		$players = Player::get(null, null, $teamId);

		$return = [];
		foreach($players as $player) {
			$data = new \stdClass();
			$data->id = $player->id;
			$data->name = $player->fullName;
			$return[] = $data;
		}

		echo json_encode($return);
	}
}