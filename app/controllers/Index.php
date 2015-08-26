<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers;

use sma\models\Setting;
use sma\View;

class Index {

	public static function index() {
		View::load("index.twig", [
				"info" => Setting::get("info_box_content")
		]);
	}
}