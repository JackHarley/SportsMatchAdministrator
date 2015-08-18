<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
require_once(__DIR__ . "/config.php");

$allowedIps = explode(",", UPDATE_IP_ADDRESSES);

if (!in_array($_SERVER["REMOTE_ADDR"], $allowedIps)) {
	header("HTTP/1.1 403 Forbidden");
	echo "Your IP address is not authorised to perform an update of the software. If you " .
			"are the administrator please configure the setting UPDATE_IP_ADDRESSES in config.php";
	die();
}

shell_exec("cd " . __DIR__ . " && git reset --hard HEAD && git pull origin master && rm -rf cache/twig && php composer.phar update");