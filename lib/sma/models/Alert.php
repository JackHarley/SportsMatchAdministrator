<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\models;

/**
 * Execution alert
 *
 * @package \sma\models
 */
class Alert {

	/**
	 * @var string alert type
	 */
	public $type;

	/**
	 * @var string alert message
	 */
	public $message;

	/**
	 * Construct the alert
	 *
	 * @param string $type alert type
	 * @param string $message alert message
	 */
	public function __construct($type, $message) {
		$this->type = $type;
		$this->message = $message;
	}
}