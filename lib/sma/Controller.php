<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma;

use sma\models\Alert;
use sma\models\forums\User;

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
	 * @var string[] saved form data
	 */
	private static $formFields = [];

	/**
	 * @var string[] invalid form fields
	 */
	private static $invalidFormFields = [];

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
	public static function redirect($path, $external = false) {
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
	 * Get saved form data
	 *
	 * @return string[] form data
	 */
	public static function getFormFields() {
		return static::$formFields;
	}

	/**
	 * Save form fields to a cookie
	 *
	 * @param mixed[] $inputArray Input array (POST, GET, COOKIE, ...)
	 * @param string[] $formFieldsToSave field names to save
	 */
	public static function saveFormFieldsToCookie($inputArray, $formFieldsToSave) {
		if (empty($inputArray) || empty($formFieldsToSave))
			return;
		$fields = array_intersect_key($inputArray, array_flip($formFieldsToSave));
		if (!empty($fields))
			setcookie("formFields", serialize($fields), time()+60*60, "/");
	}

	/**
	 * Load saved form data from a cookie
	 */
	public static function loadFormFieldsFromCookie() {
		if (isset($_COOKIE["formFields"])) {
			static::$formFields = unserialize($_COOKIE["formFields"]);
			setcookie("formFields", "null", time()-60*60, "/");
		}
	}

	/**
	 * Get invalid form fields
	 *
	 * @return string[] field names
	 */
	public static function getInvalidFormFields() {
		return static::$invalidFormFields;
	}

	/**
	 * Save invalid form fields to a cookie
	 *
	 * @param string[] $invalidFields field names
	 */
	public static function saveInvalidFormFieldsToCookie($invalidFields) {
		if (!empty($invalidFields))
			setcookie("invalidFormFields", serialize($invalidFields), time()+60*60, "/");
	}

	/**
	 * Load invalid form fields from a cookie
	 */
	public static function loadInvalidFormFieldsFromCookie() {
		if (isset($_COOKIE["invalidFormFields"])) {
			static::$invalidFormFields = unserialize($_COOKIE["invalidFormFields"]);
			setcookie("invalidFormFields", "null", time()-60*60, "/");
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
	 */
	public static function requirePermissions($permissions) {
		static::requireLoggedInUser();
		if (!User::getVisitor()->checkPermissions($permissions))
			ErrorHandler::forbidden();
	}

	/**
	 * Check if all required fields are entered and redirect otherwise
	 *
	 * @param string $type input type (post or get)
	 * @param string[] $fields required fieldnames
	 * @param string $redirectPath path to redirect to in case of missing fields
	 * @param string[] $saveFields names of fields to save in case of redirecting
	 */
	public static function requireFields($type, $fields, $redirectPath="", $saveFields=[]) {
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

		$missingFields = [];

		foreach($fields as $name) {
			if ((!array_key_exists($name, $arrayToCheck)) || (trim($arrayToCheck[$name]) === "")) {
				$missingFields[] = $name;
			}
		}

		if (sizeof($missingFields) > 0) {
			self::addAlert(new Alert("error", "You did not complete all of the required fields, please try again"));
			self::saveInvalidFormFieldsToCookie($missingFields);
			self::saveFormFieldsToCookie($arrayToCheck, $saveFields);
			self::redirect($redirectPath);
		}
	}
}