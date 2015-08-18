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
	 * @var \sma\models\LeagueSection league section team is assigned to
	 */
	protected $leagueSection;

	/**
	 * @var int league section id
	 */
	public $leagueSectionId;

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
	 * Delete the team
	 */
	public function delete() {
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
	 * Get objects
	 *
	 * @param int $id id
	 * @param int $organizationId organization id to fetch teams for
	 * @param string $designation designation
	 * @param int|bool $leagueSectionId league section or boolean false to fetch unassigned teams only
	 * @return \sma\models\Team[] teams
	 */
	public static function get($id=null, $organizationId=null, $designation=null,
			$leagueSectionId=null) {

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
	 * Add a new team
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
}