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
use sma\query\UpdateQuery;

/**
 * Match
 *
 * @package \sma\models
 */
class Match {

	/**
	 * @var int id
	 */
	public $id;

	/**
	 * @var string date played (YYYY-MM-DD)
	 */
	public $date;

	/**
	 * @var \sma\models\League league team is assigned to
	 */
	protected $league;

	/**
	 * @var int league id
	 */
	public $leagueId;

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
	 * @var int home team score
	 */
	public $homeScore;

	/**
	 * @var int away team score
	 */
	public $awayScore;

	/**
	 * @var MatchReport[] associated match reports
	 */
	protected $matchReports;

	/**
	 * Get reports for the match
	 *
	 * @return MatchReport[] match reports associated with this match
	 */
	public function getMatchReports() {
		if (!$this->matchReports)
			$this->matchReports = MatchReport::get(null, $this->id);
		return $this->matchReports;
	}

	public function getHomeTeamMatchReport() {
		foreach($this->getMatchReports() as $report)
			if ($report->teamId == $this->homeTeamId)
				return $report;
		return null;
	}

	public function getAwayTeamMatchReport() {
		foreach($this->getMatchReports() as $report)
			if ($report->teamId == $this->awayTeamId)
				return $report;
		return null;
	}

	public function getHomeTeamPlayers() {
		return Player::getMatchPlayers($this->id, $this->homeTeamId);
	}

	public function getAwayTeamPlayers() {
		return Player::getMatchPlayers($this->id, $this->awayTeamId);
	}

	/**
	 * Add player to match
	 *
	 * @param int $teamId team
	 * @param int $playerId player id
	 * @param int $playerName player name if id unknown/inapplicable
	 */
	public function addParticipatingPlayer($teamId, $playerId, $playerName=null) {
		if (!$playerId) {
			$players = Player::get(null, $playerName);
			if ($players)
				$playerId = current($players)->id;
			else
				$playerId = Player::add($playerName, null, false);
		}

		(new InsertQuery(Database::getConnection()))
				->into("matches_players")
				->fields(["match_id", "team_id", "player_id"])
				->values("(?,?,?)", [$this->id, $teamId, $playerId])
				->prepare()
				->execute();
	}

	/**
	 * Attempts to reconcile the match reports for the match
	 *
	 * @return bool true if a reconciliation was completed/not needed/not able to be performed yet
	 * or false if the reconciliation failed due to a mismatch
	 */
	public function attemptReportReconciliation() {
		// if scores are already entered, we can't reconcile
		if (($this->homeScore) || ($this->awayScore))
			return true;

		// attempt to get both reports
		$reports = $this->getMatchReports();
		if (count($reports) != 2)
			return true;

		// check if scores match
		$homeScore = null;
		$awayScore = null;
		foreach($reports as $report) {
			if (!$homeScore)
				$homeScore = $report->homeScore;
			if (!$awayScore)
				$awayScore = $report->awayScore;

			if (($report->homeScore != $homeScore) || ($report->awayScore != $awayScore))
				return false;
		}

		// update match record
		(new UpdateQuery(Database::getConnection()))
				->table("matches")
				->set("home_score = ?", $homeScore)
				->set("away_score = ?", $awayScore)
				->where("id = ?", $this->id)
				->limit(1)
				->prepare()
				->execute();

		// update team records
		if ($homeScore != $awayScore) {
			$winnerTeamId = ($homeScore > $awayScore) ? $this->homeTeamId : $this->awayTeamId;
			$loserTeamId = ($homeScore < $awayScore) ? $this->homeTeamId : $this->awayTeamId;
			$winnerScore = ($homeScore > $awayScore) ? $homeScore : $awayScore;
			$loserScore = ($homeScore < $awayScore) ? $homeScore : $awayScore;

			(new UpdateQuery(Database::getConnection()))
				->table("teams")
				->set("wins = wins+1")
				->set("score_for = score_for+?", $winnerScore)
				->set("score_against = score_against+?", $loserScore)
				->set("points = points+" . POINTS_FOR_WIN)
				->where("id = ?", $winnerTeamId)
				->prepare()
				->execute();

			(new UpdateQuery(Database::getConnection()))
				->table("teams")
				->set("losses = losses+1")
				->set("score_for = score_for+?", $loserScore)
				->set("score_against = score_against+?", $winnerScore)
				->set("points = points+" . POINTS_FOR_LOSS)
				->where("id = ?", $loserTeamId)
				->prepare()
				->execute();
		}
		else {
			(new UpdateQuery(Database::getConnection()))
					->table("teams")
					->set("draws = draws+1")
					->set("score_for = score_for+?", $homeScore)
					->set("score_against = score_against+?", $homeScore)
					->set("points = points+" . (($homeScore == 0) ? POINTS_FOR_DRAW : POINTS_FOR_SCORING_DRAW))
					->where("id=? OR id=?", [$this->homeTeamId, $this->awayTeamId])
					->prepare()
					->execute();
		}

		return true;
	}

	/**
	 * Add a new match
	 *
	 * @param string $date date played (YYYY-MM-DD)
	 * @param int $leagueId league id
	 * @param int $homeTeamId home team id
	 * @param int $awayTeamId away team id
	 * @return int new id
	 */
	public static function add($date, $leagueId, $homeTeamId, $awayTeamId) {
		(new InsertQuery(Database::getConnection()))
				->into("matches")
				->fields(["date", "league_id", "home_team_id", "away_team_id"])
				->values("(?,?,?,?)", [$date, $leagueId, $homeTeamId, $awayTeamId])
				->prepare()
				->execute();

		return Database::getConnection()->lastInsertId();
	}

	/**
	 * Get objects
	 *
	 * @param int $id id
	 * @param string $date date to filter by
	 * @param int $league league to filter by
	 * @param int $homeTeamId home team id to filter by
	 * @param int $awayTeamId away team id to filter by
	 * @param bool $reconciled set to true to only fetch reconciled matches, false to only fetch
	 * unreconciled matches
	 * @param int $limit maximum number of records to fetch
	 * @return Match[] matches
	 */
	public static function get($id=null, $date=null, $league=null, $homeTeamId=null, $awayTeamId=null,
			$reconciled=null, $limit=null) {

		$q = (new SelectQuery(Database::getConnection()))
				->from("matches m")
				->fields(["m.id", "m.date", "m.league_id", "m.home_team_id", "m.away_team_id", "m.home_score",
						"m.away_score"])
				->join("LEFT JOIN teams ht ON ht.id=m.home_team_id")
				->fields(["ht.designation AS ht_designation"])
				->join("LEFT JOIN organizations ho ON ho.id=ht.organization_id")
				->fields(["ho.name AS ht_org_name"])
				->join("LEFT JOIN teams at ON at.id=m.away_team_id")
				->fields(["at.designation AS at_designation"])
				->join("LEFT JOIN organizations ao ON ao.id=at.organization_id")
				->fields(["ao.name AS at_org_name"])
				->orderby("date", "DESC");

		if ($limit)
			$q->limit($limit);

		if ($id)
			$q->where("m.id = ?", $id);
		if ($date)
			$q->where("m.date = ?", $date);
		if ($league)
			$q->where("m.league_id = ?", $league);
		if ($homeTeamId)
			$q->where("m.home_team_id = ?", $homeTeamId);
		if ($awayTeamId)
			$q->where("m.away_team_id = ?", $awayTeamId);
		if ($reconciled !== null) {
			if ($reconciled)
				$q->where("m.home_score IS NOT NULL AND m.away_score IS NOT NULL");
			else
				$q->where("m.home_score IS NULL OR m.away_score IS NULL");
		}

		$stmt = $q->prepare();
		$stmt->execute();

		$matches = [];
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$m = new self;
			$m->homeTeam = new Team();
			$m->awayTeam = new Team();
			$m->homeTeam->organization = new Organization();
			$m->awayTeam->organization = new Organization();
			list($m->id, $m->date, $m->leagueId, $m->homeTeamId, $m->awayTeamId, $m->homeScore,
					$m->awayScore, $m->homeTeam->designation, $m->homeTeam->organization->name,
					$m->awayTeam->designation, $m->awayTeam->organization->name) = $row;
			$matches[] = $m;
		}

		return $matches;
	}
}