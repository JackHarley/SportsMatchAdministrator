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
 * Team
 *
 * @package \sma\models
 */
class Team {

	/**
	 * @var int id
	 */
	public $id;

	/**
	 * @var string team designation e.g. Senior 1, Senior 2, Junior A, Junior B
	 */
	public $designation;

	/**
	 * @var \sma\models\Organization organization
	 */
	public $organization;

	/**
	 * @var int organization id
	 */
	public $organizationId;

	/**
	 * @var \sma\models\League league team is assigned to
	 */
	protected $league;

	/**
	 * @var int league id
	 */
	public $leagueId;

	/**
	 * @var \sma\models\LeagueSection league section team is assigned to
	 */
	protected $leagueSection;

	/**
	 * @var int league section id
	 */
	public $leagueSectionId;

	/**
	 * @var int assigned number for fixtures use
	 */
	public $assignedNumber;

	/**
	 * @var \sma\models\User registrant
	 */
	public $registrant;

	/**
	 * @var int registrant id
	 */
	public $registrantId;

	/**
	 * @var int epoch registered
	 */
	public $epochRegistered;

	/**
	 * Score related variables
	 */
	public $scoreFor;
	public $scoreAgainst;
	public $wins;
	public $draws;
	public $losses;
	public $points;

	/**
	 * Delete the team
	 */
	public function delete() {
		(new DeleteQuery(Database::getConnection()))
				->from("players")
				->where("team_id = ?", $this->id)
				->prepare()
				->execute();

		(new DeleteQuery(Database::getConnection()))
				->from("teams")
				->where("id = ?", $this->id)
				->limit(1)
				->prepare()
				->execute();
	}

	/**
	 * Get players
	 *
	 * @return \sma\models\Player[] players
	 */
	public function getPlayers() {
		return Player::get(null, null, $this->id);
	}

	/**
	 * Get number of players marked as exempt
	 *
	 * @return int number of exempt players
	 */
	public function getNumberOfExemptPlayers() {
		$q = (new SelectQuery(Database::getConnection()))
				->from("players")
				->where("team_id = ?", $this->id)
				->where("exempt = 1")
				->fields("COUNT(1)");
		$stmt = $q->prepare();
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	/**
	 * Get league
	 *
	 * @return \sma\models\League
	 */
	public function getLeague() {
		if (!$this->league)
			$this->league = current(League::get($this->leagueId));
		return $this->league;
	}

	/**
	 * Get league section
	 *
	 * @return \sma\models\LeagueSection
	 */
	public function getLeagueSection() {
		if (!$this->leagueSection)
			$this->leagueSection = current(LeagueSection::get($this->leagueSectionId));
		return $this->leagueSection;
	}

	/**
	 * Get score difference
	 *
	 * @return int score difference
	 */
	public function getScoreDifference() {
		return $this->scoreFor - $this->scoreAgainst;
	}

	/**
	 * Get number of matches played
	 *
	 * @return int score difference
	 */
	public function getMatchesPlayed() {
		return $this->wins + $this->draws + $this->losses;
	}

	/**
	 * Get objects
	 *
	 * @param int $id id
	 * @param int $organizationId organization id to fetch teams for
	 * @param string $designation designation
	 * @param int|bool $leagueSectionId league section or boolean false to fetch unassigned teams only
	 * @param int|bool $leagueId league or boolean false to fetch unassigned teams only
	 * @param int $orderMethod one of the order method constants (ASSIGNED_NUMBER, POINTS)
	 * @return \sma\models\Team[] teams
	 */
	const ASSIGNED_NUMBER = 1;
	const POINTS = 2;
	public static function get($id=null, $organizationId=null, $designation=null,
			$leagueSectionId=null, $leagueId=null, $orderMethod=self::ASSIGNED_NUMBER) {

		$q = (new SelectQuery(Database::getConnection()))
				->from("teams t")
				->fields(["t.id", "t.designation", "t.organization_id", "t.league_section_id",
						"t.league_id", "t.assigned_number", "t.registrant_id", "t.epoch_registered",
						"t.score_for", "t.score_against", "t.wins", "t.draws", "t.losses", "t.points"])
				->join("LEFT JOIN organizations o ON o.id=t.organization_id")
				->fields(["o.id AS org_id", "o.name AS organization_name"])
				->join("LEFT JOIN users u ON u.id=t.registrant_id")
				->fields(["u.id AS u_id", "u.full_name"]);

		if ($orderMethod == self::ASSIGNED_NUMBER)
			$q->orderby("t.assigned_number");
		else if ($orderMethod == self::POINTS) {
			$q->fields("(CAST(t.score_for AS SIGNED)-CAST(t.score_against AS SIGNED)) AS score_difference");
			$q->orderby("t.points", "DESC");
			$q->orderby("score_difference", "DESC");
		}

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
		if ($leagueId !== null) {
			if ($leagueId === false)
				$q->where("t.league_id IS NULL");
			else
				$q->where("t.league_id = ?", $leagueId);
		}

		$stmt = $q->prepare();
		$stmt->execute();

		$teams = [];
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$t = new self;
			$t->organization = new Organization();
			$t->registrant = new User();
			list($t->id, $t->designation, $t->organizationId, $t->leagueSectionId,
					$t->leagueId, $t->assignedNumber, $t->registrantId, $t->epochRegistered,
					$t->scoreFor, $t->scoreAgainst, $t->wins, $t->draws, $t->losses, $t->points,
					$t->organization->id, $t->organization->name, $t->registrant->id,
					$t->registrant->fullName) = $row;
			$teams[] = $t;
		}

		return $teams;
	}

	/**
	 * Add a new team
	 *
	 * @param int $organizationId organization
	 * @param string $designation designation
	 * @param int $registrantId registrant
	 * @param int $leagueId league id
	 * @return int new id
	 * @throws \sma\exceptions\DuplicateException if a team already exists with the same designation
	 * for the specified organization
	 */
	public static function add($organizationId, $designation, $registrantId, $leagueId=null) {
		if (count(self::get(null, $organizationId, $designation)) > 0)
			throw new DuplicateException();

		$q = (new InsertQuery(Database::getConnection()))
				->into("teams");

		if ($leagueId) {
			$q->fields(["organization_id", "designation", "registrant_id", "league_id", "epoch_registered"])
				->values("(?,?,?,?,?)", [$organizationId, $designation, $registrantId, $leagueId, time()]);
		}
		else {
			$q->fields(["organization_id", "designation", "registrant_id", "epoch_registered"])
				->values("(?,?,?,?)", [$organizationId, $designation, $registrantId, time()]);
		}

		$q->prepare()->execute();

		return Database::getConnection()->lastInsertId();
	}

	/**
	 * Update a team
	 *
	 * @param int $id team id to update
	 * @param int $organizationId organization
	 * @param string $designation designation
	 * @param int $leagueSectionId league section
	 * @param int $leagueId league id
	 * @param int $assignedNumber assigned number
	 */
	public static function update($id, $organizationId=null, $designation=null,
			$leagueSectionId=null, $leagueId=null, $assignedNumber=null) {

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
		if ($leagueId !== null) {
			$team = current(self::get($id));
			if ($team->leagueId != $leagueId) // if we are changing the league id then we reset the section
				$q->set("league_section_id = NULL");

			if ($leagueId == 0)
				$q->set("league_id = NULL");
			else
				$q->set("league_id = ?", $leagueId);
		}
		if ($assignedNumber !== null) {
			if (!$assignedNumber)
				$q->set("assigned_number = NULL");
			else
				$q->set("assigned_number = ?", $assignedNumber);
		}

		$q->prepare()->execute();
	}

	/**
	 * Get valid designations
	 *
	 * @return string[]|bool either boolean true indicating any designation is acceptable or an
	 * array of valid string designations
	 */
	public static function getValidDesignations() {
		$q = (new SelectQuery(Database::getConnection()))
				->from("valid_team_designations")
				->fields(["designation"]);
		$stmt = $q->prepare();
		$stmt->execute();

		$data = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
		return (!empty($data)) ? $data : true;
	}
}