<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\query;

/**
 * Insert Query
 *
 * @package \sma\query
 */
class InsertQuery extends Query {

	/**
	 * @var mixed[] values to insert
	 */
	protected $values = [];

	/**
	 * Create a query
	 * @param \PDO $pdo pdo
	 */
	public function __construct($pdo) {
		parent::__construct($pdo);
	}

	/**
	 * Set table to insert into
	 *
	 * @param string $table tablename
	 * @return $this current query object
	 */
	public function into($table) {
		return $this->table($table);
	}

	/**
	 * Set values to insert
	 *
	 * @param string $values clause with placeholders
	 * @param mixed|mixed[] $variables values to fill into clause
	 * @return $this current query object
	 */
	public function values($values, $variables=null) {
		$this->values[] = $this->processPlaceholdersInClause($values, $variables);
		return $this;
	}

	protected function buildQueryString() {
		$q = "INSERT INTO " . $this->table;
		$q .= " (" . implode(", ", $this->fields) . ")";

		$q .= " VALUES";
		$isFirst = true;
		foreach($this->values as $values) {
			if (!$isFirst)
				$q .= ",";
			else
				$isFirst = false;

			$q .= " " . $values;
		}

		if ($this->limit)
			$q .= " LIMIT " . $this->limit;

		if (!empty($this->extraClauses))
			$q .= " " . implode(" ", $this->extraClauses);

		return $q;
	}
}