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
	protected $homeTeam;

	/**
	 * @var int home team id
	 */
	public $homeTeamId;

	/**
	 * @var \sma\models\Team away team
	 */
	protected $awayTeam;

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
				$playerId = current($players);
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
	 * @return \sma\models\MatchReport[] match reports
	 */
	public static function get($id=null) {
		$q = (new SelectQuery(Database::getConnection()))
				->from("matches")
				->fields(["id", "date", "league_id", "home_team_id", "away_team_id", "home_score",
						"away_score"])
				->orderby("date", "DESC");

		if ($id)
			$q->where("id = ?", $id);

		$stmt = $q->prepare();
		$stmt->execute();

		$matches = [];
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$m = new self;
			list($m->id, $m->date, $m->leagueId, $m->homeTeamId, $m->awayTeamid, $m->homeScore,
					$m->awayScore) = $row;
			$matches[] = $m;
		}

		return $matches;
	}
}