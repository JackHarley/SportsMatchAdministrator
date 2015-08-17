<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
session_start();
define("START_REQUEST", microtime(true));

require_once(__DIR__ . "/config.php");
require_once(__DIR__ . "/lib/sma/Autoloader.php");
require_once(__DIR__ . "/lib/Twig/Autoloader.php");
require_once(__DIR__ . "/lib/password_compat/password.php");

use sma\Autoloader;
use sma\Router;
use sma\Controller;
use sma\Installer;

date_default_timezone_set(TIMEZONE);

ini_set("display_errors", (DISPLAY_ERRORS) ? "On" : "Off");
error_reporting(E_ALL);

if (!array_key_exists("PATH_INFO", $_SERVER))
	$_SERVER["PATH_INFO"] = "";

Autoloader::setup();
Twig_Autoloader::register();
Controller::loadAlertsFromCookie();

$dbStatus = Installer::getDatabaseStatus();
if (strpos($_SERVER["PATH_INFO"], "install") === false) {
	if ($dbStatus == Installer::DATABASE_STATUS_NOT_INSTALLED)
		Controller::redirect("/install/install");
	else if ($dbStatus == Installer::DATABASE_STATUS_NOT_UP_TO_DATE)
		Controller::redirect("/install/upgrade");
}

Router::setRoutes([
		"/install/install" => ["Installer", "install"],
		"/install/upgrade" => ["Installer", "upgrade"],
		"/install" => ["Installer", "index"],
		"/user/login" => ["User", "login"],
		"/user/logout" => ["User", "logout"],
		"/user" => ["User", "index"],
		"/team/register" => ["Team", "register"],
		"/acp/organization/add" => ["acp\Organization", "add"],
		"/acp/organization/delete" => ["acp\Organization", "delete"],
		"/acp/organization" => ["acp\Organization", "index"],
		"/acp/user/add" => ["acp\User", "add"],
		"/acp/user/edit" => ["acp\User", "edit"],
		"/acp/user/delete" => ["acp\User", "delete"],
		"/acp/user" => ["acp\User", "index"],
		"/acp/league/add" => ["acp\League", "add"],
		"/acp/league/delete" => ["acp\League", "delete"],
		"/acp/league" => ["acp\League", "index"],
		"/acp" => ["acp\Dashboard", "index"],
		"" => ["Index", "index"]
]);

Router::route();