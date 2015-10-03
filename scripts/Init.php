<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
if (!defined('STDIN'))
	die("This is a script which is run from the CLI, it cannot be accessed via the web");

define("START_REQUEST", microtime(true));

require_once(__DIR__ . "/../config.php");

if (!defined("ORGANIZATION_WORD"))
	define("ORGANIZATION_WORD", "organization");

require_once(__DIR__ . "/../app/Autoloader.php");
require_once(__DIR__ . "/../vendor/autoload.php");

use sma\Autoloader;
use sma\Installer;

date_default_timezone_set(TIMEZONE);

ini_set("display_errors", (DISPLAY_ERRORS) ? "On" : "Off");
error_reporting(E_ALL);

Autoloader::setup();

$dbStatus = Installer::getDatabaseStatus();
if ($dbStatus !== Installer::DATABASE_STATUS_INSTALLED)
	die("You must install/upgrade your application before you can run scripts on it");