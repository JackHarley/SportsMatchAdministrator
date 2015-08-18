# Database Schema

* **Type:** InnoDB
* **Encoding:** UTF-8 Unicode (utf8)
* **Collation:** utf8_general_ci

# Tables
******************************************************************************************************************

Tables should be in alphabetical order: special characters, 0-9, A-Z

autologins
-------------------------
Holds all autologins ('remember me's)

* **id** - Primary key identifier
* **user_id** - User
* **browser_parameters_hash** - SHA256 hash of the browser parameters, i.e. some uniquely identifying information about the browser, which if change should invalidate the autologin
* **key_hash** - password_hash() of a randomly generated key that will be stored in the browser
* **epoch_created** - Epoch of when the autologin was created
* **epoch_last_used** - Epoch of when the autologin was last successfully used

```sql
CREATE TABLE `autologins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `browser_parameters_hash` char(64) NOT NULL,
  `key_hash` varchar(255) NOT NULL,
  `epoch_created` bigint(20) unsigned NOT NULL,
  `epoch_last_used` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

database_version
-------------------------
Holds the current database version to allow the system to work out whether its database requires upgrading.

* **database_version** - Database version integer

* **PRIMARY** key on **database_version**

```sql
CREATE TABLE `database_version` (
  `database_version` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`database_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

league_sections
-------------------------
Sections of a league, for example if the 1st Division has too many teams for all of them to reasonably play each other, the 1st Division could be split into two sections: A and B, the top teams from each section would then play in a semi-final (and subsequent final) at the end of the season.

* **id** - Primary key identifier
* **letter** - Section identification letter, e.g. A, B, C, D. Sections in the same league may not have the same letter.
* **league_id** - League

```sql
CREATE TABLE `league_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `letter` varchar(4) NOT NULL,
  `league_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `league_letter` (`letter`,`league_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

leagues
-------------------------
Leagues, e.g. 1st Division, 2nd Division, 3rd Division

* **id** - Primary key identifier
* **name** - League name, e.g. 1st Division
* **manager_id** - User who is in charge of the division

```sql
CREATE TABLE `leagues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `manager_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

match_reports
-------------------------
Reports from a match (each team manager will enter a report, they will be cross-checked by the system)

* **id** - Primary key identifier
* **match_id** - Match the report is for
* **user_id** - User entering the report
* **epoch** - UNIX epoch timestamp for when the report was entered
* **home_score** - Home team score (goals, points, etc.)
* **away_score** - Away team score (goals, points, etc.)

```sql
CREATE TABLE `match_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `match_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `epoch` bigint(20) unsigned NOT NULL,
  `home_score` tinyint unsigned NOT NULL,
  `away_score` tinyint unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

matches
-------------------------
Matches (two teams playing against each other)

* **id** - Primary key identifier
* **date** - Date (YYYY-MM-DD) match is played
* **home_team_id** - Home team
* **away_team_id** - Away team
* **home_score** - Home team score (goals, points, etc.) (field will not be filled until after match reports are cross-checked)
* **away_score** - Away team score (goals, points, etc.) (field will not be filled until after match reports are cross-checked)
* **winner_team_id** - If applicable, the winning team (field will not be filled until after match reports are cross-checked)

```sql
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
```

matches_players
-------------------------
Maps matches to the players who played for either of the participating teams (will be filled for each team when the manager files a report)

* **match_id** - Match
* **player_id** - Player
* **team_id** - Team the player played for (not necessarily the team the player is attached to)

```sql
CREATE TABLE `matches_players` (
  `match_id` bigint(20) unsigned NOT NULL,
  `player_id` bigint(20) unsigned NOT NULL,
  `team_id` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

organizations
-------------------------
Organizations (i.e. schools, clubs)

* **id** - Primary key identifier
* **name** - Organization name

```sql
CREATE TABLE `organizations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

permissions
-------------------------
Permissions available on the app for performing various actions and requests

* **id** - Primary key identifier
* **type** - String describing the type of permission (e.g. sysadmin)
* **name** - String used to identify the permission when checking for it, and when granting it in the sysadmin panel
* **description** - String describing what the permission is for

* **PRIMARY** key on **id**
* **UNIQUE** key on **name**

```sql
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(16) NOT NULL,
  `name` varchar(32) NOT NULL,
  `description` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

players
-------------------------
**Registered** Players (from the teams)

* **id** - Primary key identifier
* **full_name** - Player full name
* **team_id** - Team the player is attached to as primary
* **exempt** - If true, the player can play in any league without generating alerts

```sql
CREATE TABLE `players` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(128) NOT NULL,
  `team_id` bigint(20) unsigned NOT NULL,
  `exempt` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

teams
-------------------------
Teams

* **id** - Primary key identifier
* **designation** - Team designation, e.g. Senior 1, Senior 2, Junior 1, Junior 2, Minor A
* **organization_id** - Organization the team belongs to
* **league_section_id** - League section the team is assigned to or null if unassigned

```sql
CREATE TABLE `teams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `designation` varchar(32) NOT NULL,
  `organization_id` bigint(20) unsigned NOT NULL,
  `league_section_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_designation` (`organization_id`,`designation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

user_groups
-------------------------
Holds all user groups

* **id** - Primary key identifier
* **name** - Group name
* **special** - Prevents deletion of the group (i.e. system group)

```sql
CREATE TABLE `user_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `special` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

user_groups_permissions
-------------------------
Holds the map of user groups to permissions and the status they have been given regarding them (granted, not granted, never granted)

* **group_id** - User group
* **permission_id** - Permission
* **granted** - Grant status of permission, constants are defined in the UserGroupPermission model

* **UNIQUE** key on **group_id, permission_id**

```sql
CREATE TABLE `user_groups_permissions` (
  `group_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `granted` tinyint(4) NOT NULL,
  UNIQUE KEY `group_permission` (`group_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

users
-------------------------
Holds all users

* **id** - Primary key identifier
* **email** - Email
* **password_hash** - Password hash, generated by PHP password_* library
* **full_name** - Full name
* **phone_number** - Telephone number
* **group_id** - Assigned user group
* **organization_id** - Organization

```sql
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
```