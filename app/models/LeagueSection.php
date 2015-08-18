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
use sma\exceptions\ObjectCannotBeDeletedException;
use sma\query\DeleteQuery;
use sma\query\InsertQuery;
use sma\query\SelectQuery;

/**
 * League Section
 *
 * @package \sma\models
 */
class LeagueSection {

	/**
	 * @var int id
	 */
	public $id;

	/**
	 * @var string section designated letter
	 */
	public $letter;

	/**
	 * @var \sma\models\League league
	 */
	protected $league;

	/**
	 * @var int league id
	 */
	public $leagueId;

	/**
	 * @var \sma\models\Team[] assigned teams
	 */
	protected $assignedTeams;

	/**
	 * Delete the league section
	 */
	public function delete() {
		if ($this->getAssignedTeams())
			throw new ObjectCannotBeDeletedException();

		(new DeleteQuery(Database::getConnection()))
				->from("league_sections")
				->where("id = ?", $this->id)
				->limit(1)
				->prepare()
				->execute();
	}

	/**
	 * Get league
	 *
	 * @return \sma\models\User
	 */
	public function getLeague() {
		if (!$this->league)
			$this->league = current(League::get($this->leagueId));
		return $this->league;
	}

	/**
	 * Get assigned teams
	 *
	 * @return \sma\models\Team[]
	 */
	public function getAssignedTeams() {
		if (!$this->assignedTeams)
			$this->assignedTeams = Team::get(null, null, null, $this->id);
		return $this->assignedTeams;
	}

	/**
	 * Get objects
	 *
	 * @param int $id id
	 * @param int $leagueId league id
	 * @return \sma\models\LeagueSection[] league sections
	 */
	public static function get($id=null, $leagueId=null) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("league_sections s")
				->fields(["s.id", "s.letter", "s.league_id"])
				->orderby("s.letter");

		if ($id)
			$q->where("s.id = ?", $id);
		if ($leagueId)
			$q->where("s.league_id = ?", $leagueId);

		$stmt = $q->prepare();
		$stmt->execute();

		$sections = [];
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$section = new self;
			list($section->id, $section->letter, $section->leagueId) = $row;
			$sections[] = $section;
		}

		return $sections;
	}

	/**
	 * Add a new section
	 *
	 * @param int $leagueId league to add section for
	 * @return int new id
	 */
	public static function add($leagueId) {
		$currentSections = self::get(null, $leagueId);
		if (!empty($currentSections)) {
			$latestSection = end($currentSections);
			$newLetter = ++$latestSection->letter;
		}
		else {
			$newLetter = "A";
		}

		(new InsertQuery(Database::getConnection()))
				->into("league_sections")
				->fields(["league_id", "letter"])
				->values("(?,?)", [$leagueId, $newLetter])
				->prepare()
				->execute();

		return Database::getConnection()->lastInsertId();
	}
}