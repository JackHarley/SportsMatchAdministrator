<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma;

use PDO;

/**
 * Database
 *
 * @package sma
 */
class Database {

	/**
	 * @var PDO[] connections
	 */
	private static $connections = [];

	/**
	 * This is a static class, it may not be instantiated
	 */
	private function __construct() { }

	/**
	 * Get a database connection. Create if it doesn't exist yet.
	 *
	 * @param string $host hostname
	 * @param int $port port number
	 * @param string $username username
	 * @param string $password password
	 * @param string $name database name
	 * @param bool $throwExceptions throw exceptions in case of errors
	 * @return PDO connection
	 */
	public static function getConnection($host=DB_HOST, $port=DB_PORT, $username=DB_USERNAME,
			$password=DB_PASSWORD, $name=DB_NAME, $throwExceptions=true) {
		$key = $host . $port . $username . $password . $name;

		if (!array_key_exists($key, static::$connections)) {
			$dsn = "mysql:host=" . $host . ";dbname=" . $name . ";port=" . $port;
			$pdo = new PDO($dsn, $username, $password);
			if ($throwExceptions)
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			static::$connections[$key] = $pdo;
		}

		return static::$connections[$key];
	}
}