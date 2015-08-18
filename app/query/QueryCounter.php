<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\query;

/**
 * Query counter
 *
 * @package \sma\query
 */
class QueryCounter {

	/**
	 * @var int queries executed
	 */
	protected static $count = 0;

	/**
	 * Increment counter
	 */
	public static function increment() {
		self::$count++;
	}

	/**
	 * Get number of queries executed
	 *
	 * @return int queries executed
	 */
	public static function get() {
		return static::$count;
	}
}