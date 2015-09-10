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
use sma\exceptions\DuplicateException;
use sma\query\DeleteQuery;
use sma\query\InsertQuery;
use sma\query\SelectQuery;
use sma\query\UpdateQuery;

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
	 * Delete current object
	 */
	public function delete() {
		(new DeleteQuery(Database::getConnection()))
				->from("organizations")
				->where("id = ?", $this->id)
				->limit(1)
				->prepare()
				->execute();
	}

	/**
	 * Get objects
	 *
	 * @param int $id id
	 * @param string $name name
	 * @return \sma\models\Organization[] organizations
	 */
	public static function get($id=null, $name=null) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("organizations")
				->fields(["id", "name"])
				->orderby("name");

		if ($id)
			$q->where("id = ?", $id);
		if ($name)
			$q->where("LOWER(name) = LOWER(?)", $name);

		$stmt = $q->prepare();
		$stmt->execute();

		$orgs = [];
		while($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$org = new self;
			list($org->id, $org->name) = $row;
			$orgs[] = $org;
		}

		return $orgs;
	}

	/**
	 * Add object
	 *
	 * @param string $name
	 * @return int object id
	 * @throws \sma\exceptions\DuplicateException if name already exists
	 */
	public static function add($name) {
		if (count(self::get(null, $name)) > 0)
			throw new DuplicateException();

		(new InsertQuery(Database::getConnection()))
				->into("organizations")
				->fields("name")
				->values("(?)", $name)
				->prepare()
				->execute();

		return Database::getConnection()->lastInsertId();
	}

	/**
	 * Update object
	 *
	 * @param int $id org id
	 * @param string $name
	 * @return int object id
	 * @throws \sma\exceptions\DuplicateException if name already exists
	 */
	public static function update($id, $name=null) {
		$objs = self::get(null, $name);
		if (count($objs) > 0) {
			if (current($objs)->id != $id)
				throw new DuplicateException();
		}

		$q = (new UpdateQuery(Database::getConnection()))
				->table("organizations")
				->where("id = ?", $id)
				->limit(1);

		if ($name)
			$q->set("name = ?", $name);

		$q->prepare()->execute();
	}
}