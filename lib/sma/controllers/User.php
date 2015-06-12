<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\controllers;

use sma\models\Alert;
use sma\Controller;
use sma\View;
use sma\models\User as UserModel;
use Exception;

class User {

	public static function index() {
		$visitor = UserModel::getVisitor();

		if (!$visitor->id)
			Controller::redirect("/user/login");

		View::load("user/index.twig", array(
			"username" => $visitor->username,
			"email" => $visitor->email
		));
	}

	public static function login() {
		if (UserModel::getVisitor()->id != 0)
			Controller::redirect(array_key_exists("r", $_GET) ? urlencode($_GET["r"]) : "");

		if (empty($_POST)) {
			View::load("user/login.twig", [
				"redirectTo" => (array_key_exists("r", $_GET) ? urlencode($_GET["r"]) : "")
			]);
		}
		else {
			if (isset($_POST["register"]) && $_POST["register"]) {
				Controller::redirect(ForumsFactory::getForumsInstance()->getRegistrationPage(
						(isset($_POST["email"]) ? $_POST["email"] : null)
				), true);
			}
			try {
				UserModel::attemptLogin($_POST["email"], $_POST["password"], isset($_POST["remember-me"]));
				Controller::addAlert(new Alert("success", "You have been logged in successfully"));
				Controller::redirect((array_key_exists("r", $_GET)) ? $_GET["r"] : "");
			}
			catch (Exception $e) {
				Controller::addAlert(new Alert("error", "The login credentials you entered were incorrect, please try again"));
				Controller::redirect("/user/login");
			}
		}
	}

	public static function logout() {
		UserModel::logout();
		Controller::redirect("");
	}
}