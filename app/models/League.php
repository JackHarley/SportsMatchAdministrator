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
use sma\query\DeleteQuery;
use sma\query\InsertQuery;
use sma\query\SelectQuery;
use sma\query\UpdateQuery;

/**
 * League
 *
 * @package \sma\models
 */
class League {

	/**
	 * @var int id
	 */
	public $id;

	/**
	 * @var string league name
	 */
	public $name;

	/**
	 * @var \sma\models\User manager
	 */
	protected $manager;

	/**
	 * @var int manager id
	 */
	public $managerId;

	/**
	 * @var \sma\models\LeagueSection[] league sections
	 */
	protected $sections;

	/**
	 * @var \sma\models\Team[] assigned teams
	 */
	protected $assignedTeams;

	/**
	 * Delete the league
	 */
	public function delete() {
		(new DeleteQuery(Database::getConnection()))
				->from("leagues")
				->where("id = ?", $this->id)
				->limit(1)
				->prepare()
				->execute();
	}

	/**
	 * Get manager
	 *
	 * @return \sma\models\User
	 */
	public function getManager() {
		if (!$this->manager)
			$this->manager = current(User::get($this->managerId));
		return $this->manager;
	}

	/**
	 * Get sections
	 *
	 * @return \sma\models\LeagueSection[] league sections
	 */
	public function getSections() {
		if (!$this->sections)
			$this->sections = LeagueSection::get(null, $this->id);
		return $this->sections;
	}

	/**
	 * Get assigned teams
	 *
	 * @return \sma\models\Team[]
	 */
	public function getAssignedTeams() {
		if (!$this->assignedTeams)
			$this->assignedTeams = Team::get(null, null, null, null, $this->id);
		return $this->assignedTeams;
	}

	/**
	 * Get objects
	 *
	 * @param int $id id
	 * @return \sma\models\League[] leagues
	 */
	public static function get($id=null) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("leagues l")
				->fields(["l.id", "l.name", "l.manager_id"])
				->orderby("l.name");

		if ($id)
			$q->where("l.id = ?", $id);

		$stmt = $q->prepare();
		$stmt->execute();

		$leagues = [];
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$league = new self;
			list($league->id, $league->name, $league->managerId) = $row;
			$leagues[] = $league;
		}

		return $leagues;
	}

	/**
	 * Add a new league
	 *
	 * @param string $name league name
	 * @param int $managerId manager id
	 * @return int new id
	 */
	public static function add($name, $managerId) {
		(new InsertQuery(Database::getConnection()))
				->into("leagues")
				->fields(["name", "manager_id"])
				->values("(?,?)", [$name, $managerId])
				->prepare()
				->execute();

		return Database::getConnection()->lastInsertId();
	}

	/**
	 * Update a league
	 *
	 * @param int $id league id
	 * @param string $name league name
	 * @param int $managerId manager id
	 * @return int new id
	 */
	public static function update($id, $name=null, $managerId=null) {
		$q = (new UpdateQuery(Database::getConnection()))
				->table("leagues")
				->where("id = ?", $id)
				->limit(1);

		if ($name)
			$q->set("name = ?", $name);
		if ($managerId)
			$q->set("manager_id = ?", $managerId);

		$q->prepare()->execute();
	}
}