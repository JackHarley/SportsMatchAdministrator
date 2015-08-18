<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma;

/**
 * Router
 *
 * @package sma
 */
class Router {

	/**
	 * This is a static class, it may not be instantiated
	 */
	private function __construct() { }

	/**
	 * @var mixed[] routes
	 */
	private static $routes = [];

	/**
	 * Perform routing and call the appropriate controller
	 * Iterates over the routes set and when it finds a match, calls the indicated controller and
	 * method, supplies any additional URL arguments as parameters to the called controller method
	 */
	public static function route() {
		foreach(static::$routes as $path => $options) {
			if (
					(($path === "") && ($_SERVER["PATH_INFO"] == "")) ||
					(($path != "") && (strpos($_SERVER["PATH_INFO"], $path) === 0))
			) {
				list($controllerName, $methodName) = $options;
				$matchedPath = $path;
				break;
			}
		}

		if (!isset($controllerName))
			ErrorHandler::notFound();

		$remainder = str_replace($matchedPath, "", $_SERVER["PATH_INFO"]);
		if (($remainder != "/") && ($remainder != "")) {
			$arguments = explode("/", $remainder);
			foreach($arguments as $key => $argument) {
				if (!$argument)
					unset($arguments[$key]);
			}
		}
		else {
			$arguments = [];
		}

		$namespace = "\\sma\controllers\\" . $controllerName . "::" . $methodName;
		call_user_func_array($namespace, $arguments);
	}

	/**
	 * Sets the internal array of routes
	 *
	 * @param mixed[] $routes routes to overwrite the current ones with, note that the most specific
	 * routes should be supplied first, as the first match will be the one used
	 */
	public static function setRoutes($routes) {
		static::$routes = $routes;
	}
}