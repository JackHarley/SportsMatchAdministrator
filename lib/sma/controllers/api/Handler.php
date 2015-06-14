<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers\api;

use sma\models\APISecretKey;

class Handler {

	public static function index() {
		if (
			(!array_key_exists("resource", $_POST)) ||
			(!array_key_exists("action", $_POST))
		)
			self::output(self::BAD_REQUEST);

		$call = "\\sma\controllers\api\\resources\\". $_POST["resource"]
				. "::" . $_POST["action"];

		if (!is_callable($call))
			self::output(self::NOT_FOUND);

		call_user_func($call);
	}

	/**
	 * Reply to an API-call
	 *
	 * @param int $statusCode status code
	 * @param mixed $data data
	 * @param bool $endExecution end execution after replying
	 */
	public static function output($statusCode, $data=null, $endExecution=true) {
		header("Content-type: application/json");
		http_response_code($statusCode);

		echo json_encode([
			"status" => $statusCode,
			"data" => $data,
			"requestTime" => round((microtime(true) - START_REQUEST), 2)
		]);

		if ($endExecution)
			die();
	}

	/**
	 * Check for required fields and send error code if necessary
	 *
	 * @param string[] $fields fieldnames
	 */
	public static function requireFields($fields) {
		foreach($fields as $name) {
			if ((!array_key_exists($name, $_POST)) || ($_POST[$name] === "")) {
				self::output(self::BAD_REQUEST);
			}
		}
	}

	// The request was successful
	const OK = 200;

	// If you fail to provide the required POST fields 'resource' and 'method'
	// or any of the POST fields required by the resource-action you specified
	const BAD_REQUEST = 400;

	// If authentication is required to perform the specified request and you
	// have not provided valid authentication (secret/session key may be missing)
	const UNAUTHORIZED = 401;

	// If authentication is required to perform the specified request and you
	// have provided valid credentials, but you still do not have permission
	const FORBIDDEN = 403;

	// If the requested resource or method do not exist
	const NOT_FOUND = 404;

	// If a server error occurred during fulfilling your request
	const INTERNAL_SERVER_ERROR = 500;
}