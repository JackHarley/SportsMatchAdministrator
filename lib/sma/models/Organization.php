<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\models;

use PDO;
use sma\Database;
use sma\query\InsertQuery;
use sma\query\SelectQuery;

/**
 * Organization
 *
 * @package \sma\models
 */
class Organization {

	/**
	 * @var int user id
	 */
	public $id;

	/**
	 * @var string name
	 */
	public $name;

	/**
	 * Get objects
	 *
	 * @param int $id id
	 * @return \sma\models\Organization[] organizations
	 */
	public static function get($id=null) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("organizations")
				->fields("name");

		if ($id)
			$q->where("id = ?", $id);

		$stmt = $q->prepare();
		$stmt->execute();

		$orgs = [];
		while($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$org = new self;
			list($org->name) = $row;
			$orgs[] = $org;
		}

		return $orgs;
	}

	/**
	 * Add object
	 *
	 * @param string $name
	 * @return int object id
	 */
	public static function add($name) {
		(new InsertQuery(Database::getConnection()))
				->into("organizations")
				->fields("name")
				->values("(?)", $name)
				->prepare()
				->execute();

		return Database::getConnection()->lastInsertId();
	}
}