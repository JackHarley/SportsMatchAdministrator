<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\query;

/**
 * Update Query
 *
 * @package \sma\query
 */
class UpdateQuery extends Query {
	use WhereFunctionality;

	/**
	 * @var string[] SET clauses
	 */
	protected $setClauses = [];

	/**
	 * Create a query
	 * @param \PDO $pdo pdo
	 */
	public function __construct($pdo) {
		parent::__construct($pdo);
	}

	/**
	 * Add SET clauses
	 *
	 * @param string $clause clause with placeholders
	 * @param mixed|mixed[] $variables values to fill into clause
	 * @return $this current query object
	 */
	public function set($clause, $variables=null) {
		$this->setClauses[] = $this->processPlaceholdersInClause($clause, $variables);
		return $this;
	}

	protected function buildQueryString() {
		$q = "UPDATE";
		$q .= " " . $this->table;

		if (!empty($this->setClauses)) {
			$q .= " SET";
			$q .= " " . implode(" , ", $this->setClauses);
		}

		if (!empty($this->whereClauses)) {
			$q .= " WHERE";

			$isFirst = true;
			foreach($this->whereClauses as $clause) {
				if (!$isFirst)
					$q .= " " . $clause[1];

				$q .= " " . $clause[0];

				$isFirst = false;
			}
		}

		if ($this->limit)
			$q .= " LIMIT " . $this->limit;

		if (!empty($this->extraClauses))
			$q .= " " . implode(" ", $this->extraClauses);

		return $q;
	}
}