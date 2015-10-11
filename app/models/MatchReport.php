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
 * Match Report
 *
 * @package \sma\models
 */
class MatchReport {

	/**
	 * @var int id
	 */
	public $id;

	/**
	 * @var int submission epoch
	 */
	public $epoch;

	/**
	 * @var \sma\models\Match match
	 */
	protected $match;

	/**
	 * @var int match id
	 */
	public $matchId;

	/**
	 * @var \sma\models\User submitting user
	 */
	protected $user;

	/**
	 * @var int submitting user id
	 */
	public $userId;

	/**
	 * @var \sma\models\Team submitting team
	 */
	protected $team;

	/**
	 * @var int submitting team id
	 */
	public $teamId;

	/**
	 * @var int home score according to report
	 */
	public $homeScore;

	/**
	 * @var int away score according to report
	 */
	public $awayScore;

	/**
	 * Get match record
	 *
	 * @return \sma\models\Match match
	 */
	public function getMatch() {
		if (!$this->match)
			$this->match = current(Match::get($this->matchId));
		return $this->match;
	}

	/**
	 * Get user
	 *
	 * @return \sma\models\User user
	 */
	public function getUser() {
		return current(User::get($this->userId));
	}

	/**
	 * Permanently delete report
	 */
	public function delete() {
		(new DeleteQuery(Database::getConnection()))
			->from("match_reports")
			->where("id = ?", $this->id)
			->limit(1)
			->prepare()
			->execute();
	}

	/**
	 * Get objects
	 *
	 * @param int $id id
	 * @param int $matchId match id to get reports for
	 * @param int $userId user id to restrict reports to
	 * @param int|int[] $teamId submitting team id(s) to restrict reports to
	 * @param int $limit maximum number of rows to fetch or null for no limit
	 * @return \sma\models\MatchReport[] match reports
	 */
	public static function get($id=null, $matchId=null, $userId=null, $teamId=null, $limit=null) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("match_reports mr")
				->fields(["mr.id", "mr.epoch", "mr.match_id", "mr.user_id", "mr.team_id",
						"mr.home_score", "mr.away_score"])
				->orderby("mr.epoch", "DESC");

		if ($id)
			$q->where("mr.id = ?", $id);
		if ($matchId)
			$q->where("mr.match_id = ?", $matchId);
		if ($userId)
			$q->where("mr.user_id = ?", $userId);
		if ($teamId) {
			if (is_array($teamId))
				$q->whereInArray("mr.team_id", $teamId);
			else
				$q->where("mr.team_id = ?", $teamId);
		}
		if ($limit)
			$q->limit($limit);

		$stmt = $q->prepare();
		$stmt->execute();

		$reports = [];
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$report = new self;
			list($report->id, $report->epoch, $report->matchId, $report->userId, $report->teamId,
					$report->homeScore, $report->awayScore) = $row;
			$reports[] = $report;
		}

		return $reports;
	}

	/**
	 * Add a new report
	 *
	 * @param int $matchId match id
	 * @param int $teamId team id the report is being submitted from
	 * @param int $userId submitter user id
	 * @param int $homeScore home score
	 * @param int $awayScore away score
	 * @return int new id
	 * @throws \sma\exceptions\DuplicateException if a report for this match already exists from
	 * this team
	 */
	public static function add($matchId, $teamId, $userId, $homeScore, $awayScore) {
		if (count(self::get(null, $matchId, null, $teamId)) > 0)
			throw new DuplicateException();

		(new InsertQuery(Database::getConnection()))
				->into("match_reports")
				->fields(["match_id", "team_id", "user_id", "home_score", "away_score", "epoch"])
				->values("(?,?,?,?,?,?)", [$matchId, $teamId, $userId, $homeScore, $awayScore, time()])
				->prepare()
				->execute();

		return Database::getConnection()->lastInsertId();

	}
}