<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
require_once(__DIR__ . "/Init.php");

echo "Clearing all reconciled results and team points...";

(new \sma\query\UpdateQuery(\sma\Database::getConnection()))
	->table("teams")
	->set("wins = 0")
	->set("draws = 0")
	->set("losses = 0")
	->set("points = 0")
	->set("score_for = 0")
	->set("score_against = 0")
	->prepare()
	->execute();

(new \sma\query\UpdateQuery(\sma\Database::getConnection()))
	->table("matches")
	->set("home_score = NULL")
	->set("away_score = NULL")
	->prepare()
	->execute();

echo "done!\n\n";

echo "Now beginning to reconcile all matches\n";

require_once(__DIR__ . "/ReconcileAllMatches.php");