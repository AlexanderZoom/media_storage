# SQL Manager 2011 for MySQL 5.1.0.2
# ---------------------------------------
# Host     : localhost
# Port     : 3306
# Database : local_feed


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

SET FOREIGN_KEY_CHECKS=0;

CREATE DATABASE `local_feed`
    CHARACTER SET 'utf8'
    COLLATE 'utf8_general_ci';

USE `local_feed`;

#
# Structure for the `media_storage_categories` table : 
#

CREATE TABLE `media_storage_categories` (
  `code` VARCHAR(50) COLLATE utf8_general_ci NOT NULL,
  `hidden` VARCHAR(3) COLLATE utf8_general_ci NOT NULL DEFAULT 'no',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`code`)
)ENGINE=InnoDB
AVG_ROW_LENGTH=8192 CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
COMMENT=''
;

#
# Structure for the `media_storage_file_extras` table : 
#

CREATE TABLE `media_storage_file_extras` (
  `file_id` CHAR(36) COLLATE utf8_general_ci NOT NULL,
  `width` SMALLINT(6) DEFAULT NULL,
  `height` SMALLINT(6) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`file_id`),
  KEY `created_at` (`created_at`)
)ENGINE=InnoDB
CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
COMMENT=''
;

#
# Structure for the `media_storage_files` table : 
#

CREATE TABLE `media_storage_files` (
  `id` CHAR(36) COLLATE utf8_general_ci NOT NULL,
  `location_code` VARCHAR(50) COLLATE utf8_general_ci NOT NULL,
  `category_code` VARCHAR(50) COLLATE utf8_general_ci DEFAULT NULL,
  `vfolder_id` VARCHAR(36) COLLATE utf8_general_ci NOT NULL,
  `location_path` VARCHAR(100) COLLATE utf8_general_ci NOT NULL,
  `file_name` VARCHAR(100) COLLATE utf8_general_ci NOT NULL,
  `file_extension` VARCHAR(10) COLLATE utf8_general_ci NOT NULL,
  `file_size` INTEGER(11) NOT NULL,
  `file_mime` VARCHAR(100) COLLATE utf8_general_ci NOT NULL,
  `name` VARCHAR(75) COLLATE utf8_general_ci NOT NULL,
  `type` VARCHAR(20) COLLATE utf8_general_ci NOT NULL DEFAULT 'normal',
  `private` VARCHAR(3) COLLATE utf8_general_ci NOT NULL DEFAULT 'no',
  `status` VARCHAR(40) COLLATE utf8_general_ci NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqbyfilename` (`location_code`, `location_path`, `file_name`),
  UNIQUE KEY `category_code` (`category_code`, `vfolder_id`, `name`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`)
)ENGINE=InnoDB
AVG_ROW_LENGTH=5461 CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
COMMENT=''
;

#
# Structure for the `media_storage_reserved_size` table : 
#

CREATE TABLE `media_storage_reserved_size` (
  `location_code` VARCHAR(50) COLLATE utf8_general_ci NOT NULL,
  `size` BIGINT(20) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`location_code`)
)ENGINE=InnoDB
AVG_ROW_LENGTH=8192 CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
COMMENT=''
;

#
# Structure for the `media_storage_vfolders` table : 
#

CREATE TABLE `media_storage_vfolders` (
  `id` CHAR(36) COLLATE utf8_general_ci NOT NULL,
  `category_code` VARCHAR(50) COLLATE utf8_general_ci NOT NULL,
  `name` VARCHAR(75) COLLATE utf8_general_ci NOT NULL,
  `parent_id` VARCHAR(36) COLLATE utf8_general_ci DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_code` (`category_code`, `name`, `parent_id`),
  KEY `created_at` (`created_at`),
  KEY `updated_at` (`updated_at`)
)ENGINE=InnoDB
CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'
COMMENT=''
;

#
# Data for the `media_storage_categories` table  (LIMIT -497,500)
#

INSERT INTO `media_storage_categories` (`code`, `hidden`, `created_at`, `updated_at`) VALUES 
  ('files','no','2014-05-31 00:00:00',NULL),
  ('media','no','2013-11-28 09:16:19',NULL);
COMMIT;

#
# Data for the `media_storage_files` table  (LIMIT -496,500)
#

INSERT INTO `media_storage_files` (`id`, `location_code`, `category_code`, `vfolder_id`, `location_path`, `file_name`, `file_extension`, `file_size`, `file_mime`, `name`, `type`, `private`, `status`, `created_at`, `updated_at`) VALUES 
  ('08e1184c-bcfc-443f-afb2-cfff22f69a98','main','media','','public/0/0','99elwu4da.gif','gif',4433369,'image/gif','7.gif','normal','no','ok','2014-06-01 20:11:24',NULL),
  ('4e5817d5-ec1f-4cd8-922d-a328fb610943','private','media','','private/0/0','c1sow9zgw.jpg','jpg',170962,'image/jpeg','7B8Fny8Dlqc.jpg','normal','yes','ok','2014-06-01 20:11:43',NULL),
  ('96c4aadd-d5b7-487a-9e28-bb92939047ad','main','media','','public/0/0','93vp5aqu7.jpg','jpg',357367,'image/jpeg','w_a5f8907f.jpg','normal','no','ok','2014-05-31 18:42:24',NULL);
COMMIT;

#
# Data for the `media_storage_reserved_size` table  (LIMIT -497,500)
#

INSERT INTO `media_storage_reserved_size` (`location_code`, `size`, `created_at`, `updated_at`) VALUES 
  ('main',8866738,'2013-11-28 09:17:20','2014-06-01 20:11:24'),
  ('private',0,'2013-11-28 09:19:55','2014-06-01 20:11:43');
COMMIT;



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;