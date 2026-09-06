SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `admin_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` smallint(5) unsigned NOT NULL,
  `employee_number` varchar(50) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admins_user` (`user_id`),
  UNIQUE KEY `uq_admins_employee` (`employee_number`),
  KEY `idx_admins_role` (`role_id`),
  CONSTRAINT `fk_admins_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  CONSTRAINT `fk_admins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(80) NOT NULL COMMENT 'e.g. payment.verified, van.deleted',
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_audit_user` (`user_id`,`created_at`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_action` (`action`,`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_status_history` (
  `history_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(30) DEFAULT NULL,
  `to_status` varchar(30) NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `idx_bsh_booking` (`booking_id`,`created_at`),
  KEY `idx_bsh_user` (`changed_by`),
  CONSTRAINT `fk_bsh_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bsh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `booking_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reservation_id` bigint(20) unsigned NOT NULL,
  `schedule_id` bigint(20) unsigned DEFAULT NULL COMMENT 'NULL for ad-hoc whole-van charter',
  `route_id` bigint(20) unsigned NOT NULL,
  `van_id` bigint(20) unsigned NOT NULL,
  `driver_id` bigint(20) unsigned DEFAULT NULL,
  `leg` enum('outbound','return') NOT NULL DEFAULT 'outbound',
  `booking_mode` enum('seat','exclusive') NOT NULL DEFAULT 'seat',
  `pickup_location_id` bigint(20) unsigned NOT NULL,
  `dropoff_location_id` bigint(20) unsigned NOT NULL,
  `custom_pickup_address` varchar(255) DEFAULT NULL COMMENT 'Door-to-door detail within the barangay',
  `travel_date` date NOT NULL,
  `pickup_time` time NOT NULL,
  `seats_booked` smallint(5) unsigned NOT NULL DEFAULT 1,
  `fare_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','accepted','rejected','en_route','picked_up','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
  `accepted_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `trip_started_at` datetime DEFAULT NULL,
  `trip_ended_at` datetime DEFAULT NULL,
  `actual_distance_km` decimal(6,2) DEFAULT NULL,
  `actual_duration_minutes` smallint(5) unsigned DEFAULT NULL,
  `driver_notes` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `exclusive_slot_key` varchar(80) GENERATED ALWAYS AS (case when `booking_mode` = 'exclusive' and `status` not in ('cancelled','rejected','no_show') then concat(`van_id`,'|',`travel_date`,'|',`pickup_time`) else NULL end) STORED,
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `uq_bookings_exclusive_slot` (`exclusive_slot_key`),
  KEY `idx_bookings_reservation` (`reservation_id`),
  KEY `idx_bookings_schedule` (`schedule_id`),
  KEY `idx_bookings_driver` (`driver_id`,`status`),
  KEY `idx_bookings_van_date` (`van_id`,`travel_date`,`status`),
  KEY `idx_bookings_date_status` (`travel_date`,`status`),
  KEY `idx_bookings_route` (`route_id`),
  KEY `idx_bookings_pickup` (`pickup_location_id`),
  KEY `idx_bookings_dropoff` (`dropoff_location_id`),
  CONSTRAINT `fk_bookings_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bookings_dropoff` FOREIGN KEY (`dropoff_location_id`) REFERENCES `locations` (`location_id`),
  CONSTRAINT `fk_bookings_pickup` FOREIGN KEY (`pickup_location_id`) REFERENCES `locations` (`location_id`),
  CONSTRAINT `fk_bookings_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`),
  CONSTRAINT `fk_bookings_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `trip_schedules` (`schedule_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bookings_van` FOREIGN KEY (`van_id`) REFERENCES `vans` (`van_id`),
  CONSTRAINT `chk_bookings_seats` CHECK (`seats_booked` >= 1),
  CONSTRAINT `chk_bookings_trip_times` CHECK (`trip_ended_at` is null or `trip_started_at` is null or `trip_ended_at` >= `trip_started_at`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `conversation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL COMMENT 'Chat is scoped to a trip -- prevents unsolicited contact',
  `customer_user_id` bigint(20) unsigned NOT NULL,
  `driver_user_id` bigint(20) unsigned NOT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `last_message_preview` varchar(160) DEFAULT NULL,
  `customer_unread_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `driver_unread_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `status` enum('open','closed','archived') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`conversation_id`),
  UNIQUE KEY `uq_conversations_booking` (`booking_id`),
  KEY `idx_conv_customer` (`customer_user_id`,`last_message_at`),
  KEY `idx_conv_driver` (`driver_user_id`,`last_message_at`),
  CONSTRAINT `fk_conv_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conv_customer` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conv_driver` FOREIGN KEY (`driver_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `customer_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `province` varchar(100) NOT NULL DEFAULT 'Romblon',
  `birthdate` date DEFAULT NULL,
  `gender` enum('male','female','prefer_not_to_say') DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `valid_id_type` varchar(50) DEFAULT NULL,
  `valid_id_number` varchar(100) DEFAULT NULL,
  `valid_id_image` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `total_bookings` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Denormalized counter',
  `total_no_shows` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Used for deposit-risk policy',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `uq_customers_user` (`user_id`),
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `destinations`;
/*!50001 DROP VIEW IF EXISTS `destinations`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `destinations` AS SELECT
 1 AS `location_id`,
  1 AS `name`,
  1 AS `category`,
  1 AS `barangay`,
  1 AS `municipality`,
  1 AS `province`,
  1 AS `latitude`,
  1 AS `longitude`,
  1 AS `landmark`,
  1 AS `is_active`,
  1 AS `sort_order` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `driver_current_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `driver_current_location` (
  `driver_id` bigint(20) unsigned NOT NULL,
  `booking_id` bigint(20) unsigned DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `accuracy_meters` smallint(5) unsigned DEFAULT NULL,
  `heading_degrees` smallint(5) unsigned DEFAULT NULL,
  `speed_kph` decimal(5,2) DEFAULT NULL,
  `battery_level` tinyint(3) unsigned DEFAULT NULL,
  `is_moving` tinyint(1) NOT NULL DEFAULT 0,
  `recorded_at` datetime(3) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`driver_id`),
  KEY `idx_dcl_booking` (`booking_id`),
  KEY `idx_dcl_recorded` (`recorded_at`),
  CONSTRAINT `fk_dcl_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dcl_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `driver_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `driver_locations` (
  `location_log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `driver_id` bigint(20) unsigned NOT NULL,
  `booking_id` bigint(20) unsigned DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `accuracy_meters` smallint(5) unsigned DEFAULT NULL,
  `heading_degrees` smallint(5) unsigned DEFAULT NULL,
  `speed_kph` decimal(5,2) DEFAULT NULL,
  `recorded_at` datetime(3) NOT NULL,
  PRIMARY KEY (`location_log_id`),
  KEY `idx_dl_driver_time` (`driver_id`,`recorded_at`),
  KEY `idx_dl_booking_time` (`booking_id`,`recorded_at`),
  CONSTRAINT `fk_dl_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_dl_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drivers` (
  `driver_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `license_expiry` date NOT NULL,
  `license_image` varchar(255) DEFAULT NULL,
  `years_experience` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `address` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `availability_status` enum('available','on_trip','on_break','offline') NOT NULL DEFAULT 'offline',
  `assigned_van_id` bigint(20) unsigned DEFAULT NULL,
  `rating_average` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(10) unsigned NOT NULL DEFAULT 0,
  `total_trips` int(10) unsigned NOT NULL DEFAULT 0,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Admin must approve before driver can accept bookings',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`driver_id`),
  UNIQUE KEY `uq_drivers_user` (`user_id`),
  UNIQUE KEY `uq_drivers_license` (`license_number`),
  KEY `idx_drivers_availability` (`availability_status`,`is_approved`),
  KEY `idx_drivers_van` (`assigned_van_id`),
  KEY `idx_drivers_approved_by` (`approved_by`),
  CONSTRAINT `fk_drivers_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_drivers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_drivers_van` FOREIGN KEY (`assigned_van_id`) REFERENCES `vans` (`van_id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback` (
  `feedback_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'NULL allows anonymous submissions',
  `category` enum('bug','suggestion','complaint','compliment','other') NOT NULL DEFAULT 'other',
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `status` enum('new','in_review','resolved','closed') NOT NULL DEFAULT 'new',
  `handled_by` bigint(20) unsigned DEFAULT NULL,
  `response` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`feedback_id`),
  KEY `idx_feedback_status` (`status`,`created_at`),
  KEY `idx_feedback_user` (`user_id`),
  KEY `idx_feedback_handler` (`handled_by`),
  CONSTRAINT `fk_feedback_handler` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locations` (
  `location_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `location_type` enum('pickup','destination','both') NOT NULL DEFAULT 'both',
  `category` enum('port','terminal','town_proper','barangay','resort','landmark','airport','other') NOT NULL DEFAULT 'other',
  `barangay` varchar(100) DEFAULT NULL,
  `municipality` enum('Magdiwang','San Fernando','Cajidiocan','Other') NOT NULL DEFAULT 'Other',
  `province` varchar(100) NOT NULL DEFAULT 'Romblon',
  `latitude` decimal(10,8) NOT NULL COMMENT 'WGS84; Sibuyan approx 12.3-12.6 N',
  `longitude` decimal(11,8) NOT NULL COMMENT 'WGS84; Sibuyan approx 122.4-122.7 E',
  `landmark` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`location_id`),
  UNIQUE KEY `uq_locations_name_muni` (`name`,`municipality`),
  KEY `idx_locations_type_active` (`location_type`,`is_active`),
  KEY `idx_locations_municipality` (`municipality`),
  CONSTRAINT `chk_locations_lat` CHECK (`latitude` between -90 and 90),
  CONSTRAINT `chk_locations_lng` CHECK (`longitude` between -180 and 180)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `attempt_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(150) NOT NULL COMMENT 'Submitted email or phone',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `was_successful` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attempt_id`),
  KEY `idx_attempts_identifier` (`identifier`,`attempted_at`),
  KEY `idx_attempts_ip` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `message_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `sender_user_id` bigint(20) unsigned NOT NULL,
  `message_type` enum('text','image','location','system') NOT NULL DEFAULT 'text',
  `body` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'For location-type messages',
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp(3) NOT NULL DEFAULT current_timestamp(3),
  PRIMARY KEY (`message_id`),
  KEY `idx_messages_conversation` (`conversation_id`,`created_at`),
  KEY `idx_messages_unread` (`conversation_id`,`is_read`),
  KEY `idx_messages_sender` (`sender_user_id`),
  CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `channel` enum('in_app','email','sms','push') NOT NULL DEFAULT 'in_app',
  `type` varchar(60) NOT NULL COMMENT 'e.g. booking.confirmed, payment.verified',
  `title` varchar(150) NOT NULL,
  `body` text NOT NULL,
  `related_type` varchar(50) DEFAULT NULL COMMENT 'Polymorphic target, e.g. reservation',
  `related_id` bigint(20) unsigned DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `delivery_status` enum('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `error_message` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_notif_user_unread` (`user_id`,`is_read`,`created_at`),
  KEY `idx_notif_queue` (`delivery_status`,`channel`,`attempts`),
  KEY `idx_notif_related` (`related_type`,`related_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `reset_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'SHA-256 of the emailed token; raw token never stored',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `uq_pwreset_token` (`token_hash`),
  KEY `idx_pwreset_user` (`user_id`),
  KEY `idx_pwreset_expires` (`expires_at`),
  CONSTRAINT `fk_pwreset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_methods` (
  `method_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `method_code` varchar(30) NOT NULL COMMENT 'gcash | face_to_face',
  `method_name` varchar(100) NOT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `requires_proof` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Screenshot upload required',
  `account_name` varchar(150) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `qr_image_path` varchar(255) DEFAULT NULL COMMENT 'Merchant GCash QR shown to customer',
  `instructions` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`method_id`),
  UNIQUE KEY `uq_paymethods_code` (`method_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `payment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reservation_id` bigint(20) unsigned NOT NULL,
  `method_id` smallint(5) unsigned NOT NULL,
  `payment_type` enum('deposit','balance','full','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL COMMENT 'GCash transaction reference',
  `proof_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected','refunded') NOT NULL DEFAULT 'pending',
  `receipt_number` varchar(30) DEFAULT NULL COMMENT 'Issued only on verification',
  `paid_at` datetime DEFAULT NULL COMMENT 'Customer-declared payment datetime',
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL COMMENT 'Driver/staff who took cash (face-to-face)',
  `notes` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `uq_payments_receipt` (`receipt_number`),
  UNIQUE KEY `uq_payments_reference` (`method_id`,`reference_number`),
  KEY `idx_payments_reservation` (`reservation_id`,`status`),
  KEY `idx_payments_status` (`status`,`created_at`),
  KEY `idx_payments_verifier` (`verified_by`),
  KEY `idx_payments_receiver` (`received_by`),
  CONSTRAINT `fk_payments_method` FOREIGN KEY (`method_id`) REFERENCES `payment_methods` (`method_id`),
  CONSTRAINT `fk_payments_receiver` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payments_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`),
  CONSTRAINT `fk_payments_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `chk_payments_amount` CHECK (`amount` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `permission_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `permission_code` varchar(100) NOT NULL COMMENT 'e.g. bookings.approve, payments.verify',
  `module` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `uq_permissions_code` (`permission_code`),
  KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pickup_locations`;
/*!50001 DROP VIEW IF EXISTS `pickup_locations`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `pickup_locations` AS SELECT
 1 AS `location_id`,
  1 AS `name`,
  1 AS `category`,
  1 AS `barangay`,
  1 AS `municipality`,
  1 AS `province`,
  1 AS `latitude`,
  1 AS `longitude`,
  1 AS `landmark`,
  1 AS `is_active`,
  1 AS `sort_order` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `qr_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qr_bookings` (
  `qr_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'SHA-256 of the QR payload; raw token only in the image',
  `qr_image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','used','expired','revoked') NOT NULL DEFAULT 'active',
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `scanned_at` datetime DEFAULT NULL,
  `scanned_by` bigint(20) unsigned DEFAULT NULL COMMENT 'Driver user_id who verified boarding',
  `scan_count` smallint(5) unsigned NOT NULL DEFAULT 0 COMMENT '>1 indicates a reuse attempt',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`qr_id`),
  UNIQUE KEY `uq_qr_token` (`token_hash`),
  UNIQUE KEY `uq_qr_booking` (`booking_id`),
  KEY `idx_qr_status` (`status`,`expires_at`),
  KEY `idx_qr_scanner` (`scanned_by`),
  CONSTRAINT `fk_qr_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qr_scanner` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ratings` (
  `rating_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `driver_id` bigint(20) unsigned DEFAULT NULL,
  `van_id` bigint(20) unsigned DEFAULT NULL,
  `overall_rating` tinyint(3) unsigned NOT NULL,
  `punctuality_rating` tinyint(3) unsigned DEFAULT NULL,
  `cleanliness_rating` tinyint(3) unsigned DEFAULT NULL,
  `driving_rating` tinyint(3) unsigned DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Admin can hide abusive reviews',
  `hidden_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`rating_id`),
  UNIQUE KEY `uq_ratings_booking` (`booking_id`) COMMENT 'One rating per completed trip',
  KEY `idx_ratings_driver` (`driver_id`,`is_visible`),
  KEY `idx_ratings_van` (`van_id`),
  KEY `idx_ratings_customer` (`customer_id`),
  CONSTRAINT `fk_ratings_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ratings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ratings_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ratings_van` FOREIGN KEY (`van_id`) REFERENCES `vans` (`van_id`) ON DELETE SET NULL,
  CONSTRAINT `chk_ratings_overall` CHECK (`overall_rating` between 1 and 5)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `report_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `report_type` varchar(60) NOT NULL COMMENT 'e.g. revenue_summary, driver_performance',
  `title` varchar(150) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_format` enum('pdf','csv','xlsx') DEFAULT NULL,
  `generated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `idx_reports_type` (`report_type`,`created_at`),
  KEY `idx_reports_user` (`generated_by`),
  CONSTRAINT `fk_reports_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservations` (
  `reservation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference_code` varchar(25) NOT NULL COMMENT 'Human-readable, e.g. SIT-20260803-A7K2',
  `customer_id` bigint(20) unsigned NOT NULL,
  `booking_type` enum('seat','whole_van','rental') NOT NULL DEFAULT 'seat',
  `passenger_count` smallint(5) unsigned NOT NULL DEFAULT 1,
  `is_round_trip` tinyint(1) NOT NULL DEFAULT 0,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deposit_percentage` decimal(5,2) NOT NULL DEFAULT 30.00 COMMENT 'Snapshot of policy at booking time',
  `deposit_required` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(10,2) GENERATED ALWAYS AS (`total_amount` - `amount_paid`) STORED,
  `status` enum('pending','confirmed','in_progress','completed','cancelled','no_show','expired') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','partially_paid','paid','cancelled','refunded','completed') NOT NULL DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `hold_expires_at` datetime DEFAULT NULL COMMENT 'Unpaid holds auto-expire; frees seats back',
  `confirmed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`reservation_id`),
  UNIQUE KEY `uq_reservations_ref` (`reference_code`),
  KEY `idx_reservations_customer` (`customer_id`,`status`),
  KEY `idx_reservations_status` (`status`,`payment_status`),
  KEY `idx_reservations_hold` (`hold_expires_at`),
  KEY `idx_reservations_cancelby` (`cancelled_by`),
  CONSTRAINT `fk_reservations_cancelby` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reservations_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  CONSTRAINT `chk_reservations_amounts` CHECK (`amount_paid` >= 0 and `total_amount` >= 0),
  CONSTRAINT `chk_reservations_pax` CHECK (`passenger_count` >= 1)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `role_id` smallint(5) unsigned NOT NULL,
  `permission_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `idx_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `role_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `role_code` varchar(50) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'System roles cannot be deleted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uq_roles_code` (`role_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `routes` (
  `route_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `route_code` varchar(30) NOT NULL COMMENT 'e.g. SF-CAJ',
  `route_name` varchar(150) NOT NULL,
  `origin_location_id` bigint(20) unsigned NOT NULL,
  `destination_location_id` bigint(20) unsigned NOT NULL,
  `distance_km` decimal(6,2) NOT NULL DEFAULT 0.00,
  `estimated_duration_minutes` smallint(5) unsigned NOT NULL DEFAULT 0,
  `base_fare` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fare_per_passenger` decimal(10,2) NOT NULL DEFAULT 0.00,
  `route_polyline` mediumtext DEFAULT NULL COMMENT 'Encoded polyline / GeoJSON for Leaflet rendering',
  `road_condition` enum('paved','partially_paved','rough') NOT NULL DEFAULT 'paved',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`route_id`),
  UNIQUE KEY `uq_routes_code` (`route_code`),
  UNIQUE KEY `uq_routes_endpoints` (`origin_location_id`,`destination_location_id`),
  KEY `idx_routes_destination` (`destination_location_id`),
  KEY `idx_routes_active` (`is_active`),
  CONSTRAINT `fk_routes_destination` FOREIGN KEY (`destination_location_id`) REFERENCES `locations` (`location_id`),
  CONSTRAINT `fk_routes_origin` FOREIGN KEY (`origin_location_id`) REFERENCES `locations` (`location_id`),
  CONSTRAINT `chk_routes_distinct` CHECK (`origin_location_id` <> `destination_location_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `payload` mediumtext DEFAULT NULL,
  `last_activity` int(10) unsigned NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_expires` (`expires_at`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `setting_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `data_type` enum('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
  `group_name` varchar(50) NOT NULL DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Safe to expose to frontend',
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`),
  KEY `idx_settings_group` (`group_name`),
  KEY `idx_settings_user` (`updated_by`),
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trip_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trip_schedules` (
  `schedule_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `route_id` bigint(20) unsigned NOT NULL,
  `van_id` bigint(20) unsigned NOT NULL,
  `driver_id` bigint(20) unsigned DEFAULT NULL,
  `departure_date` date NOT NULL,
  `departure_time` time NOT NULL,
  `estimated_arrival` time DEFAULT NULL,
  `total_seats` tinyint(3) unsigned NOT NULL,
  `available_seats` tinyint(3) unsigned NOT NULL,
  `fare_per_seat` decimal(10,2) NOT NULL,
  `booking_mode` enum('seat','exclusive') NOT NULL DEFAULT 'seat' COMMENT 'exclusive = whole-van hire/rental',
  `status` enum('scheduled','boarding','departed','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`schedule_id`),
  UNIQUE KEY `uq_schedule_van_slot` (`van_id`,`departure_date`,`departure_time`),
  KEY `idx_schedule_lookup` (`route_id`,`departure_date`,`status`),
  KEY `idx_schedule_driver` (`driver_id`,`departure_date`),
  KEY `idx_schedule_calendar` (`departure_date`,`status`),
  KEY `idx_schedule_creator` (`created_by`),
  CONSTRAINT `fk_schedule_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_schedule_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_schedule_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`),
  CONSTRAINT `fk_schedule_van` FOREIGN KEY (`van_id`) REFERENCES `vans` (`van_id`),
  CONSTRAINT `chk_schedule_seats` CHECK (`available_seats` <= `total_seats`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL COMMENT 'Public-facing ID; never expose user_id in URLs',
  `role` enum('customer','driver','admin') NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL COMMENT 'E.164 PH format: +639XXXXXXXXX',
  `password_hash` varchar(255) NOT NULL COMMENT 'password_hash() PASSWORD_DEFAULT (bcrypt/argon2id)',
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('pending','active','suspended','deactivated') NOT NULL DEFAULT 'pending',
  `email_verified_at` datetime DEFAULT NULL,
  `phone_verified_at` datetime DEFAULT NULL,
  `failed_login_attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL COMMENT 'Brute-force lockout expiry',
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT 'IPv6-safe length',
  `remember_token` varchar(255) DEFAULT NULL COMMENT 'Store HASH of token, never the raw value',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL COMMENT 'Soft delete: preserves booking/payment history',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_uuid` (`uuid`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_phone` (`phone`),
  KEY `idx_users_role_status` (`role`,`status`),
  KEY `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `van_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `van_images` (
  `image_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `van_id` bigint(20) unsigned NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL COMMENT 'Compressed variant generated on upload',
  `caption` varchar(150) DEFAULT NULL COMMENT 'Doubles as alt text (accessibility)',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `file_size_kb` int(10) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `idx_vanimages_van` (`van_id`,`sort_order`),
  KEY `idx_vanimages_uploader` (`uploaded_by`),
  CONSTRAINT `fk_vanimages_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_vanimages_van` FOREIGN KEY (`van_id`) REFERENCES `vans` (`van_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vans` (
  `van_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plate_number` varchar(20) NOT NULL,
  `body_number` varchar(20) DEFAULT NULL,
  `make` varchar(50) NOT NULL COMMENT 'e.g. Toyota, Nissan',
  `model` varchar(50) NOT NULL COMMENT 'e.g. HiAce Commuter',
  `year_model` smallint(5) unsigned DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `van_type` enum('standard','premium','tourist') NOT NULL DEFAULT 'standard',
  `seating_capacity` tinyint(3) unsigned NOT NULL,
  `luggage_capacity` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `has_aircon` tinyint(1) NOT NULL DEFAULT 1,
  `has_wifi` tinyint(1) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `base_fare` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Flat fare component (PHP)',
  `fare_per_km` decimal(10,2) NOT NULL DEFAULT 0.00,
  `whole_van_day_rate` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Rental / exclusive hire rate',
  `status` enum('active','maintenance','inactive','retired') NOT NULL DEFAULT 'active',
  `or_cr_number` varchar(50) DEFAULT NULL,
  `registration_expiry` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `last_maintenance_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`van_id`),
  UNIQUE KEY `uq_vans_plate` (`plate_number`),
  KEY `idx_vans_status_type` (`status`,`van_type`),
  KEY `idx_vans_deleted` (`deleted_at`),
  CONSTRAINT `chk_vans_capacity` CHECK (`seating_capacity` between 1 and 30)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vw_active_trips`;
/*!50001 DROP VIEW IF EXISTS `vw_active_trips`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_active_trips` AS SELECT
 1 AS `booking_id`,
  1 AS `reservation_id`,
  1 AS `reference_code`,
  1 AS `booking_status`,
  1 AS `travel_date`,
  1 AS `pickup_time`,
  1 AS `trip_started_at`,
  1 AS `van_id`,
  1 AS `plate_number`,
  1 AS `driver_id`,
  1 AS `driver_name`,
  1 AS `driver_phone`,
  1 AS `customer_name`,
  1 AS `customer_phone`,
  1 AS `pickup_name`,
  1 AS `pickup_lat`,
  1 AS `pickup_lng`,
  1 AS `dropoff_name`,
  1 AS `dropoff_lat`,
  1 AS `dropoff_lng`,
  1 AS `current_lat`,
  1 AS `current_lng`,
  1 AS `speed_kph`,
  1 AS `heading_degrees`,
  1 AS `location_updated_at`,
  1 AS `distance_km`,
  1 AS `estimated_duration_minutes` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vw_available_schedules`;
/*!50001 DROP VIEW IF EXISTS `vw_available_schedules`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_available_schedules` AS SELECT
 1 AS `schedule_id`,
  1 AS `departure_date`,
  1 AS `departure_time`,
  1 AS `estimated_arrival`,
  1 AS `available_seats`,
  1 AS `total_seats`,
  1 AS `fare_per_seat`,
  1 AS `booking_mode`,
  1 AS `route_id`,
  1 AS `route_code`,
  1 AS `route_name`,
  1 AS `distance_km`,
  1 AS `estimated_duration_minutes`,
  1 AS `origin_id`,
  1 AS `origin_name`,
  1 AS `destination_id`,
  1 AS `destination_name`,
  1 AS `van_id`,
  1 AS `plate_number`,
  1 AS `make`,
  1 AS `model`,
  1 AS `van_type`,
  1 AS `has_aircon`,
  1 AS `has_wifi`,
  1 AS `driver_id`,
  1 AS `driver_name`,
  1 AS `rating_average`,
  1 AS `rating_count`,
  1 AS `primary_image` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vw_daily_revenue`;
/*!50001 DROP VIEW IF EXISTS `vw_daily_revenue`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_daily_revenue` AS SELECT
 1 AS `revenue_date`,
  1 AS `method_code`,
  1 AS `transaction_count`,
  1 AS `net_amount` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vw_reservation_summary`;
/*!50001 DROP VIEW IF EXISTS `vw_reservation_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_reservation_summary` AS SELECT
 1 AS `reservation_id`,
  1 AS `reference_code`,
  1 AS `customer_id`,
  1 AS `customer_name`,
  1 AS `customer_email`,
  1 AS `customer_phone`,
  1 AS `booking_type`,
  1 AS `passenger_count`,
  1 AS `is_round_trip`,
  1 AS `total_amount`,
  1 AS `deposit_percentage`,
  1 AS `deposit_required`,
  1 AS `amount_paid`,
  1 AS `balance_due`,
  1 AS `status`,
  1 AS `payment_status`,
  1 AS `created_at`,
  1 AS `first_travel_date`,
  1 AS `leg_count`,
  1 AS `verified_payments_total`,
  1 AS `pending_payment_count` */;
SET character_set_client = @saved_cs_client;
DROP TABLE IF EXISTS `vw_user_accounts`;
/*!50001 DROP VIEW IF EXISTS `vw_user_accounts`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vw_user_accounts` AS SELECT
 1 AS `user_id`,
  1 AS `uuid`,
  1 AS `role`,
  1 AS `full_name`,
  1 AS `email`,
  1 AS `phone`,
  1 AS `status`,
  1 AS `profile_picture`,
  1 AS `last_login_at`,
  1 AS `customer_id`,
  1 AS `driver_id`,
  1 AS `availability_status`,
  1 AS `driver_is_approved`,
  1 AS `admin_id`,
  1 AS `admin_role_id`,
  1 AS `created_at` */;
SET character_set_client = @saved_cs_client;
/*!50001 DROP VIEW IF EXISTS `destinations`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `destinations` AS select `locations`.`location_id` AS `location_id`,`locations`.`name` AS `name`,`locations`.`category` AS `category`,`locations`.`barangay` AS `barangay`,`locations`.`municipality` AS `municipality`,`locations`.`province` AS `province`,`locations`.`latitude` AS `latitude`,`locations`.`longitude` AS `longitude`,`locations`.`landmark` AS `landmark`,`locations`.`is_active` AS `is_active`,`locations`.`sort_order` AS `sort_order` from `locations` where `locations`.`location_type` in ('destination','both') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `pickup_locations`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `pickup_locations` AS select `locations`.`location_id` AS `location_id`,`locations`.`name` AS `name`,`locations`.`category` AS `category`,`locations`.`barangay` AS `barangay`,`locations`.`municipality` AS `municipality`,`locations`.`province` AS `province`,`locations`.`latitude` AS `latitude`,`locations`.`longitude` AS `longitude`,`locations`.`landmark` AS `landmark`,`locations`.`is_active` AS `is_active`,`locations`.`sort_order` AS `sort_order` from `locations` where `locations`.`location_type` in ('pickup','both') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_active_trips`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_active_trips` AS select `b`.`booking_id` AS `booking_id`,`b`.`reservation_id` AS `reservation_id`,`rs`.`reference_code` AS `reference_code`,`b`.`status` AS `booking_status`,`b`.`travel_date` AS `travel_date`,`b`.`pickup_time` AS `pickup_time`,`b`.`trip_started_at` AS `trip_started_at`,`v`.`van_id` AS `van_id`,`v`.`plate_number` AS `plate_number`,`d`.`driver_id` AS `driver_id`,concat(`du`.`first_name`,' ',`du`.`last_name`) AS `driver_name`,`du`.`phone` AS `driver_phone`,concat(`cu`.`first_name`,' ',`cu`.`last_name`) AS `customer_name`,`cu`.`phone` AS `customer_phone`,`pick`.`name` AS `pickup_name`,`pick`.`latitude` AS `pickup_lat`,`pick`.`longitude` AS `pickup_lng`,`drop_loc`.`name` AS `dropoff_name`,`drop_loc`.`latitude` AS `dropoff_lat`,`drop_loc`.`longitude` AS `dropoff_lng`,`dcl`.`latitude` AS `current_lat`,`dcl`.`longitude` AS `current_lng`,`dcl`.`speed_kph` AS `speed_kph`,`dcl`.`heading_degrees` AS `heading_degrees`,`dcl`.`recorded_at` AS `location_updated_at`,`r`.`distance_km` AS `distance_km`,`r`.`estimated_duration_minutes` AS `estimated_duration_minutes` from ((((((((((`bookings` `b` join `reservations` `rs` on(`rs`.`reservation_id` = `b`.`reservation_id`)) join `customers` `c` on(`c`.`customer_id` = `rs`.`customer_id`)) join `users` `cu` on(`cu`.`user_id` = `c`.`user_id`)) join `vans` `v` on(`v`.`van_id` = `b`.`van_id`)) join `routes` `r` on(`r`.`route_id` = `b`.`route_id`)) join `locations` `pick` on(`pick`.`location_id` = `b`.`pickup_location_id`)) join `locations` `drop_loc` on(`drop_loc`.`location_id` = `b`.`dropoff_location_id`)) left join `drivers` `d` on(`d`.`driver_id` = `b`.`driver_id`)) left join `users` `du` on(`du`.`user_id` = `d`.`user_id`)) left join `driver_current_location` `dcl` on(`dcl`.`driver_id` = `d`.`driver_id`)) where `b`.`status` in ('accepted','en_route','picked_up') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_available_schedules`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_available_schedules` AS select `ts`.`schedule_id` AS `schedule_id`,`ts`.`departure_date` AS `departure_date`,`ts`.`departure_time` AS `departure_time`,`ts`.`estimated_arrival` AS `estimated_arrival`,`ts`.`available_seats` AS `available_seats`,`ts`.`total_seats` AS `total_seats`,`ts`.`fare_per_seat` AS `fare_per_seat`,`ts`.`booking_mode` AS `booking_mode`,`r`.`route_id` AS `route_id`,`r`.`route_code` AS `route_code`,`r`.`route_name` AS `route_name`,`r`.`distance_km` AS `distance_km`,`r`.`estimated_duration_minutes` AS `estimated_duration_minutes`,`origin`.`location_id` AS `origin_id`,`origin`.`name` AS `origin_name`,`dest`.`location_id` AS `destination_id`,`dest`.`name` AS `destination_name`,`v`.`van_id` AS `van_id`,`v`.`plate_number` AS `plate_number`,`v`.`make` AS `make`,`v`.`model` AS `model`,`v`.`van_type` AS `van_type`,`v`.`has_aircon` AS `has_aircon`,`v`.`has_wifi` AS `has_wifi`,`d`.`driver_id` AS `driver_id`,concat(`du`.`first_name`,' ',`du`.`last_name`) AS `driver_name`,`d`.`rating_average` AS `rating_average`,`d`.`rating_count` AS `rating_count`,(select `vi`.`image_path` from `van_images` `vi` where `vi`.`van_id` = `v`.`van_id` and `vi`.`is_primary` = 1 limit 1) AS `primary_image` from ((((((`trip_schedules` `ts` join `routes` `r` on(`r`.`route_id` = `ts`.`route_id` and `r`.`is_active` = 1)) join `locations` `origin` on(`origin`.`location_id` = `r`.`origin_location_id`)) join `locations` `dest` on(`dest`.`location_id` = `r`.`destination_location_id`)) join `vans` `v` on(`v`.`van_id` = `ts`.`van_id` and `v`.`status` = 'active' and `v`.`deleted_at` is null)) left join `drivers` `d` on(`d`.`driver_id` = `ts`.`driver_id`)) left join `users` `du` on(`du`.`user_id` = `d`.`user_id`)) where `ts`.`status` = 'scheduled' and `ts`.`available_seats` > 0 and timestamp(`ts`.`departure_date`,`ts`.`departure_time`) > current_timestamp() */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_daily_revenue`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_daily_revenue` AS select cast(`p`.`verified_at` as date) AS `revenue_date`,`pm`.`method_code` AS `method_code`,count(0) AS `transaction_count`,sum(case when `p`.`payment_type` = 'refund' then -`p`.`amount` else `p`.`amount` end) AS `net_amount` from (`payments` `p` join `payment_methods` `pm` on(`pm`.`method_id` = `p`.`method_id`)) where `p`.`status` = 'verified' and `p`.`verified_at` is not null group by cast(`p`.`verified_at` as date),`pm`.`method_code` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_reservation_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_reservation_summary` AS select `rs`.`reservation_id` AS `reservation_id`,`rs`.`reference_code` AS `reference_code`,`rs`.`customer_id` AS `customer_id`,concat(`cu`.`first_name`,' ',`cu`.`last_name`) AS `customer_name`,`cu`.`email` AS `customer_email`,`cu`.`phone` AS `customer_phone`,`rs`.`booking_type` AS `booking_type`,`rs`.`passenger_count` AS `passenger_count`,`rs`.`is_round_trip` AS `is_round_trip`,`rs`.`total_amount` AS `total_amount`,`rs`.`deposit_percentage` AS `deposit_percentage`,`rs`.`deposit_required` AS `deposit_required`,`rs`.`amount_paid` AS `amount_paid`,`rs`.`balance_due` AS `balance_due`,`rs`.`status` AS `status`,`rs`.`payment_status` AS `payment_status`,`rs`.`created_at` AS `created_at`,(select min(`b`.`travel_date`) from `bookings` `b` where `b`.`reservation_id` = `rs`.`reservation_id`) AS `first_travel_date`,(select count(0) from `bookings` `b` where `b`.`reservation_id` = `rs`.`reservation_id`) AS `leg_count`,(select coalesce(sum(`p`.`amount`),0) from `payments` `p` where `p`.`reservation_id` = `rs`.`reservation_id` and `p`.`status` = 'verified') AS `verified_payments_total`,(select count(0) from `payments` `p` where `p`.`reservation_id` = `rs`.`reservation_id` and `p`.`status` = 'pending') AS `pending_payment_count` from ((`reservations` `rs` join `customers` `c` on(`c`.`customer_id` = `rs`.`customer_id`)) join `users` `cu` on(`cu`.`user_id` = `c`.`user_id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vw_user_accounts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_user_accounts` AS select `u`.`user_id` AS `user_id`,`u`.`uuid` AS `uuid`,`u`.`role` AS `role`,concat(`u`.`first_name`,' ',`u`.`last_name`) AS `full_name`,`u`.`email` AS `email`,`u`.`phone` AS `phone`,`u`.`status` AS `status`,`u`.`profile_picture` AS `profile_picture`,`u`.`last_login_at` AS `last_login_at`,`c`.`customer_id` AS `customer_id`,`d`.`driver_id` AS `driver_id`,`d`.`availability_status` AS `availability_status`,`d`.`is_approved` AS `driver_is_approved`,`a`.`admin_id` AS `admin_id`,`a`.`role_id` AS `admin_role_id`,`u`.`created_at` AS `created_at` from (((`users` `u` left join `customers` `c` on(`c`.`user_id` = `u`.`user_id`)) left join `drivers` `d` on(`d`.`user_id` = `u`.`user_id`)) left join `admins` `a` on(`a`.`user_id` = `u`.`user_id`)) where `u`.`deleted_at` is null */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

SET FOREIGN_KEY_CHECKS = 1;