<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma;

class ErrorHandler {

	/**
	 * This is a static class, it may not be instantiated
	 */
	private function __construct() { }

	/**
	 * Show 404 Error page
	 */
	public static function notFound() {
		header("HTTP/1.0 404 Not Found");
		View::load("errors/404.twig");
		die();
	}

	/**
	 * Show 403 Forbidden page
	 */
	public static function forbidden() {
		header("HTTP/1.0 403 Forbidden");
		View::load("errors/403.twig");
		die();
	}
}