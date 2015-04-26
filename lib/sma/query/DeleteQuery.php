<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\query;

/**
 * Delete Query
 *
 * @package \sma\query
 */
class DeleteQuery extends Query {
	use WhereFunctionality;

	/**
	 * Create a query
	 * @param \PDO $pdo pdo
	 */
	public function __construct($pdo) {
		parent::__construct($pdo);
	}

	/**
	 * Set table to delete from
	 *
	 * @param string $table tablename
	 * @return $this current query object
	 */
	public function from($table) {
		return $this->table($table);
	}

	protected function buildQueryString() {
		$q = "DELETE";
		$q .= " FROM " . $this->table;

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

		if (!empty($this->extraClauses))
			$q .= " " . implode(" ", $this->extraClauses);

		return $q;
	}
}