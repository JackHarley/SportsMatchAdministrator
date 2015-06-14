<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\admin;

use sma\Controller;
use sma\View;

class Dashboard {

	public static function index() {
		Controller::requirePermissions(["AdminAccessDashboard"]);

		View::load("admin/dashboard.twig");
	}
}