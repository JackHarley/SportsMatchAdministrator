<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\query;

trait WhereFunctionality {

	/**
	 * @var string[] WHERE clauses
	 */
	protected $whereClauses = [];

	/**
	 * Add WHERE clause
	 *
	 * @param string $clause clause with placeholders
	 * @param mixed|mixed[] $variables values to fill into clause
	 * @param string $logic logic type
	 * @return $this current query object
	 */
	public function where($clause, $variables=null, $logic="AND") {
		$clause = $this->processPlaceholdersInClause($clause, $variables);
		$this->whereClauses[] = array($clause, $logic);
		return $this;
	}

	/**
	 * Add WHERE field IN (values) clause
	 *
	 * @param string $field field identifier
	 * @param array $array values the field must match one of
	 * @return $this current query object
	 */
	public function whereInArray($field, $array) {
		if (empty($array)) {
			$q = "false";
		}
		else {
			$q = $field . " IN (";

			$isFirst = true;
			foreach ($array as $value) {
				if (!$isFirst)
					$q .= ",";
				$q .= "?";
				$isFirst = false;
			}

			$q .= ")";
		}

		$this->where($q, $array);

		return $this;
	}

	/**
	 * Add WHERE field NOT IN (values) clause
	 *
	 * @param string $field field identifier
	 * @param array $array values the field must not match any of
	 * @return $this current query object
	 */
	public function whereNotInArray($field, $array) {
		if (empty($array)) {
			$q = "true";
		}
		else {
			$q = $field . " NOT IN (";

			$isFirst = true;
			foreach ($array as $value) {
				if (!$isFirst)
					$q .= ",";
				$q .= "?";
				$isFirst = false;
			}

			$q .= ")";
		}

		$this->where($q, $array);

		return $this;
	}
}