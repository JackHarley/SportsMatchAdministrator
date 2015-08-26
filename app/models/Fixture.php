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
	 * @var \sma\models\Team away team
	 */
	public $awayTeam;

	/**
	 * @var int away team id
	 */
	public $awayTeamId;

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
	 * Get objects
	 *
	 * @param int $id id
	 * @param int $leagueId league to get fixtures for
	 * @return \sma\models\Fixture[] teams
	 */
	public static function get($id=null, $leagueId=null) {

		$q = (new SelectQuery(Database::getConnection()))
				->from("teams t")
				->fields(["t.id", "t.designation", "t.organization_id", "t.league_section_id",
						"t.registrant_id", "t.epoch_registered"])
				->join("LEFT JOIN organizations o ON o.id=t.organization_id")
				->fields(["o.id AS org_id", "o.name AS organization_name"])
				->join("LEFT JOIN users u ON u.id=t.registrant_id")
				->fields(["u.id AS u_id", "u.full_name"]);

		if ($id)
			$q->where("t.id = ?", $id);
		if ($organizationId)
			$q->where("t.organization_id = ?", $organizationId);
		if ($designation)
			$q->where("t.designation = ?", $designation);
		if ($leagueSectionId !== null) {
			if ($leagueSectionId === false)
				$q->where("t.league_section_id IS NULL");
			else
				$q->where("t.league_section_id = ?", $leagueSectionId);
		}

		$stmt = $q->prepare();
		$stmt->execute();

		$teams = [];
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$team = new self;
			$team->organization = new Organization();
			$team->registrant = new User();
			list($team->id, $team->designation, $team->organizationId, $team->leagueSectionId,
					$team->registrantId, $team->epochRegistered, $team->organization->id,
					$team->organization->name, $team->registrant->id, $team->registrant->fullName) = $row;
			$teams[] = $team;
		}

		return $teams;
	}

	/**
	 * Add a new fixture
	 *
	 * @param int $organizationId organization
	 * @param string $designation designation
	 * @param int $registrantId registrant
	 * @return int new id
	 * @throws \sma\exceptions\DuplicateException if a team already exists with the same designation
	 * for the specified organization
	 */
	public static function add($organizationId, $designation, $registrantId) {
		if (count(self::get(null, $organizationId, $designation)) > 0)
			throw new DuplicateException();

		(new InsertQuery(Database::getConnection()))
				->into("teams")
				->fields(["organization_id", "designation", "registrant_id", "epoch_registered"])
				->values("(?,?,?,?)", [$organizationId, $designation, $registrantId, time()])
				->prepare()
				->execute();

		return Database::getConnection()->lastInsertId();
	}

	/**
	 * Update a team
	 *
	 * @param int $id team id to update
	 * @param int $organizationId organization
	 * @param string $designation designation
	 * @param int $leagueSectionId league section
	 */
	public static function update($id, $organizationId=null, $designation=null,
			$leagueSectionId=null) {

		$q = (new UpdateQuery(Database::getConnection()))
				->table("teams")
				->where("id = ?", $id)
				->limit(1);

		if ($organizationId)
			$q->set("organization_id = ?", $organizationId);
		if ($designation)
			$q->set("designation = ?", $designation);
		if ($leagueSectionId !== null) {
			if ($leagueSectionId == 0)
				$q->set("league_section_id = NULL");
			else
				$q->set("league_section_id = ?", $leagueSectionId);
		}

		$q->prepare()->execute();
	}
}