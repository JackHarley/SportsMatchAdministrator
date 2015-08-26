<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\models;

use sma\Database;
use sma\query\InsertQuery;
use sma\query\SelectQuery;

/**
 * Setting
 *
 * @package \sma\models
 */
class Setting {

	/**
	 * @var string setting id
	 */
	public $id;

	/**
	 * @var string setting value
	 */
	public $value;

	/**
	 * Set setting
	 *
	 * @param string $id
	 * @param string $value
	 */
	public static function set($id, $value) {
		(new InsertQuery(Database::getConnection()))
				->into("settings")
				->fields(["id", "value"])
				->values("(?,?)", [$id, $value])
				->extraClause("ON DUPLICATE KEY UPDATE value=?", $value)
				->prepare()
				->execute();
	}

	/**
	 * Get setting
	 *
	 * @param string $id
	 * @return string value
	 */
	public static function get($id) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("settings")
				->where("id = ?", $id)
				->limit(1)
				->fields("value");
		$stmt = $q->prepare();
		$stmt->execute();

		return $stmt->fetchColumn();
	}
}