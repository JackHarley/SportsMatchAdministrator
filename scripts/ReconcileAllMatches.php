<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
require_once(__DIR__ . "/Init.php");

$matches = \sma\models\Match::get(null, null, null, null, null, \sma\models\Match::STATUS_PENDING);

foreach($matches as $match) {
	echo "Attempting reconciliation of match #" . $match->id . "...";
	$status = $match->attemptReportReconciliation();
	if ($status)
		echo "good.\n";
	else
		echo "mismatch!\n";
}