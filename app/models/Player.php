<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\models;

use PDO;
use sma\models\Team as TeamModel;
use sma\Database;
use sma\exceptions\DuplicateException;
use sma\query\DeleteQuery;
use sma\query\InsertQuery;
use sma\query\SelectQuery;
use sma\query\UpdateQuery;

/**
 * Player
 *
 * @package \sma\models
 */
class Player {

	/**
	 * @var int id
	 */
	public $id;

	/**
	 * @var string player's first and last name
	 */
	public $fullName;

	/**
	 * @var \sma\models\Team team
	 */
	public $team;

	/**
	 * @var int team id
	 */
	public $teamId;

	/**
	 * @var bool exempt status, if true, the player can play in any league without generating alerts
	 */
	public $exempt;

	/**
	 * Get team
	 *
	 * @return \sma\models\Team
	 */
	public function getTeam() {
		if (!$this->team)
			$this->team = TeamModel::get($this->teamId);
		return $this->team;
	}

	/**
	 * Delete the player
	 */
	public function delete() {
		(new DeleteQuery(Database::getConnection()))
				->from("players")
				->where("id = ?", $this->id)
				->limit(1)
				->prepare()
				->execute();
	}

	/**
	 * Get objects
	 *
	 * @param int $id id
	 * @param int $fullName search by full name
	 * @param int $teamId team id to fetch players for
	 * @return \sma\models\Team[] teams
	 */
	public static function get($id=null, $fullName=null, $teamId=null) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("players p")
				->fields(["p.id", "p.full_name", "p.team_id", "p.exempt"])
				->join("LEFT JOIN teams t ON t.id=p.team_id")
				->fields(["t.id AS te_id", "t.designation", "t.organization_id"])
				->join("LEFT JOIN organizations o ON o.id=t.organization_id")
				->fields(["o.id AS org_id", "o.name AS organization_name"])
				->orderby("p.full_name");

		if ($id)
			$q->where("p.id = ?", $id);
		if ($fullName)
			$q->where("p.full_name LIKE ?", $fullName);
		if ($teamId)
			$q->where("p.team_id = ?", $teamId);

		$stmt = $q->prepare();
		$stmt->execute();

		$players = [];
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$player = new self;
			$player->team = new Team();
			$player->team->organization = new Organization();

			list($player->id, $player->fullName, $player->teamId, $player->exempt, $player->team->id,
					$player->team->designation, $player->team->organizationId, $player->team->organization->id,
					$player->team->organization->name) = $row;
			$players[] = $player;
		}

		return $players;
	}

	/**
	 * Add a new player
	 *
	 * @param string $fullName full name
	 * @param int $teamId team id
	 * @param bool $exempt exempt status
	 * @return int new id
	 * @throws \sma\exceptions\DuplicateException if a team already exists with the same ordinal
	 * for the specified organization
	 */
	public static function add($fullName, $teamId, $exempt) {
		if (count(self::get(null, $fullName, $teamId)) > 0)
			throw new DuplicateException();

		$q = (new InsertQuery(Database::getConnection()))
				->into("players")
				->fields(["full_name", "team_id", "exempt"]);

		if ($teamId)
			$q->values("(?,?,?)", [ucwords($fullName), $teamId, (int) $exempt]);
		else
			$q->values("(?,NULL,?)", [ucwords($fullName), (int) $exempt]);

		$q->prepare()->execute();

		return Database::getConnection()->lastInsertId();
	}

	/**
	 * Update a player
	 *
	 * @param int $id player id to update
	 * @param string $fullName full name
	 * @param bool $exempt exempt status
	 */
	public static function update($id, $fullName=null, $exempt=null) {
		$q = (new UpdateQuery(Database::getConnection()))
				->table("players")
				->where("id = ?", $id)
				->limit(1);

		if ($fullName)
			$q->set("full_name = ?", $fullName);
		if ($exempt !== null)
			$q->set("exempt = ?", (int) $exempt);

		$q->prepare()->execute();
	}
}