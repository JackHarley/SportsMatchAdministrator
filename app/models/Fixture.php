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
 * Fixture
 *
 * @package \sma\models
 */
class Fixture {

	const TYPE_ASSIGNED_NUMBERS = 1;
	const TYPE_SPECIFIC_TEAMS = 2;

	/**
	 * @var int id
	 */
	public $id;

	/**
	 * @var string play by date (YYYY-MM-DD)
	 */
	public $playByDate;

	/**
	 * @var \sma\models\Team home team
	 */
	public $homeTeam;

	/**
	 * @var int home team id
	 */
	public $homeTeamId;

	/**
	 * @var int home team assigned number
	 */
	public $homeTeamAssignedNumber;

	/**
	 * @var \sma\models\Team away team
	 */
	public $awayTeam;

	/**
	 * @var int away team id
	 */
	public $awayTeamId;

	/**
	 * @var int away team assigned number
	 */
	public $awayTeamAssignedNumber;

	/**
	 * @var int league id (if applicable)
	 */
	public $leagueId;

	/**
	 * Delete the fixture
	 */
	public function delete() {
		(new DeleteQuery(Database::getConnection()))
				->from("fixtures")
				->where("id = ?", $this->id)
				->limit(1)
				->prepare()
				->execute();
	}

	/**
	 * Get league
	 *
	 * @return \sma\models\League league
	 */
	public function getLeague() {
		return current(League::get($this->leagueId));
	}

	/**
	 * Get formatted home team information
	 *
	 * @return string home team string
	 */
	public function getHomeTeamString() {
		return ($this->homeTeamId) ? $this->homeTeam->organization->name . " " . $this->homeTeam->designation : 
				"Team " . $this->homeTeamAssignedNumber;
	}

	/**
	 * Get formatted home team information
	 *
	 * @return string away team string
	 */
	public function getAwayTeamString() {
		return ($this->awayTeamId) ? $this->awayTeam->organization->name . " " . $this->awayTeam->designation :
				"Team " . $this->awayTeamAssignedNumber;
	}

	/**
	 * Get objects
	 *
	 * @param int $id id
	 * @param int $leagueId league to get fixtures for
	 * @return \sma\models\Fixture[] teams
	 */
	public static function get($id=null, $leagueId=null) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("fixtures f")
				->fields(["f.id", "f.play_by_date", "f.home_team_id", "f.home_team_assigned_number",
						"f.away_team_id", "f.away_team_assigned_number", "f.league_id"])
				->orderby("f.play_by_date")
				->join("LEFT JOIN teams ht ON ht.id=f.home_team_id")
				->join("LEFT JOIN organizations ho ON ho.id=ht.organization_id")
				->fields(["ht.designation AS ht_designation", "ho.name AS ho_name"])
				->join("LEFT JOIN teams at ON at.id=f.away_team_id")
				->join("LEFT JOIN organizations ao ON ao.id=at.organization_id")
				->fields(["at.designation AS at_designation", "ao.name AS ao_name"]);

		if ($id)
			$q->where("f.id = ?", $id);
		if ($leagueId)
			$q->where("f.league_id = ?", $leagueId);

		$stmt = $q->prepare();
		$stmt->execute();

		$fixtures = [];
		while($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$f = new self;
			$f->homeTeam = new Team();
			$f->homeTeam->organization = new Organization();
			$f->awayTeam = new Team();
			$f->awayTeam->organization = new Organization();
			list($f->id, $f->playByDate, $f->homeTeamId, $f->homeTeamAssignedNumber, $f->awayTeamId,
					$f->awayTeamAssignedNumber, $f->leagueId, $f->homeTeam->designation,
					$f->homeTeam->organization->name, $f->awayTeam->designation,
					$f->awayTeam->organization->name) = $row;

			$fixtures[] = $f;
		}

		return $fixtures;

	}

	/**
	 * Add a new fixture
	 *
	 * @param int $type TYPE_ASSIGNED_NUMBERS or TYPE_SPECIFIC_TEAMS
	 * @param string $playByDate YYYY-MM-DD
	 * @param int $leagueId league
	 * @param int $homeTeamId home team
	 * @param int $awayTeamId away team
	 * @param int $homeTeamNumber home team assigned number
	 * @param int $awayTeamNumber away team assigned number
	 */
	public static function add($type, $playByDate, $leagueId, $homeTeamId=null, $awayTeamId=null,
			$homeTeamNumber=null, $awayTeamNumber=null) {

		$q = (new InsertQuery(Database::getConnection()))->into("fixtures");

		if ($type == self::TYPE_ASSIGNED_NUMBERS) {
			$q->fields(["play_by_date", "league_id", "home_team_assigned_number", "away_team_assigned_number"]);
			$q->values("(?,?,?,?)", [$playByDate, $leagueId, $homeTeamNumber, $awayTeamNumber]);
		}
		else if ($type == self::TYPE_SPECIFIC_TEAMS) {
			$q->fields(["play_by_date", "league_id", "home_team_id", "away_team_id"]);
			$q->values("(?,?,?,?)", [$playByDate, $leagueId, $homeTeamId, $awayTeamId]);
		}

		$q->prepare()->execute();
	}

	/**
	 * Update a fixture
	 *
	 */
	public static function update() {

	}
}