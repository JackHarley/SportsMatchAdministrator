<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma;

/**
 * Class Autoloader
 *
 * @package sma
 */
class Autoloader {

	/**
	 * This is a static class, it may not be instantiated
	 */
	private function __construct() { }

	/**
	 * Setup autoloader
	 */
	public static function setup() {
		spl_autoload_register(function ($namespace) {
			while ($namespace[0] === '\\')
				$namespace = substr($namespace, 1);

			if (strpos($namespace, __NAMESPACE__) === 0) {
				$path = __DIR__ . '/' . str_replace('\\', '/', substr($namespace, strlen(__NAMESPACE__) + 1)) . '.php';
				if (!is_file($path))
					return false;
				return include_once($path);
			}

			return false;
		});
	}
}