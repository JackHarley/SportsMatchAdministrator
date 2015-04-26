<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\models;

/**
 * Server Check
 *
 * @package \sma\models
 */
class ServerCheck {

	/**
	 * @var string check name
	 */
	public $name;

	/**
	 * @var string check status
	 */
	public $status;
	const SUCCESS = 1;
	const FAILURE = 0;

	/**
	 * @var string check message
	 */
	public $message;

	/**
	 * Construct the alert
	 *
	 * @param string $name check name
	 * @param int %status check status
	 * @param string $message check message
	 */
	public function __construct($name=null, $status=null, $message=null) {
		$this->name = $name;
		$this->status = $status;
		$this->message = $message;
	}
}