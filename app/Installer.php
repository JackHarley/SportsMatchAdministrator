<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma;

use PDO;
use sma\exceptions\DatabaseLockedException;
use sma\models\Permission;
use sma\models\ServerCheck;
use sma\models\UserGroup;
use sma\query\SelectQuery;

/**
 * Database Installer
 *
 * @package sma
 */
class Installer {

	/**
	 * Current version of the database
	 */
	const DB_VERSION = 1;

	/**
	 * Checks if the DB is installed and up to date
	 *
	 * @return int DATABASE_STATUS_* constant
	 */
	const DATABASE_STATUS_NOT_INSTALLED = 0;
	const DATABASE_STATUS_NOT_UP_TO_DATE = 1;
	const DATABASE_STATUS_INSTALLED = 2;

	/**
	 * This is a static class, it may not be instantiated
	 */
	private function __construct() { }

	/**
	 * Checks the database and determines its status, returning one of the DATABASE_STATUS_*
	 * constants from this class
	 *
	 * @return int DATABASE_STATUS_* constant
	 */
	public static function getDatabaseStatus() {
		$db = Database::getConnection();

		$db->query("
			CREATE TABLE IF NOT EXISTS `database_version` (
			  `database_version` bigint(20) unsigned NOT NULL,
			  PRIMARY KEY (`database_version`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

		$q = (new SelectQuery($db))
				->from("database_version")
				->fields("database_version")
				->orderby("database_version", "DESC")
				->limit(1);

		$stmt = $q->prepare();

		if (!$stmt)
			return Installer::DATABASE_STATUS_NOT_INSTALLED;

		$stmt->execute();
		$row = $stmt->fetchObject();

		if (!isset($row->database_version))
			return Installer::DATABASE_STATUS_NOT_INSTALLED;

		if ($row->database_version < self::DB_VERSION)
			return Installer::DATABASE_STATUS_NOT_UP_TO_DATE;

		return Installer::DATABASE_STATUS_INSTALLED;
	}

	/**
	 * Checks if the DB is locked (and therefore installation cannot be performed)
	 *
	 * @return boolean true if the installer is locked, otherwise false
	 */
	public static function databaseLocked() {
		return is_file(__DIR__ . "/../install/LOCK");
	}

	/**
	 * Checks to ensure the runtime environment meets all of the requirements to run the software
	 * and returns an array of ServerCheck objects
	 *
	 * @return ServerCheck[] results of each server check
	 */
	public static function checkRequirements() {
		$checks = [];

		// PHP version
		$check = new ServerCheck("PHP");
		if ((!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50400)) {
			$check->status = ServerCheck::FAILURE;
			$check->message = phpversion() . ". Requires PHP >= 5.4.0";
		}
		else {
			$check->status = ServerCheck::SUCCESS;
			$check->message = phpversion();
		}
		$checks[] = $check;

		// Check password_* function support
		$check = new ServerCheck("Password Hashing");
		if (PHP_VERSION_ID >= 50500) {
			$check->status = ServerCheck::SUCCESS;
			$check->message = "Natively Supported";
		}
		else if (\PasswordCompat\binary\check()) {
			$check->status = ServerCheck::SUCCESS;
			$check->message = "Supported via password_compat Library";
		}
		else {
			$check->status = ServerCheck::FAILURE;
			$check->message = "Not Supported! Requires PHP >= 5.3.7, DO NOT CONTINUE WITHOUT UPGRADING!";
		}
		$checks[] = $check;

		// Check PHP MySQL extension
		$check = new ServerCheck("PHP MySQL");
		if (extension_loaded("mysql")) {
			$check->status = ServerCheck::SUCCESS;
			$check->message = "Extension Loaded";
		}
		else {
			$check->status = ServerCheck::FAILURE;
			$check->message = "Please install the PHP5 MySQL extension before continuing";
		}
		$checks[] = $check;

		// Check MySQL server
		$check = new ServerCheck("MySQL Server");
		if (Database::getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION)) {
			$check->status = ServerCheck::SUCCESS;
			$check->message = Database::getConnection()->getAttribute(PDO::ATTR_SERVER_VERSION);
		}
		else {
			$check->status = ServerCheck::FAILURE;
			$check->message = "Please install MySQL Server before continuing";
		}
		$checks[] = $check;

		// Check cache directory writable
		$check = new ServerCheck("Cache Directory");
		if (is_writable(__DIR__ . "/../cache")) {
			$check->status = ServerCheck::SUCCESS;
			$check->message = "Writable";
		}
		else {
			$check->status = ServerCheck::FAILURE;
			$check->message = "Not Writable, CHMOD/CHOWN the /cache folder before continuing";
		}
		$checks[] = $check;

		// Check install directory writable
		$check = new ServerCheck("Install Directory");
		if (is_writable(__DIR__ . "/../install")) {
			$check->status = ServerCheck::SUCCESS;
			$check->message = "Writable";
		}
		else {
			$check->status = ServerCheck::FAILURE;
			$check->message = "Not Writable, CHMOD/CHOWN the /install folder before continuing";
		}
		$checks[] = $check;

		return $checks;
	}

	/**
	 * Installs the tables into the database or upgrades to the latest version
	 *
	 * @param boolean $overwriteExisting set to true to wipe database before install
	 * @throws \sma\exceptions\DatabaseLockedException if the database is locked and overwrite existing has been
	 * specified
	 */
	public static function installDatabase($overwriteExisting=false) {
		if (($overwriteExisting) && (static::databaseLocked()))
			throw new DatabaseLockedException;

		@session_start();
		@session_destroy();

		$db = Database::getConnection();

		if ($overwriteExisting) {
			$tables = [
				"autologins",
				"database_version",
				"fixtures",
				"league_sections",
				"leagues",
				"match_reports",
				"matches",
				"matches_players",
				"organizations",
				"permissions",
				"players",
				"settings",
				"teams",
				"user_groups",
				"user_groups_permissions",
				"users",
				"valid_team_designations"
			];
			$tableString = "`" . implode("`, `", $tables) . "`";
			$db->query("DROP TABLE IF EXISTS " . $tableString);
		}

		$db->query("
			CREATE TABLE IF NOT EXISTS `database_version` (
				`database_version` bigint(20) unsigned NOT NULL
			) ENGINE=InnoDB CHARSET=utf8;

			INSERT INTO `database_version` (`database_version`) VALUES (0);"
		);

		static::migrateDatabase(self::DB_VERSION);

		if ($overwriteExisting)
			static::loadInitialData();

		static::lockDatabase();
	}

	/**
	 * Migrate the database to the specified version
	 *
	 * @param int $version version to migrate the database to
	 */
	protected static function migrateDatabase($version) {
		$db = Database::getConnection();

		$currentVersion =
				$db->query("SELECT * FROM database_version ORDER BY database_version DESC")
						->fetchColumn();

		while ($currentVersion < $version) {
			$nextVersion = $currentVersion + 1;
			$migrationName = "migrateToVersion" . $nextVersion;
			static::$migrationName();
			$currentVersion = $nextVersion;
			$stmt = $db->prepare("UPDATE database_version SET database_version=?");
			$stmt->execute([$currentVersion]);
		}
	}

	/**
	 * Creates a /install/LOCK file to prevent re-installation (which would destroy the database)
	 */
	protected static function lockDatabase() {
		if (!is_file(__DIR__ . "/../install/LOCK"))
			touch(__DIR__ . "/../install/LOCK");
	}

	/**
	 * Load initial data
	 */
	protected static function loadInitialData() {
		$allPermissions = Permission::get();
		$allPermissionIds = [];
		foreach($allPermissions as $permission)
			$allPermissionIds[] = $permission->id;

		UserGroup::add("Root Admin", true, true);
		UserGroup::add("Committee", [
			"AccessAdminDashboard"
		]);
		UserGroup::add("Head Coach");
		UserGroup::add("Coach");
		UserGroup::add("Guest", [], true);
	}

	/**
	 * DATABASE MIGRATIONS
	 */
	protected static function migrateToVersion1() {
		$db = Database::getConnection();

		$db->query(<<<QUERY
			CREATE TABLE `autologins` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `user_id` bigint(20) unsigned NOT NULL,
			  `browser_parameters_hash` char(64) NOT NULL,
			  `key_hash` varchar(255) NOT NULL,
			  `epoch_created` bigint(20) unsigned NOT NULL,
			  `epoch_last_used` bigint(20) unsigned DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `fixtures` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `home_team_id` bigint(20) unsigned DEFAULT NULL,
			  `home_team_assigned_number` bigint(20) unsigned DEFAULT NULL,
			  `away_team_id` bigint(20) unsigned DEFAULT NULL,
			  `away_team_assigned_number` bigint(20) unsigned DEFAULT NULL,
			  `league_id` bigint(20) unsigned NOT NULL,
			  `play_by_date` date NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `league_sections` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `letter` varchar(4) NOT NULL,
			  `league_id` bigint(20) unsigned NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `league_letter` (`letter`,`league_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `leagues` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(32) NOT NULL,
			  `manager_id` bigint(20) unsigned NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `match_reports` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `match_id` bigint(20) unsigned NOT NULL,
			  `user_id` bigint(20) unsigned NOT NULL,
			  `epoch` bigint(20) unsigned NOT NULL,
			  `home_score` tinyint unsigned NOT NULL,
			  `away_score` tinyint unsigned NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `matches` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `date` date NOT NULL,
			  `home_team_id` bigint(20) unsigned NOT NULL,
			  `away_team_id` bigint(20) unsigned NOT NULL,
			  `home_score` tinyint unsigned NOT NULL,
			  `away_score` tinyint unsigned NOT NULL,
			  `winner_team_id` bigint(20) unsigned DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `matches_players` (
			  `match_id` bigint(20) unsigned NOT NULL,
			  `player_id` bigint(20) unsigned NOT NULL,
			  `team_id` bigint(20) unsigned NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `organizations` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(128) NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `name` (`name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `permissions` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `type` varchar(16) NOT NULL,
			  `name` varchar(32) NOT NULL,
			  `description` varchar(128) NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `name` (`name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `players` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `full_name` varchar(128) NOT NULL,
			  `team_id` bigint(20) unsigned NOT NULL,
			  `exempt` tinyint(1) unsigned NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `settings` (
			  `id` varchar(64) NOT NULL,
			  `value` longtext NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `teams` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `designation` varchar(32) NOT NULL,
			  `organization_id` bigint(20) unsigned NOT NULL,
			  `league_id` bigint(20) unsigned DEFAULT NULL,
			  `league_section_id` bigint(20) unsigned DEFAULT NULL,
			  `assigned_number` bigint(20) unsigned DEFAULT NULL,
			  `epoch_registered` bigint(20) unsigned NOT NULL,
			  `registrant_id` bigint(20) unsigned NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `organization_designation` (`organization_id`,`designation`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `user_groups` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(64) NOT NULL,
			  `special` tinyint(1) unsigned NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `name` (`name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `user_groups_permissions` (
			  `group_id` bigint(20) unsigned NOT NULL,
			  `permission_id` bigint(20) unsigned NOT NULL,
			  UNIQUE KEY `group_permission` (`group_id`,`permission_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `users` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `email` varchar(254) NOT NULL,
			  `password_hash` varchar(255) NOT NULL,
			  `full_name` varchar(64) NOT NULL,
			  `phone_number` varchar(32) NOT NULL,
			  `group_id` bigint(20) unsigned NOT NULL,
			  `organization_id` bigint(20) unsigned DEFAULT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `email` (`email`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;

			CREATE TABLE `valid_team_designations` (
			  `designation` varchar(32) NOT NULL,
			  PRIMARY KEY (`designation`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
QUERY
		);

		Permission::add("Admin", "AdminAccessDashboard", "Access the admin dashboard");
		Permission::add("Admin", "AdminUsers", "Administrate users");
		Permission::add("Admin", "AdminUserGroups", "Administrate user groups and their assigned permissions");
		Permission::add("Admin", "AdminOrganizations", "Administrate organizations");
		Permission::add("Admin", "AdminTeams", "Administrate teams");
		Permission::add("Admin", "AdminPlayers", "Administrate players");
		Permission::add("Admin", "AdminAllLeagues", "Administrate all leagues (users always have permission to administrate leagues they are assigned as the manager of)");
		Permission::add("Admin", "AdminAccessMaintenance", "Access the admin maintenance area and use the maintenance tools");
		Permission::add("Admin", "PerformDeletionOperations", "Permanently delete data which they have admin access to");

		Permission::add("Team", "RegisterTeamsForOwnOrganization", "Register teams for their own organization");
		Permission::add("Team", "RegisterTeamsForAnyOrganization", "Register teams for any organization");
	}
}