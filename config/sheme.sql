# SQL Manager 2010 for MySQL 4.5.0.9
# ---------------------------------------
# Host     : localhost
# Port     : 3306
# Database : local_feed


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

SET FOREIGN_KEY_CHECKS=0;

DROP DATABASE IF EXISTS `local_feed`;

CREATE DATABASE `local_feed`
    CHARACTER SET 'utf8'
    COLLATE 'utf8_general_ci';

USE `local_feed`;

#
# Structure for the `media_storage_categories` table : 
#

CREATE TABLE `media_storage_categories` (
  `code` varchar(50) NOT NULL,
  `hidden` varchar(3) NOT NULL DEFAULT 'no',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure for the `media_storage_file_extras` table : 
#

CREATE TABLE `media_storage_file_extras` (
  `file_id` char(36) NOT NULL,
  `width` smallint(6) DEFAULT NULL,
  `height` smallint(6) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`file_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure for the `media_storage_files` table : 
#

CREATE TABLE `media_storage_files` (
  `id` char(36) NOT NULL,
  `location_code` varchar(50) NOT NULL,
  `category_code` varchar(50) DEFAULT NULL,
  `vfolder_id` varchar(36) NOT NULL,
  `location_path` varchar(100) NOT NULL,
  `file_name` varchar(100) NOT NULL,
  `file_extension` varchar(10) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_mime` varchar(100) NOT NULL,
  `name` varchar(75) NOT NULL,
  `private` varchar(3) NOT NULL DEFAULT 'no',
  `status` varchar(40) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqbyfilename` (`location_code`,`location_path`,`file_name`),
  UNIQUE KEY `category_code` (`category_code`,`vfolder_id`,`name`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure for the `media_storage_reserved_size` table : 
#

CREATE TABLE `media_storage_reserved_size` (
  `location_code` varchar(50) NOT NULL,
  `size` bigint(20) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`location_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Structure for the `media_storage_vfolders` table : 
#

CREATE TABLE `media_storage_vfolders` (
  `id` char(36) NOT NULL,
  `category_code` varchar(50) NOT NULL,
  `name` varchar(75) NOT NULL,
  `parent_id` varchar(36) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_code` (`category_code`,`name`,`parent_id`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
# Data for the `media_storage_categories` table  (LIMIT 0,500)
#

INSERT INTO `media_storage_categories` (`code`, `hidden`, `created_at`, `updated_at`) VALUES 
  ('media','no','2013-11-28 09:16:19',NULL);
COMMIT;



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;