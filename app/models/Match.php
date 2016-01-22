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
	 * @var int status code
	 */
	public $status;
	const STATUS_PENDING = 0;
	const STATUS_RECONCILED = 1;
	const STATUS_MISMATCH = 2;

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
	 * Permanently delete match, reports and participating players
	 */
	public function delete() {
		if ($this->status == self::STATUS_RECONCILED) {
			// we have to reverse the league tables as well as deleting
			// TODO: build this out into a function
			if ($this->homeScore != $this->awayScore) {
				$winnerTeamId = ($this->homeScore > $this->awayScore) ? $this->homeTeamId : $this->awayTeamId;
				$loserTeamId = ($this->homeScore < $this->awayScore) ? $this->homeTeamId : $this->awayTeamId;
				$winnerScore = ($this->homeScore > $this->awayScore) ? $this->homeScore : $this->awayScore;
				$loserScore = ($this->homeScore < $this->awayScore) ? $this->homeScore : $this->awayScore;

				(new UpdateQuery(Database::getConnection()))
					->table("teams")
					->set("wins = wins-1")
					->set("score_for = score_for-?", $winnerScore)
					->set("score_against = score_against-?", $loserScore)
					->set("points = points-" . POINTS_FOR_WIN)
					->where("id = ?", $winnerTeamId)
					->prepare()
					->execute();

				(new UpdateQuery(Database::getConnection()))
					->table("teams")
					->set("losses = losses-1")
					->set("score_for = score_for-?", $loserScore)
					->set("score_against = score_against-?", $winnerScore)
					->set("points = points-" . POINTS_FOR_LOSS)
					->where("id = ?", $loserTeamId)
					->prepare()
					->execute();
			}
			else {
				(new UpdateQuery(Database::getConnection()))
					->table("teams")
					->set("draws = draws-1")
					->set("score_for = score_for-?", $this->homeScore)
					->set("score_against = score_against-?", $this->homeScore)
					->set("points = points-" . (($this->homeScore == 0) ? POINTS_FOR_DRAW : POINTS_FOR_SCORING_DRAW))
					->where("id=? OR id=?", [$this->homeTeamId, $this->awayTeamId])
					->prepare()
					->execute();
			}
		}

		(new DeleteQuery(Database::getConnection()))
			->from("matches")
			->where("id = ?", $this->id)
			->limit(1)
			->prepare()
			->execute();
		(new DeleteQuery(Database::getConnection()))
			->from("match_reports")
			->where("match_id = ?", $this->id)
			->limit(2)
			->prepare()
			->execute();
		(new DeleteQuery(Database::getConnection()))
			->from("matches_players")
			->where("match_id = ?", $this->id)
			->prepare()
			->execute();
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
		// if reconciled, we can't do it again
		if ($this->status == self::STATUS_RECONCILED)
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

			if (($report->homeScore != $homeScore) || ($report->awayScore != $awayScore)) {
				(new UpdateQuery(Database::getConnection()))
						->table("matches")
						->set("status = ?", self::STATUS_MISMATCH)
						->where("id = ?", $this->id)
						->limit(1)
						->prepare()
						->execute();
				return false;
			}
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

		(new UpdateQuery(Database::getConnection()))
				->table("matches")
				->set("status = ?", self::STATUS_RECONCILED)
				->where("id = ?", $this->id)
				->limit(1)
				->prepare()
				->execute();
		return true;
	}

	/**
	 * Correct the reports so that they match
	 *
	 * @param int $correctReportId id of the correct report
	 * @param bool $reconcile when set to true, attempts to reconcile the reports after correcting them
	 */
	public function correctReports($correctReportId, $reconcile=true) {
		$correctReport = current(MatchReport::get($correctReportId));

		$reports = $this->getMatchReports();

		foreach($reports as $report) {
			if ($report->id != $correctReportId) {
				(new UpdateQuery(Database::getConnection()))
					->table("match_reports")
					->where("id = ?", $report->id)
					->set("home_score = ?", $correctReport->homeScore)
					->set("away_score = ?", $correctReport->awayScore)
					->prepare()
					->execute();
			}
		}

		// invalidate quick cache
		$this->matchReports = null;

		if ($reconcile)
			$this->attemptReportReconciliation();
	}

	/**
	 * Correct the date on a match if it is incorrect
	 *
	 * @param int $id match id to correct
	 * @param string $date correct date YYYY-MM-DD
	 * @return int corrected match id (it may have changed in order to re-assign reports)
	 */
	public function correctDate($id, $date) {
		$currentMatch = current(self::get($id));

		$currentMatchReports = $currentMatch->getMatchReports();
		if (count($currentMatchReports) == 2) {
			// we can update the date by simply modifying the match record
			self::updateDate($id, $date);
			return $id;
		}
		else {
			$currentMatchReport = current($currentMatchReports);
		}

		// check if there is a match record for the correct date that we can reassign the report(s) to
		$candidateMatch = current(self::get(null, $date, $currentMatch->leagueId,
				$currentMatch->homeTeamId, $currentMatch->awayTeamId));

		if ($candidateMatch) {
			// check this match has a free spot for this report to be merged into it
			$candidateMatchReports = $candidateMatch->getMatchReports();
			foreach($candidateMatchReports as $report) {
				if ($report->teamId == $currentMatchReport->teamId)
					throw new DuplicateException();
			}

			// we reassign the old report to the new match record, reassign the player records, then purge
			// the old match record
			$newMatchId = $candidateMatch->id;
			(new UpdateQuery(Database::getConnection()))
				->table("match_reports")
				->where("id = ?", $currentMatchReport->id)
				->set("match_id = ?", $newMatchId)
				->prepare()
				->execute();
			(new UpdateQuery(Database::getConnection()))
				->table("matches_players")
				->where("match_id = ?", $currentMatch->id)
				->set("match_id = ?", $newMatchId)
				->prepare()
				->execute();
			(new DeleteQuery(Database::getConnection()))
				->from("matches")
				->where("id = ?", $currentMatch->id)
				->limit(1)
				->prepare()
				->execute();

			// finally trigger a reconciliation attempt
			$candidateMatch = current(self::get($candidateMatch->id));
			$candidateMatch->attemptReportReconciliation();

			return $candidateMatch->id;
		}
		else {
			// we can update the date by simply modifying the match record
			self::updateDate($id, $date);
			return $id;
		}
	}

	/**
	 * Correct the date on a match if it is incorrect
	 *
	 * @param int $id match id to correct
	 * @param int $homeTeamId new home team id
	 * @param int $awayTeamId new away team id
	 * @return int corrected match id (it may have changed in order to re-assign reports)
	 */
	public function correctTeams($id, $homeTeamId=null, $awayTeamId=null) {
		$currentMatch = current(self::get($id));
		$oldHomeTeamId = $currentMatch->homeTeamId;
		$oldAwayTeamId = $currentMatch->awayTeamId;
		$newHomeTeamId = ($homeTeamId) ? $homeTeamId : $currentMatch->homeTeamId;
		$newAwayTeamId = ($awayTeamId) ? $awayTeamId : $currentMatch->awayTeamId;

		/**
		 * @var $currentMatchReports MatchReport[]
		 */
		$currentMatchReports = $currentMatch->getMatchReports();

		if (count($currentMatchReports) == 2) {
			// we can update the team(s) by modifying the match record, and then changing the
			// report submitter team ids (we'll do this at the end because it has to be done regardless)
			$newId = $id;
			self::updateTeams($id, $homeTeamId, $awayTeamId);
		}
		else {
			$currentMatchReport = current($currentMatchReports);

			// check if there is a match record for the correct teams that we can reassign the report(s) to
			$candidateMatch = current(self::get(null, $currentMatch->date, $currentMatch->leagueId,
				$newHomeTeamId, $newAwayTeamId));

			if ($candidateMatch) {
				// check this match has a free spot for this report to be merged into it
				$candidateMatchReports = $candidateMatch->getMatchReports();
				foreach ($candidateMatchReports as $report) {
					if ($report->teamId == $currentMatchReport->teamId)
						throw new DuplicateException();
				}

				// we reassign the old report to the new match record, reassign the player records, then purge
				// the old match record
				$newMatchId = $candidateMatch->id;
				(new UpdateQuery(Database::getConnection()))
					->table("match_reports")
					->where("id = ?", $currentMatchReport->id)
					->set("match_id = ?", $newMatchId)
					->prepare()
					->execute();
				(new UpdateQuery(Database::getConnection()))
					->table("matches_players")
					->where("match_id = ?", $currentMatch->id)
					->set("match_id = ?", $newMatchId)
					->prepare()
					->execute();
				(new DeleteQuery(Database::getConnection()))
					->from("matches")
					->where("id = ?", $currentMatch->id)
					->limit(1)
					->prepare()
					->execute();

				// finally trigger a reconciliation attempt
				$candidateMatch = current(self::get($candidateMatch->id));
				$candidateMatch->attemptReportReconciliation();

				$newId = $candidateMatch->id;
			}
			else {
				// we can update the date by simply modifying the match record
				$newId = $id;
				self::updateTeams($id, $homeTeamId, $awayTeamId);
			}
		}

		// now we update the reporter team since that could also be incorrect if a team managed
		// to somehow incorrectly enter themselves (lusers)
		foreach ($currentMatchReports as $report) {
			if (($report->teamId == $oldHomeTeamId) && ($homeTeamId))
				$report->update($homeTeamId);
			else if (($report->teamId == $oldAwayTeamId) && ($awayTeamId))
				$report->update($awayTeamId);
		}

		// finally we run queries to update player records so that they belong to the changed team id
		(new UpdateQuery(Database::getConnection()))
			->table("matches_players")
			->where("match_id = ?", $newId)
			->where("team_id = ?", $oldHomeTeamId)
			->set("team_id = ?", $newHomeTeamId)
			->prepare()
			->execute();
		(new UpdateQuery(Database::getConnection()))
			->table("matches_players")
			->where("match_id = ?", $newId)
			->where("team_id = ?", $oldAwayTeamId)
			->set("team_id = ?", $newAwayTeamId)
			->prepare()
			->execute();

		return $newId;
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
	 * @param int $status match status constant
	 * @param int $limit maximum number of records to fetch
	 * @return Match[] matches
	 */
	public static function get($id=null, $date=null, $league=null, $homeTeamId=null, $awayTeamId=null,
			$status=null, $limit=null) {

		$q = (new SelectQuery(Database::getConnection()))
				->from("matches m")
				->fields(["m.id", "m.date", "m.league_id", "m.home_team_id", "m.away_team_id", "m.home_score",
						"m.away_score", "m.status"])
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
		if ($status !== null)
			$q->where("m.status = ?", $status);

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
					$m->awayScore, $m->status, $m->homeTeam->designation, $m->homeTeam->organization->name,
					$m->awayTeam->designation, $m->awayTeam->organization->name) = $row;
			$matches[] = $m;
		}

		return $matches;
	}

	/**
	 * Change the date on a match record
	 *
	 * @param int $id match id
	 * @param string $date correct date YYYY-MM-DD
	 */
	protected function updateDate($id, $date) {
		(new UpdateQuery(Database::getConnection()))
				->table("matches")
				->where("id = ?", $id)
				->set("date = ?", $date)
				->limit(1)
				->prepare()
				->execute();
	}

	/**
	 * Change the teams on a match record
	 *
	 * @param int $id match id
	 * @param int $homeTeamId home team id
	 * @param int $awayTeamId away team id
	 */
	protected function updateTeams($id, $homeTeamId, $awayTeamId) {
		$q = (new UpdateQuery(Database::getConnection()))
			->table("matches")
			->where("id = ?", $id)
			->limit(1);

		if ($homeTeamId)
			$q->set("home_team_id = ?", $homeTeamId);
		if ($awayTeamId)
			$q->set("away_team_id = ?", $awayTeamId);

		$q->prepare()->execute();
	}
}