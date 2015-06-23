<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers;

use sma\models\Alert;
use sma\Controller;
use sma\View;
use sma\models\Team as TeamModel;
use sma\models\Organization;
use Exception;

class Team {

	public static function register() {
		Controller::requirePermissions(["RegisterTeamsForOwnOrganization",
				"RegisterTeamsForAnyOrganization"], "any");

		if (empty($_POST)) {
			View::load("team/register_form.twig", [
				"organizations" => Organization::get()
			]);
		}
	}
}