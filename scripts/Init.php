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
require_once(__DIR__ . "/../lib/sma/Autoloader.php");
require_once(__DIR__ . "/../lib/Twig/Autoloader.php");

use sma\Autoloader;
use sma\Installer;

ini_set("display_errors", "On");
error_reporting(E_ALL);

Autoloader::setup();

$dbStatus = Installer::getDatabaseStatus();
if ($dbStatus !== Installer::DATABASE_STATUS_INSTALLED)
	die("You must install/upgrade your application before you can run scripts on it");