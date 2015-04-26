<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\query;

use sma\exceptions\NoValueForQueryPlaceholderException;

/**
 * Database Query
 *
 * @package \sma\query
 */
abstract class Query {

	/**
	 * @var string[] fields to perform query on
	 */
	protected $fields = [];

	/**
	 * @var string[] bound parameters
	 */
	protected $boundParameters = [];

	/**
	 * @var string[] extra clauses
	 */
	protected $extraClauses = [];

	/**
	 * @var string table name to perform query on
	 */
	protected $table;

	/**
	 * @var int limit of rows query can affect
	 */
	protected $limit;

	/**
	 * @var \PDO pdo
	 */
	protected $pdo;

	/**
	 * Create a query
	 * @param \PDO $pdo pdo
	 */
	public function __construct($pdo) {
		$this->pdo = $pdo;
	}

	/**
	 * Set the table to perform query on
	 *
	 * @param string $table tablename
	 * @return $this current query object
	 */
	public function table($table) {
		$this->table = $table;
		return $this;
	}

	/**
	 * Set the fields to perform query on
	 *
	 * @param string|string[] $fields fieldnames
	 * @return $this current query object
	 */
	public function fields($fields) {
		if (!is_array($fields))
			$fields = [$fields];

		$this->fields = array_merge($this->fields, $fields);

		return $this;
	}

	/**
	 * Set the maximum number of rows query may affect
	 *
	 * @param int $limit limit or null for no limit
	 * @return $this current query object
	 */
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Add an extra clause
	 *
	 * @param string $clause clause with placeholders
	 * @param mixed|mixed[] $variables values to fill into clause
	 * @return $this current query object
	 */
	public function extraClause($clause, $variables=null) {
		$this->extraClauses[] = $this->processPlaceholdersInClause($clause, $variables);
		return $this;
	}

	/**
	 * Generate the PDO statement
	 *
	 * @return \PDOStatement statement
	 */
	public function prepare() {
		$q = $this->buildQueryString();
		$stmt = $this->pdo->prepare($q);

		foreach($this->boundParameters as $i => $value) {
			$stmt->bindValue(":" . $i, $value);
		}

		QueryCounter::increment();

		return $stmt;
	}

	/**
	 * Replace placeholders in clause
	 *
	 * @param string $clause clause
	 * @param mixed|mixed[] $variables variables
	 * @return string filled in clause
	 * @throws \sma\exceptions\NoValueForQueryPlaceholderException a parameter is missing
	 */
	protected function processPlaceholdersInClause($clause, $variables=null) {
		if (!is_array($variables))
			$variables = [$variables];

		while(($position = strpos($clause, "?")) !== false) {
			if (empty($variables))
				throw new NoValueForQueryPlaceholderException();
			$parameterValue = reset($variables);
			$key = ((array_push($this->boundParameters, $parameterValue)) - 1);
			$clause = substr_replace($clause, ":" . $key, $position, 1);
			unset($variables[key($variables)]);
		}

		return $clause;
	}

	/**
	 * Build SQL-query string
	 *
	 * @return string query
	 */
	abstract protected function buildQueryString();
}