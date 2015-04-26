# Database Schema

* **Type:** InnoDB
* **Encoding:** UTF-8 Unicode (utf8)
* **Collation:** utf8_general_ci

# Tables
******************************************************************************************************************

database_version
-------------------------
Holds the current database version to allow the system to work out whether its database requires upgrading.

* **database_version** - bigint(20) unsigned not null - Database version integer

* **PRIMARY** key on **database_version**

```sql
CREATE TABLE `database_version` (
  `database_version` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`database_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

permissions
-------------------------
Permissions available on the app for performing various actions and requests

* **id** - bigint(20) unsigned not null auto_increment - Primary key identifier
* **type** - varchar(32) not null - String describing the type of permission (e.g. sysadmin)
* **name** - varchar(64) not null - String used to identify the permission when checking for it, and when granting it in the sysadmin panel
* **description** - varchar(255) not null - String describing what the permission is for

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

user_groups_permissions
-------------------------
Holds the map of user groups to permissions and the status they have been given regarding them (granted, not granted, never granted)

* **group_id** - bigint(20) unsigned not null - User group
* **permission_id** - bigint(20) unsigned not null - Permission
* **granted** - tinyint(4) not null - Grant status of permission, constants are defined in the UserGroupPermission model

* **UNIQUE** key on **group_id, permission_id**

```sql
CREATE TABLE `user_groups_permissions` (
  `group_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `granted` tinyint(4) NOT NULL,
  UNIQUE KEY `group_permission` (`group_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```