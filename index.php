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

if (!defined("ORGANIZATION_WORD"))
	define("ORGANIZATION_WORD", "organization");

require_once(__DIR__ . "/app/Autoloader.php");
require_once(__DIR__ . "/vendor/autoload.php");

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
		"/download/trigger" => ["Download", "trigger"],
		"/league" => ["League", "index"],
		"/user/login" => ["User", "login"],
		"/user/logout" => ["User", "logout"],
		"/user" => ["User", "index"],
		"/team/register" => ["Team", "register"],
		"/team/edit" => ["Team", "edit"],
		"/team/addplayer" => ["Team", "addplayer"],
		"/team/updateplayer" => ["Team", "updateplayer"],
		"/match/submitted" => ["Match", "submitted"],
		"/match/submit" => ["Match", "submit"],
		"/match/record" => ["Match", "record"],
		"/ajax/teams" => ["Ajax", "teams"],
		"/ajax/players" => ["Ajax", "players"],
		"/acp/match/manage" => ["acp\Match", "manage"],
		"/acp/match/delete" => ["acp\Match", "delete"],
		"/acp/match/correct" => ["acp\Match", "correct"],
		"/acp/match/alter" => ["acp\Match", "alter"],
		"/acp/match" => ["acp\Match", "index"],
		"/acp/organization/add" => ["acp\Organization", "add"],
		"/acp/organization/edit" => ["acp\Organization", "edit"],
		"/acp/organization/delete" => ["acp\Organization", "delete"],
		"/acp/organization" => ["acp\Organization", "index"],
		"/acp/user/add" => ["acp\User", "add"],
		"/acp/user/edit" => ["acp\User", "edit"],
		"/acp/user/delete" => ["acp\User", "delete"],
		"/acp/user" => ["acp\User", "index"],
		"/acp/group/add" => ["acp\UserGroup", "add"],
		"/acp/group/manage" => ["acp\UserGroup", "manage"],
		"/acp/group/delete" => ["acp\UserGroup", "delete"],
		"/acp/group" => ["acp\UserGroup", "index"],
		"/acp/league/add" => ["acp\League", "add"],
		"/acp/league/manage" => ["acp\League", "manage"],
		"/acp/league/delete" => ["acp\League", "delete"],
		"/acp/league" => ["acp\League", "index"],
		"/acp/section/add" => ["acp\LeagueSection", "add"],
		"/acp/section/delete" => ["acp\LeagueSection", "delete"],
		"/acp/team/assign" => ["acp\Team", "assign"],
		"/acp/team/manage" => ["acp\Team", "manage"],
		"/acp/team/delete" => ["acp\Team", "delete"],
		"/acp/team" => ["acp\Team", "index"],
		"/acp/player/add" => ["acp\Player", "add"],
		"/acp/player/delete" => ["acp\Player", "delete"],
		"/acp/player/update" => ["acp\Player", "update"],
		"/acp/player/exempt" => ["acp\Player", "exempt"],
		"/acp/fixture/add" => ["acp\Fixture", "add"],
		"/acp/fixture/delete" => ["acp\Fixture", "delete"],
		"/acp" => ["acp\Dashboard", "index"],
		"" => ["Index", "index"]
]);

Router::route();