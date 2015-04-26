<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers;

use sma\Controller;
use sma\models\User;
use sma\models\UserGroup;
use sma\View;
use sma\Installer as InstallerModel;

class Installer {

	public static function index() {
		Controller::redirect("/install/install");
	}

	public static function install() {
		if (InstallerModel::databaseLocked()) {
			View::load("install/database_locked.twig");
		}
		else if (empty($_POST)) {
			View::load("install/install.twig", [
				"checks" => InstallerModel::checkRequirements()
			]);
		}
		else {
			InstallerModel::installDatabase(true);
			$adminGroupId = current(UserGroup::get(null, "Root Admin"))->id;
			User::add($_POST["email"], $_POST["full-name"], $_POST["phone-number"],
					$_POST["password"], $adminGroupId);
			View::load("install/complete.twig");
		}
	}

	public static function upgrade() {
		if (InstallerModel::getDatabaseStatus() !== InstallerModel::DATABASE_STATUS_NOT_UP_TO_DATE) {
			View::load('install/already_up_to_date.twig');
		}
		else if (empty($_POST)) {
			View::load('install/upgrade.twig');
		}
		else {
			InstallerModel::installDatabase(false);
			View::load('install/complete.twig');
		}
	}
}