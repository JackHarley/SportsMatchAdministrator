<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\query;

/**
 * Select Query
 *
 * @package \sma\query
 */
class SelectQuery extends Query {
	use WhereFunctionality;

	/**
	 * @var string[] JOIN clauses
	 */
	protected $joins = [];

	/**
	 * @var string[] columns to order by
	 */
	protected $orderedColumns = [];

	/**
	 * Create a query
	 * @param \PDO $pdo pdo
	 */
	public function __construct($pdo) {
		parent::__construct($pdo);
	}

	/**
	 * Set table to select from
	 *
	 * @param string $table tablename
	 * @return $this current query object
	 */
	public function from($table) {
		return $this->table($table);
	}

	/**
	 * Add JOIN clause
	 *
	 * @param string $clause clause
	 * @return $this current query object
	 */
	public function join($clause) {
		$this->joins[] = $clause;
		return $this;
	}

	/**
	 * Add column to order by
	 *
	 * @param string $field column name
	 * @param string $order order direction
	 * @return $this current query object
	 */
	public function orderby($field, $order="ASC") {
		$this->orderedColumns[] = [$field, $order];
		return $this;
	}

	protected function buildQueryString() {
		$q = "SELECT";
		$q .= " " . implode(", ", $this->fields);
		$q .= " FROM " . $this->table;

		if (!empty($this->joins))
			$q .= " " . implode(" ", $this->joins);

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

		if (!empty($this->orderedColumns)) {
			$q .= " ORDER BY ";
			$isFirst = true;
			foreach($this->orderedColumns as $column) {
				if (!$isFirst)
					$q .= ", ";

				$q .= $column[0] . " " . $column[1];

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