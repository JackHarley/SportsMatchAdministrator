<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma;

use sma\models\User;
use sma\query\QueryCounter;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Twig_SimpleFunction;
use Twig_SimpleTest;

/**
 * View
 *
 * @package sma
 */
class View {

	/**
	 * @var Twig_Environment twig environment
	 */
	private static $twig;

	/**
	 * This is a static class, it may not be instantiated
	 */
	private function __construct() { }

	/**
	 * Configure view environment
	 */
	public static function configureEnvironment() {
		$loader = new Twig_Loader_Filesystem(BASE_VIEW_PATH);

		$settings = [];
		$settings["cache"] = (ENABLE_VIEW_CACHING) ?
				__DIR__ . "/../../cache/twig" : false;
		$settings["auto_reload"] = (CHECK_FOR_VIEW_UPDATES) ? true : false;

		$twig = new Twig_Environment($loader, $settings);
		$twig = static::registerGlobalVariables($twig);
		$twig = static::registerTests($twig);
		$twig = static::registerFunctions($twig);
		$twig = static::registerFilters($twig);

		static::$twig = $twig;
	}

	/**
	 * Load view
	 * @param string $viewPath path to view
	 * @param mixed[] $variables viewvariables
	 */
	public static function load($viewPath, $variables=null) {
		if (!static::$twig)
			static::configureEnvironment();

		if ($variables)
			echo static::$twig->render($viewPath, $variables);
		else
			echo static::$twig->render($viewPath);

		die();
	}

	/**
	 * Reply JSON
	 *
	 * @param mixed $data JSON-contents
	 */
	public static function json($data=null) {
		$response = new \stdClass();
		$response->requestTime = round(microtime(true) - START_REQUEST, 2);
		$response->data = $data;

		header("Content-type: application/json");
		echo json_encode($response);
		die();
	}

	/**
	 * Register global variables
	 *
	 * @param Twig_Environment $twig twig environment
	 * @return Twig_Environment twig environment
	 */
	private static function registerGlobalVariables($twig) {

		$twig->addGlobal("base_url", BASE_URL);
		$twig->addGlobal("base_links_url", BASE_LINKS_URL);
		$twig->addGlobal("base_view_url", BASE_VIEW_URL);
		$twig->addGlobal("base_assets_url", BASE_ASSETS_URL);
		$twig->addGlobal("site_name", SITE_NAME);
		$twig->addGlobal("show_request_times", SHOW_REQUEST_TIMES);

		$alerts = Controller::getAlerts();
		if (!empty($alerts))
			$twig->addGlobal("alerts", $alerts);

		if (Installer::getDatabaseStatus() == Installer::DATABASE_STATUS_INSTALLED) {
			$twig->addGlobal("visitor", User::getVisitor());
		}

		return $twig;
	}

	/**
	 * Register tests
	 *
	 * @param Twig_Environment $twig twig environment
	 * @return Twig_Environment twig environment
	 */
	private static function registerTests($twig) {

		$test = new Twig_SimpleTest('currentpath', function ($path) {
			return (strpos($_SERVER["PATH_INFO"], $path) === 0);
		});
		$twig->addTest($test);

		$test = new Twig_SimpleTest('exactcurrentpath', function ($path) {
			return ($_SERVER["PATH_INFO"] == $path);
		});
		$twig->addTest($test);

		return $twig;
	}

	/**
	 * Register functions
	 *
	 * @param Twig_Environment $twig twig environment
	 * @return Twig_Environment twig environment
	 */
	private static function registerFunctions($twig) {
		$function = new Twig_SimpleFunction('request_time', function () {
			return round(microtime(true) - START_REQUEST, 2);
		});
		$twig->addFunction($function);

		$function = new Twig_SimpleFunction('database_queries', function () {
			return QueryCounter::get();
		});
		$twig->addFunction($function);

		return $twig;
	}

	/**
	 * Register filters
	 * 
	 * @param Twig_Environment $twig twig environment
	 * @return Twig_Environment twig environment
	 */
	private static function registerFilters($twig) {
		$filter = new \Twig_SimpleFilter('epochtodate', function($epoch) {
			return date("d/m/y", $epoch);
		});
		$twig->addFilter($filter);

		$filter = new \Twig_SimpleFilter('epochtodatetime', function($epoch) {
			return date("d/m/y H:i", $epoch);
		});
		$twig->addFilter($filter);

		return $twig;
	}
}