<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma;

use sma\models\Alert;
use sma\models\User;

/**
 * Controller
 *
 * @package sma
 */
class Controller {

	/**
	 * @var \sma\models\Alert[] alerts generated during execution
	 */
	private static $alerts = [];

	/**
	 * This is a static class, it may not be instantiated
	 */
	private function __construct() { }

	/**
	 * Save generated alerts and redirect the client
	 *
	 * @param string $path new location
	 * @param bool $external redirect to external location
	 */
	public static function redirect($path, $external=false) {
		if (!$external) {
			static::saveAlertsToCookie();
			header('Location: ' . BASE_LINKS_URL . $path);
		}
		else {
			header('Location: ' . $path);
		}
		die();
	}

	/**
	 * Add an alert
	 *
	 * @param \sma\models\Alert $alert
	 */
	public static function addAlert(Alert $alert) {
		static::$alerts[] = $alert;
	}

	/**
	 * Get generated alerts
	 *
	 * @return \sma\models\Alert[]
	 */
	public static function getAlerts() {
		return static::$alerts;
	}

	/**
	 * Save the generated alerts to a cookie
	 */
	public static function saveAlertsToCookie() {
		if (!empty(static::$alerts))
			setcookie("alerts", serialize(static::$alerts), time()+60*60, "/");
	}

	/**
	 * Load generated alerts from cookie
	 */
	public static function loadAlertsFromCookie() {
		if (isset($_COOKIE["alerts"])) {
			static::$alerts = unserialize($_COOKIE["alerts"]);
			setcookie("alerts", "null", time()-60*60, "/");
		}
	}

	/**
	 * Redirect the user to the login page if not logged in
	 */
	public static function requireLoggedInUser() {
		if (!User::getVisitor()->id) {
			static::addAlert(new Alert("info", "Please login to continue"));
			static::redirect("/user/login?r=" .$_SERVER["PATH_INFO"] .
					(isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != "" ?
							urlencode("?" . $_SERVER['QUERY_STRING']) : ""));
		}
	}

	/**
	 * Check if user has proper permissions and throw exception if not
	 *
	 * @param \sma\models\Permission|\sma\models\Permission[] $permissions required permissions
	 * @param string $requirement 'all' to require all permissions listed, 'any' to require at least
	 * one of them
	 */
	public static function requirePermissions($permissions, $requirement="all") {
		static::requireLoggedInUser();
		if (!User::getVisitor()->checkPermissions($permissions, $requirement))
			ErrorHandler::forbidden();
	}

	/**
	 * Check if all required fields are entered and redirect otherwise
	 *
	 * @param string $type input type (post or get)
	 * @param string[] $fields required field names
	 * @param string $redirectPath path to redirect to in case of missing fields
	 * @param callback $handler function to run if fields are not available
	 */
	public static function requireFields($type, $fields, $redirectPath="", $handler=null) {
		switch($type) {
			case "post":
				$arrayToCheck = $_POST;
			break;
			case "get":
				$arrayToCheck = $_GET;
			break;
			default:
				$arrayToCheck = [];
		}

		foreach($fields as $name) {
			if ((!array_key_exists($name, $arrayToCheck)) || (trim($arrayToCheck[$name]) === "")) {
				if (!$handler) {
					static::addAlert(new Alert("danger",
							"You did not complete all of the required fields, please try again"));
					static::redirect($redirectPath);
				}
				else {
					$handler();
				}
			}
		}
	}
}