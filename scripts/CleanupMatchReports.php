<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
require_once(__DIR__ . "/Init.php");

use sma\Database;
use sma\query\SelectQuery;
use sma\models\MatchReport;

echo "Beginning cleanup...";

$mrs = MatchReport::get();

foreach($mrs as $report) {
	if (!$report->getMatch()->id) {
		$report->delete();
		echo "Report #" . $report->id . " deleted as it did not belong to a valid match";
	}

	$match = $report->getMatch();
	if ($report->teamId == $match->homeTeamId) {
		if (!$match->getHomeTeamPlayers()) {
			$report->delete();
			echo "Report #" . $report->id . " deleted as it did not have any attached players";
		}
	}
	else if ($report->teamId == $match->awayTeamId) {
		if (!$match->getAwayTeamPlayers()) {
			$report->delete();
			echo "Report #" . $report->id . " deleted as it did not have any attached players";
		}
	}

}