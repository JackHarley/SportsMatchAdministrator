<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers;

use sma\models\Download as DownloadModel;

class Download {

	public static function trigger() {
		$dl = current(DownloadModel::get($_GET["id"]));
		$dl->serveDownloadViaReadfile();
	}
}