-- =====================================================================
-- SITRASS - FRESH INSTALL SQL (InfinityFree)
-- ISANG PASTE LANG SA phpMyAdmin.
-- Paano:
--   1. phpMyAdmin > piliin ang if0_42853048_sitrass_db sa kaliwa
--   2. i-drop lahat ng existing tables (kung mayroon)
--   3. SQL tab > i-paste ang BUONG file na ito > Go
--   4. Tapos na! Admin: admin@sitrass.local / Sitrass@2026
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- SITRASS: Transportation Reservation and Rental Management System
-- File        : 01_schema.sql
-- Purpose     : Core database schema (DDL)
-- Engine      : InnoDB / utf8mb4_unicode_ci
-- Target      : MySQL 8.0+ / MariaDB 10.4+ (XAMPP-compatible)
-- Author      : SITRASS Engineering
-- =====================================================================
-- EXECUTION ORDER: 01_schema.sql -> 02_views.sql -> 03_seed.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- (InfinityFree: tinanggal ang CREATE DATABASE - libreng hosting, isang database lang ang allowed,
-- yung if0_XXXXXX_sitrass_db na ginawa sa dashboard. Piliin muna ito sa phpMyAdmin bago mag-import.)

-- (InfinityFree: tinanggal ang USE - naka-select na ang database sa phpMyAdmin)

-- =====================================================================
-- SECTION 1: IDENTITY, ACCESS CONTROL & SECURITY
-- =====================================================================

-- ---------------------------------------------------------------------
-- roles: admin sub-roles for RBAC. Customer/driver are handled by
-- users.role; this table governs granular ADMIN permissions only.
-- ---------------------------------------------------------------------
CREATE TABLE `roles` (
  `role_id`      SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_code`    VARCHAR(50)  NOT NULL,
  `role_name`    VARCHAR(100) NOT NULL,
  `description`  VARCHAR(255) DEFAULT NULL,
  `is_system`    TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'System roles cannot be deleted',
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uq_roles_code` (`role_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `permission_id`   SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `permission_code` VARCHAR(100) NOT NULL COMMENT 'e.g. bookings.approve, payments.verify',
  `module`          VARCHAR(50)  NOT NULL,
  `description`     VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `uq_permissions_code` (`permission_code`),
  KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
  `role_id`       SMALLINT UNSIGNED NOT NULL,
  `permission_id` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  KEY `idx_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_role`       FOREIGN KEY (`role_id`)       REFERENCES `roles` (`role_id`)             ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- users: single source of truth for authentication.
-- Profile data lives in customers/drivers/admins (1:1 extensions).
-- Rationale: one login pipeline, one password policy, one lockout rule.
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `user_id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid`                  CHAR(36)     NOT NULL COMMENT 'Public-facing ID; never expose user_id in URLs',
  `role`                  ENUM('customer','driver','admin') NOT NULL,
  `first_name`            VARCHAR(80)  NOT NULL,
  `middle_name`           VARCHAR(80)  DEFAULT NULL,
  `last_name`             VARCHAR(80)  NOT NULL,
  `email`                 VARCHAR(150) NOT NULL,
  `phone`                 VARCHAR(20)  NOT NULL COMMENT 'E.164 PH format: +639XXXXXXXXX',
  `password_hash`         VARCHAR(255) NOT NULL COMMENT 'password_hash() PASSWORD_DEFAULT (bcrypt/argon2id)',
  `profile_picture`       VARCHAR(255) DEFAULT NULL,
  `status`                ENUM('pending','active','suspended','deactivated') NOT NULL DEFAULT 'pending',
  `email_verified_at`     DATETIME     DEFAULT NULL,
  `phone_verified_at`     DATETIME     DEFAULT NULL,
  `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until`          DATETIME     DEFAULT NULL COMMENT 'Brute-force lockout expiry',
  `last_login_at`         DATETIME     DEFAULT NULL,
  `last_login_ip`         VARCHAR(45)  DEFAULT NULL COMMENT 'IPv6-safe length',
  `remember_token`        VARCHAR(255) DEFAULT NULL COMMENT 'Store HASH of token, never the raw value',
  `created_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`            DATETIME     DEFAULT NULL COMMENT 'Soft delete: preserves booking/payment history',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_uuid`  (`uuid`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_phone` (`phone`),
  KEY `idx_users_role_status` (`role`, `status`),
  KEY `idx_users_deleted`     (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customers` (
  `customer_id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`                 BIGINT UNSIGNED NOT NULL,
  `address`                 VARCHAR(255) DEFAULT NULL,
  `barangay`                VARCHAR(100) DEFAULT NULL,
  `municipality`            VARCHAR(100) DEFAULT NULL,
  `province`                VARCHAR(100) NOT NULL DEFAULT 'Romblon',
  `birthdate`               DATE         DEFAULT NULL,
  `gender`                  ENUM('male','female','prefer_not_to_say') DEFAULT NULL,
  `emergency_contact_name`  VARCHAR(150) DEFAULT NULL,
  `emergency_contact_phone` VARCHAR(20)  DEFAULT NULL,
  `valid_id_type`           VARCHAR(50)  DEFAULT NULL,
  `valid_id_number`         VARCHAR(100) DEFAULT NULL,
  `valid_id_image`          VARCHAR(255) DEFAULT NULL,
  `is_verified`             TINYINT(1)   NOT NULL DEFAULT 0,
  `total_bookings`          INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Denormalized counter',
  `total_no_shows`          INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Used for deposit-risk policy',
  `created_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `uq_customers_user` (`user_id`),
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `drivers` (
  `driver_id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`                 BIGINT UNSIGNED NOT NULL,
  `license_number`          VARCHAR(50)  NOT NULL,
  `license_expiry`          DATE         NOT NULL,
  `license_image`           VARCHAR(255) DEFAULT NULL,
  `years_experience`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `address`                 VARCHAR(255) DEFAULT NULL,
  `emergency_contact_name`  VARCHAR(150) DEFAULT NULL,
  `emergency_contact_phone` VARCHAR(20)  DEFAULT NULL,
  `availability_status`     ENUM('available','on_trip','on_break','offline') NOT NULL DEFAULT 'offline',
  `assigned_van_id`         BIGINT UNSIGNED DEFAULT NULL,
  `rating_average`          DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `rating_count`            INT UNSIGNED NOT NULL DEFAULT 0,
  `total_trips`             INT UNSIGNED NOT NULL DEFAULT 0,
  `is_approved`             TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Admin must approve before driver can accept bookings',
  `approved_by`             BIGINT UNSIGNED DEFAULT NULL,
  `approved_at`             DATETIME     DEFAULT NULL,
  `created_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`driver_id`),
  UNIQUE KEY `uq_drivers_user`    (`user_id`),
  UNIQUE KEY `uq_drivers_license` (`license_number`),
  KEY `idx_drivers_availability`  (`availability_status`, `is_approved`),
  KEY `idx_drivers_van`           (`assigned_van_id`),
  KEY `idx_drivers_approved_by`   (`approved_by`),
  CONSTRAINT `fk_drivers_user`        FOREIGN KEY (`user_id`)     REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_drivers_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admins` (
  `admin_id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          BIGINT UNSIGNED NOT NULL,
  `role_id`          SMALLINT UNSIGNED NOT NULL,
  `employee_number`  VARCHAR(50)  DEFAULT NULL,
  `position`         VARCHAR(100) DEFAULT NULL,
  `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admins_user`     (`user_id`),
  UNIQUE KEY `uq_admins_employee` (`employee_number`),
  KEY `idx_admins_role` (`role_id`),
  CONSTRAINT `fk_admins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_admins_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- sessions: DB-backed sessions so admins can force-logout a user and
-- so we can list active devices. Replaces default PHP file sessions.
-- ---------------------------------------------------------------------
CREATE TABLE `sessions` (
  `session_id`    VARCHAR(128) NOT NULL,
  `user_id`       BIGINT UNSIGNED DEFAULT NULL,
  `ip_address`    VARCHAR(45)  DEFAULT NULL,
  `user_agent`    VARCHAR(255) DEFAULT NULL,
  `payload`       MEDIUMTEXT   DEFAULT NULL,
  `last_activity` INT UNSIGNED NOT NULL,
  `expires_at`    DATETIME     NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `idx_sessions_user`    (`user_id`),
  KEY `idx_sessions_expires` (`expires_at`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `reset_id`   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `token_hash` CHAR(64)     NOT NULL COMMENT 'SHA-256 of the emailed token; raw token never stored',
  `expires_at` DATETIME     NOT NULL,
  `used_at`    DATETIME     DEFAULT NULL,
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `uq_pwreset_token` (`token_hash`),
  KEY `idx_pwreset_user`    (`user_id`),
  KEY `idx_pwreset_expires` (`expires_at`),
  CONSTRAINT `fk_pwreset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate-limiting source of truth. Logged even for non-existent emails so
-- attackers cannot use timing/response differences to enumerate accounts.
CREATE TABLE `login_attempts` (
  `attempt_id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier`     VARCHAR(150) NOT NULL COMMENT 'Submitted email or phone',
  `ip_address`     VARCHAR(45)  NOT NULL,
  `user_agent`     VARCHAR(255) DEFAULT NULL,
  `was_successful` TINYINT(1)   NOT NULL DEFAULT 0,
  `attempted_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attempt_id`),
  KEY `idx_attempts_identifier` (`identifier`, `attempted_at`),
  KEY `idx_attempts_ip`         (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECTION 2: FLEET
-- =====================================================================

CREATE TABLE `vans` (
  `van_id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plate_number`         VARCHAR(20)  NOT NULL,
  `body_number`          VARCHAR(20)  DEFAULT NULL,
  `make`                 VARCHAR(50)  NOT NULL COMMENT 'e.g. Toyota, Nissan',
  `model`                VARCHAR(50)  NOT NULL COMMENT 'e.g. HiAce Commuter',
  `year_model`           SMALLINT UNSIGNED DEFAULT NULL,
  `color`                VARCHAR(30)  DEFAULT NULL,
  `van_type`             ENUM('standard','premium','tourist') NOT NULL DEFAULT 'standard',
  `seating_capacity`     TINYINT UNSIGNED NOT NULL,
  `luggage_capacity`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `has_aircon`           TINYINT(1)   NOT NULL DEFAULT 1,
  `has_wifi`             TINYINT(1)   NOT NULL DEFAULT 0,
  `description`          TEXT         DEFAULT NULL,
  `base_fare`            DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Flat fare component (PHP)',
  `fare_per_km`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `whole_van_day_rate`   DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Rental / exclusive hire rate',
  `status`               ENUM('pending','active','maintenance','inactive','retired') NOT NULL DEFAULT 'active',
  `driver_id`            BIGINT UNSIGNED DEFAULT NULL COMMENT 'Owning driver (My Vans)',
  `or_cr_number`         VARCHAR(50)  DEFAULT NULL,
  `registration_expiry`  DATE         DEFAULT NULL,
  `insurance_expiry`     DATE         DEFAULT NULL,
  `last_maintenance_at`  DATE         DEFAULT NULL,
  `created_at`           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`           DATETIME     DEFAULT NULL,
  PRIMARY KEY (`van_id`),
  UNIQUE KEY `uq_vans_plate` (`plate_number`),
  KEY `idx_vans_status_type` (`status`, `van_type`),
  KEY `idx_vans_driver`      (`driver_id`),
  KEY `idx_vans_deleted`     (`deleted_at`),
  CONSTRAINT `chk_vans_capacity` CHECK (`seating_capacity` BETWEEN 1 AND 30)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deferred FK: drivers.assigned_van_id -> vans (vans is created after drivers)
ALTER TABLE `drivers`
  ADD CONSTRAINT `fk_drivers_van` FOREIGN KEY (`assigned_van_id`)
  REFERENCES `vans` (`van_id`) ON DELETE SET NULL ON UPDATE RESTRICT;

CREATE TABLE `van_images` (
  `image_id`       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `van_id`         BIGINT UNSIGNED NOT NULL,
  `image_path`     VARCHAR(255) NOT NULL,
  `thumbnail_path` VARCHAR(255) DEFAULT NULL COMMENT 'Compressed variant generated on upload',
  `caption`        VARCHAR(150) DEFAULT NULL COMMENT 'Doubles as alt text (accessibility)',
  `is_primary`     TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `file_size_kb`   INT UNSIGNED DEFAULT NULL,
  `uploaded_by`    BIGINT UNSIGNED DEFAULT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `idx_vanimages_van`      (`van_id`, `sort_order`),
  KEY `idx_vanimages_uploader` (`uploaded_by`),
  CONSTRAINT `fk_vanimages_van`      FOREIGN KEY (`van_id`)      REFERENCES `vans` (`van_id`)   ON DELETE CASCADE  ON UPDATE RESTRICT,
  CONSTRAINT `fk_vanimages_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECTION 3: GEOGRAPHY & ROUTES
-- =====================================================================

-- ---------------------------------------------------------------------
-- locations: single canonical table for pickup points AND destinations.
-- The spec lists PickupLocations and Destinations separately, but in
-- Sibuyan the same barangay/port serves both directions. Duplicating
-- them would mean two coordinate records to keep in sync. Backwards
-- compatibility with the spec is preserved via SQL VIEWS (02_views.sql).
-- ---------------------------------------------------------------------
CREATE TABLE `locations` (
  `location_id`   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(150) NOT NULL,
  `location_type` ENUM('pickup','destination','both') NOT NULL DEFAULT 'both',
  `category`      ENUM('port','terminal','town_proper','barangay','resort','landmark','airport','other') NOT NULL DEFAULT 'other',
  `barangay`      VARCHAR(100) DEFAULT NULL,
  `municipality`  ENUM('Magdiwang','San Fernando','Cajidiocan','Other') NOT NULL DEFAULT 'Other',
  `province`      VARCHAR(100) NOT NULL DEFAULT 'Romblon',
  `latitude`      DECIMAL(10,8) NOT NULL COMMENT 'WGS84; Sibuyan approx 12.3-12.6 N',
  `longitude`     DECIMAL(11,8) NOT NULL COMMENT 'WGS84; Sibuyan approx 122.4-122.7 E',
  `landmark`      VARCHAR(255) DEFAULT NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_id`),
  UNIQUE KEY `uq_locations_name_muni` (`name`, `municipality`),
  KEY `idx_locations_type_active` (`location_type`, `is_active`),
  KEY `idx_locations_municipality` (`municipality`),
  CONSTRAINT `chk_locations_lat` CHECK (`latitude`  BETWEEN -90  AND 90),
  CONSTRAINT `chk_locations_lng` CHECK (`longitude` BETWEEN -180 AND 180)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `routes` (
  `route_id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `route_code`                 VARCHAR(30)  NOT NULL COMMENT 'e.g. SF-CAJ',
  `route_name`                 VARCHAR(150) NOT NULL,
  `origin_location_id`         BIGINT UNSIGNED NOT NULL,
  `destination_location_id`    BIGINT UNSIGNED NOT NULL,
  `distance_km`                DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `estimated_duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `base_fare`                  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fare_per_passenger`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `route_polyline`             MEDIUMTEXT   DEFAULT NULL COMMENT 'Encoded polyline / GeoJSON for Leaflet rendering',
  `road_condition`             ENUM('paved','partially_paved','rough') NOT NULL DEFAULT 'paved',
  `is_active`                  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`                 TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                 TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`route_id`),
  UNIQUE KEY `uq_routes_code`      (`route_code`),
  UNIQUE KEY `uq_routes_endpoints` (`origin_location_id`, `destination_location_id`),
  KEY `idx_routes_destination` (`destination_location_id`),
  KEY `idx_routes_active`      (`is_active`),
  CONSTRAINT `fk_routes_origin`      FOREIGN KEY (`origin_location_id`)      REFERENCES `locations` (`location_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_routes_destination` FOREIGN KEY (`destination_location_id`) REFERENCES `locations` (`location_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_routes_distinct`   CHECK (`origin_location_id` <> `destination_location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- trip_schedules: a concrete departure (van + driver + route + datetime).
-- This is what "Select Schedule" binds to and what the booking calendar
-- renders. available_seats is decremented inside the booking transaction.
-- ---------------------------------------------------------------------
CREATE TABLE `trip_schedules` (
  `schedule_id`       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `route_id`          BIGINT UNSIGNED NOT NULL,
  `van_id`            BIGINT UNSIGNED NOT NULL,
  `driver_id`         BIGINT UNSIGNED DEFAULT NULL,
  `departure_date`    DATE         NOT NULL,
  `departure_time`    TIME         NOT NULL,
  `estimated_arrival` TIME         DEFAULT NULL,
  `total_seats`       TINYINT UNSIGNED NOT NULL,
  `available_seats`   TINYINT UNSIGNED NOT NULL,
  `fare_per_seat`     DECIMAL(10,2) NOT NULL,
  `booking_mode`      ENUM('seat','exclusive') NOT NULL DEFAULT 'seat' COMMENT 'exclusive = whole-van hire/rental',
  `status`            ENUM('scheduled','boarding','departed','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `cancellation_reason` VARCHAR(255) DEFAULT NULL,
  `created_by`        BIGINT UNSIGNED DEFAULT NULL,
  `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`),
  -- Hard guarantee: one van cannot have two departures at the same moment.
  UNIQUE KEY `uq_schedule_van_slot` (`van_id`, `departure_date`, `departure_time`),
  KEY `idx_schedule_lookup`   (`route_id`, `departure_date`, `status`),
  KEY `idx_schedule_driver`   (`driver_id`, `departure_date`),
  KEY `idx_schedule_calendar` (`departure_date`, `status`),
  KEY `idx_schedule_creator`  (`created_by`),
  CONSTRAINT `fk_schedule_route`   FOREIGN KEY (`route_id`)   REFERENCES `routes` (`route_id`)     ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_schedule_van`     FOREIGN KEY (`van_id`)     REFERENCES `vans` (`van_id`)         ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_schedule_driver`  FOREIGN KEY (`driver_id`)  REFERENCES `drivers` (`driver_id`)   ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_schedule_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)       ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `chk_schedule_seats`  CHECK (`available_seats` <= `total_seats`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECTION 4: RESERVATIONS & BOOKINGS
-- =====================================================================

-- ---------------------------------------------------------------------
-- reservations: the customer's ORDER (money, status, reference code).
-- bookings: the TRIP LEGS under that order (van, driver, date, seats).
-- A round trip = 1 reservation + 2 bookings. Payments attach to the
-- reservation so a round trip is paid once, not twice.
-- ---------------------------------------------------------------------
CREATE TABLE `reservations` (
  `reservation_id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_code`      VARCHAR(25)  NOT NULL COMMENT 'Human-readable, e.g. SIT-20260803-A7K2',
  `customer_id`         BIGINT UNSIGNED NOT NULL,
  `booking_type`        ENUM('seat','whole_van','rental') NOT NULL DEFAULT 'seat',
  `passenger_count`     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `is_round_trip`       TINYINT(1)   NOT NULL DEFAULT 0,
  `subtotal`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `deposit_percentage`  DECIMAL(5,2) NOT NULL DEFAULT 30.00 COMMENT 'Snapshot of policy at booking time',
  `deposit_required`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `balance_due`         DECIMAL(10,2) GENERATED ALWAYS AS (`total_amount` - `amount_paid`) STORED,
  `status`              ENUM('pending','confirmed','in_progress','completed','cancelled','no_show','expired') NOT NULL DEFAULT 'pending',
  `payment_status`      ENUM('pending','partially_paid','paid','cancelled','refunded','completed') NOT NULL DEFAULT 'pending',
  `special_requests`    TEXT         DEFAULT NULL,
  `hold_expires_at`     DATETIME     DEFAULT NULL COMMENT 'Unpaid holds auto-expire; frees seats back',
  `confirmed_at`        DATETIME     DEFAULT NULL,
  `completed_at`        DATETIME     DEFAULT NULL,
  `cancelled_at`        DATETIME     DEFAULT NULL,
  `cancelled_by`        BIGINT UNSIGNED DEFAULT NULL,
  `cancellation_reason` VARCHAR(255) DEFAULT NULL,
  `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reservation_id`),
  UNIQUE KEY `uq_reservations_ref` (`reference_code`),
  KEY `idx_reservations_customer`  (`customer_id`, `status`),
  KEY `idx_reservations_status`    (`status`, `payment_status`),
  KEY `idx_reservations_hold`      (`hold_expires_at`),
  KEY `idx_reservations_cancelby`  (`cancelled_by`),
  CONSTRAINT `fk_reservations_customer`  FOREIGN KEY (`customer_id`)  REFERENCES `customers` (`customer_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_reservations_cancelby`  FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`user_id`)        ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `chk_reservations_amounts`  CHECK (`amount_paid` >= 0 AND `total_amount` >= 0),
  CONSTRAINT `chk_reservations_pax`      CHECK (`passenger_count` >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bookings` (
  `booking_id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id`         BIGINT UNSIGNED NOT NULL,
  `schedule_id`            BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL for ad-hoc whole-van charter',
  `route_id`               BIGINT UNSIGNED NOT NULL,
  `van_id`                 BIGINT UNSIGNED NOT NULL,
  `driver_id`              BIGINT UNSIGNED DEFAULT NULL,
  `leg`                    ENUM('outbound','return') NOT NULL DEFAULT 'outbound',
  `booking_mode`           ENUM('seat','exclusive') NOT NULL DEFAULT 'seat',
  `pickup_location_id`     BIGINT UNSIGNED NOT NULL,
  `dropoff_location_id`    BIGINT UNSIGNED NOT NULL,
  `custom_pickup_address`  VARCHAR(255) DEFAULT NULL COMMENT 'Door-to-door detail within the barangay',
  `travel_date`            DATE         NOT NULL,
  `pickup_time`            TIME         NOT NULL,
  `seats_booked`           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `fare_amount`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`                 ENUM('pending','accepted','rejected','en_route','picked_up','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
  `accepted_at`            DATETIME     DEFAULT NULL,
  `rejected_at`            DATETIME     DEFAULT NULL,
  `rejection_reason`       VARCHAR(255) DEFAULT NULL,
  `trip_started_at`        DATETIME     DEFAULT NULL,
  `trip_ended_at`          DATETIME     DEFAULT NULL,
  `actual_distance_km`     DECIMAL(6,2) DEFAULT NULL,
  `actual_duration_minutes` SMALLINT UNSIGNED DEFAULT NULL,
  `driver_notes`           VARCHAR(500) DEFAULT NULL,
  `created_at`             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- --------------------------------------------------------------
  -- DOUBLE-BOOKING GUARD (database-level, not just application-level)
  -- For exclusive/whole-van hires the van must be uniquely held for
  -- that date+time. For shared seat bookings the key is NULL, and
  -- MySQL permits unlimited NULLs in a UNIQUE index -- so shared
  -- rides remain unconstrained while charters cannot collide, even
  -- under concurrent requests or a buggy controller.
  -- --------------------------------------------------------------
  `exclusive_slot_key` VARCHAR(80)
      GENERATED ALWAYS AS (
        CASE WHEN `booking_mode` = 'exclusive'
                  AND `status` NOT IN ('cancelled','rejected','no_show')
             THEN CONCAT(`van_id`, '|', `travel_date`, '|', `pickup_time`)
             ELSE NULL END
      ) STORED,
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `uq_bookings_exclusive_slot` (`exclusive_slot_key`),
  KEY `idx_bookings_reservation` (`reservation_id`),
  KEY `idx_bookings_schedule`    (`schedule_id`),
  KEY `idx_bookings_driver`      (`driver_id`, `status`),
  KEY `idx_bookings_van_date`    (`van_id`, `travel_date`, `status`),
  KEY `idx_bookings_date_status` (`travel_date`, `status`),
  KEY `idx_bookings_route`       (`route_id`),
  KEY `idx_bookings_pickup`      (`pickup_location_id`),
  KEY `idx_bookings_dropoff`     (`dropoff_location_id`),
  CONSTRAINT `fk_bookings_reservation` FOREIGN KEY (`reservation_id`)      REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE  ON UPDATE RESTRICT,
  CONSTRAINT `fk_bookings_schedule`    FOREIGN KEY (`schedule_id`)         REFERENCES `trip_schedules` (`schedule_id`)  ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_bookings_route`       FOREIGN KEY (`route_id`)            REFERENCES `routes` (`route_id`)            ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_bookings_van`         FOREIGN KEY (`van_id`)              REFERENCES `vans` (`van_id`)                ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_bookings_driver`      FOREIGN KEY (`driver_id`)           REFERENCES `drivers` (`driver_id`)          ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_bookings_pickup`      FOREIGN KEY (`pickup_location_id`)  REFERENCES `locations` (`location_id`)      ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_bookings_dropoff`     FOREIGN KEY (`dropoff_location_id`) REFERENCES `locations` (`location_id`)      ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_bookings_seats`      CHECK (`seats_booked` >= 1),
  CONSTRAINT `chk_bookings_trip_times` CHECK (`trip_ended_at` IS NULL OR `trip_started_at` IS NULL OR `trip_ended_at` >= `trip_started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immutable trail of every state transition (who changed what, when, why).
CREATE TABLE `booking_status_history` (
  `history_id`   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id`   BIGINT UNSIGNED NOT NULL,
  `from_status`  VARCHAR(30)  DEFAULT NULL,
  `to_status`    VARCHAR(30)  NOT NULL,
  `changed_by`   BIGINT UNSIGNED DEFAULT NULL,
  `reason`       VARCHAR(255) DEFAULT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `idx_bsh_booking` (`booking_id`, `created_at`),
  KEY `idx_bsh_user`    (`changed_by`),
  CONSTRAINT `fk_bsh_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE  ON UPDATE RESTRICT,
  CONSTRAINT `fk_bsh_user`    FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`)       ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECTION 5: PAYMENTS
-- =====================================================================

CREATE TABLE `payment_methods` (
  `method_id`       SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `method_code`     VARCHAR(30)  NOT NULL COMMENT 'gcash | face_to_face',
  `method_name`     VARCHAR(100) NOT NULL,
  `is_online`       TINYINT(1)   NOT NULL DEFAULT 0,
  `requires_proof`  TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Screenshot upload required',
  `account_name`    VARCHAR(150) DEFAULT NULL,
  `account_number`  VARCHAR(50)  DEFAULT NULL,
  `qr_image_path`   VARCHAR(255) DEFAULT NULL COMMENT 'Merchant GCash QR shown to customer',
  `instructions`    TEXT         DEFAULT NULL,
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`method_id`),
  UNIQUE KEY `uq_paymethods_code` (`method_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `payment_id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id`    BIGINT UNSIGNED DEFAULT NULL,
  `rental_id`         BIGINT UNSIGNED DEFAULT NULL,
  `method_id`         SMALLINT UNSIGNED NOT NULL,
  `payment_type`      ENUM('deposit','balance','full','refund') NOT NULL,
  `amount`            DECIMAL(10,2) NOT NULL,
  `reference_number`  VARCHAR(100) DEFAULT NULL COMMENT 'GCash transaction reference',
  `proof_image`       VARCHAR(255) DEFAULT NULL,
  `status`            ENUM('pending','verified','rejected','refunded') NOT NULL DEFAULT 'pending',
  `receipt_number`    VARCHAR(30)  DEFAULT NULL COMMENT 'Issued only on verification',
  `paid_at`           DATETIME     DEFAULT NULL COMMENT 'Customer-declared payment datetime',
  `verified_by`       BIGINT UNSIGNED DEFAULT NULL,
  `verified_at`       DATETIME     DEFAULT NULL,
  `rejection_reason`  VARCHAR(255) DEFAULT NULL,
  `received_by`       BIGINT UNSIGNED DEFAULT NULL COMMENT 'Driver/staff who took cash (face-to-face)',
  `notes`             VARCHAR(500) DEFAULT NULL,
  `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `uq_payments_receipt`   (`receipt_number`),
  -- Blocks replay of the same GCash reference across reservations.
  UNIQUE KEY `uq_payments_reference` (`method_id`, `reference_number`),
  KEY `idx_payments_reservation` (`reservation_id`, `status`),
  KEY `idx_payments_rental`     (`rental_id`),
  KEY `idx_payments_status`      (`status`, `created_at`),
  KEY `idx_payments_verifier`    (`verified_by`),
  KEY `idx_payments_receiver`    (`received_by`),
  CONSTRAINT `fk_payments_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`)  ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_payments_method`      FOREIGN KEY (`method_id`)      REFERENCES `payment_methods` (`method_id`)    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_payments_verifier`    FOREIGN KEY (`verified_by`)    REFERENCES `users` (`user_id`)                ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_payments_receiver`    FOREIGN KEY (`received_by`)    REFERENCES `users` (`user_id`)                ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `chk_payments_amount`     CHECK (`amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECTION 6: QR BOOKING & VERIFICATION
-- =====================================================================

CREATE TABLE `qr_bookings` (
  `qr_id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id`    BIGINT UNSIGNED NOT NULL,
  `token_hash`    CHAR(64)     NOT NULL COMMENT 'SHA-256 of the QR payload; raw token only in the image',
  `qr_image_path` VARCHAR(255) DEFAULT NULL,
  `status`        ENUM('active','used','expired','revoked') NOT NULL DEFAULT 'active',
  `issued_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at`    DATETIME     NOT NULL,
  `scanned_at`    DATETIME     DEFAULT NULL,
  `scanned_by`    BIGINT UNSIGNED DEFAULT NULL COMMENT 'Driver user_id who verified boarding',
  `scan_count`    SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '>1 indicates a reuse attempt',
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`qr_id`),
  UNIQUE KEY `uq_qr_token`   (`token_hash`),
  UNIQUE KEY `uq_qr_booking` (`booking_id`),
  KEY `idx_qr_status`  (`status`, `expires_at`),
  KEY `idx_qr_scanner` (`scanned_by`),
  CONSTRAINT `fk_qr_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE  ON UPDATE RESTRICT,
  CONSTRAINT `fk_qr_scanner` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`user_id`)       ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECTION 7: LIVE TRACKING (GPS)
-- =====================================================================

-- ---------------------------------------------------------------------
-- driver_current_location: exactly one row per driver, UPSERTed on every
-- ping. The map polls THIS table -- always a single-row primary key read,
-- never a scan of the history table. Keeps the map fast as history grows.
-- ---------------------------------------------------------------------
CREATE TABLE `driver_current_location` (
  `driver_id`       BIGINT UNSIGNED NOT NULL,
  `booking_id`      BIGINT UNSIGNED DEFAULT NULL,
  `latitude`        DECIMAL(10,8) NOT NULL,
  `longitude`       DECIMAL(11,8) NOT NULL,
  `accuracy_meters` SMALLINT UNSIGNED DEFAULT NULL,
  `heading_degrees` SMALLINT UNSIGNED DEFAULT NULL,
  `speed_kph`       DECIMAL(5,2) DEFAULT NULL,
  `battery_level`   TINYINT UNSIGNED DEFAULT NULL,
  `is_moving`       TINYINT(1)   NOT NULL DEFAULT 0,
  `recorded_at`     DATETIME(3)  NOT NULL,
  `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`driver_id`),
  KEY `idx_dcl_booking` (`booking_id`),
  KEY `idx_dcl_recorded` (`recorded_at`),
  CONSTRAINT `fk_dcl_driver`  FOREIGN KEY (`driver_id`)  REFERENCES `drivers` (`driver_id`)   ON DELETE CASCADE  ON UPDATE RESTRICT,
  CONSTRAINT `fk_dcl_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Append-only breadcrumb trail. High write volume: prune/archive by cron.
CREATE TABLE `driver_locations` (
  `location_log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `driver_id`       BIGINT UNSIGNED NOT NULL,
  `booking_id`      BIGINT UNSIGNED DEFAULT NULL,
  `latitude`        DECIMAL(10,8) NOT NULL,
  `longitude`       DECIMAL(11,8) NOT NULL,
  `accuracy_meters` SMALLINT UNSIGNED DEFAULT NULL,
  `heading_degrees` SMALLINT UNSIGNED DEFAULT NULL,
  `speed_kph`       DECIMAL(5,2) DEFAULT NULL,
  `recorded_at`     DATETIME(3)  NOT NULL,
  PRIMARY KEY (`location_log_id`),
  KEY `idx_dl_driver_time`  (`driver_id`, `recorded_at`),
  KEY `idx_dl_booking_time` (`booking_id`, `recorded_at`),
  CONSTRAINT `fk_dl_driver`  FOREIGN KEY (`driver_id`)  REFERENCES `drivers` (`driver_id`)   ON DELETE CASCADE  ON UPDATE RESTRICT,
  CONSTRAINT `fk_dl_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECTION 8: LIVE CHAT
-- =====================================================================

CREATE TABLE `conversations` (
  `conversation_id`       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id`            BIGINT UNSIGNED NOT NULL COMMENT 'Chat is scoped to a trip -- prevents unsolicited contact',
  `customer_user_id`      BIGINT UNSIGNED NOT NULL,
  `driver_user_id`        BIGINT UNSIGNED NOT NULL,
  `last_message_at`       DATETIME     DEFAULT NULL,
  `last_message_preview`  VARCHAR(160) DEFAULT NULL,
  `customer_unread_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `driver_unread_count`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status`                ENUM('open','closed','archived') NOT NULL DEFAULT 'open',
  `created_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversation_id`),
  UNIQUE KEY `uq_conversations_booking` (`booking_id`),
  KEY `idx_conv_customer` (`customer_user_id`, `last_message_at`),
  KEY `idx_conv_driver`   (`driver_user_id`,   `last_message_at`),
  CONSTRAINT `fk_conv_booking`  FOREIGN KEY (`booking_id`)       REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_conv_customer` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`user_id`)       ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_conv_driver`   FOREIGN KEY (`driver_user_id`)   REFERENCES `users` (`user_id`)       ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `messages` (
  `message_id`       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id`  BIGINT UNSIGNED NOT NULL,
  `sender_user_id`   BIGINT UNSIGNED NOT NULL,
  `message_type`     ENUM('text','image','location','system') NOT NULL DEFAULT 'text',
  `body`             TEXT         DEFAULT NULL,
  `attachment_path`  VARCHAR(255) DEFAULT NULL,
  `latitude`         DECIMAL(10,8) DEFAULT NULL COMMENT 'For location-type messages',
  `longitude`        DECIMAL(11,8) DEFAULT NULL,
  `is_read`          TINYINT(1)   NOT NULL DEFAULT 0,
  `read_at`          DATETIME     DEFAULT NULL,
  `created_at`       TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`message_id`),
  KEY `idx_messages_conversation` (`conversation_id`, `created_at`),
  KEY `idx_messages_unread`       (`conversation_id`, `is_read`),
  KEY `idx_messages_sender`       (`sender_user_id`),
  CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_messages_sender`       FOREIGN KEY (`sender_user_id`)  REFERENCES `users` (`user_id`)                ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECTION 9: NOTIFICATIONS
-- =====================================================================

CREATE TABLE `notifications` (
  `notification_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`         BIGINT UNSIGNED NOT NULL,
  `channel`         ENUM('in_app','email','sms','push') NOT NULL DEFAULT 'in_app',
  `type`            VARCHAR(60)  NOT NULL COMMENT 'e.g. booking.confirmed, payment.verified',
  `title`           VARCHAR(150) NOT NULL,
  `body`            TEXT         NOT NULL,
  `related_type`    VARCHAR(50)  DEFAULT NULL COMMENT 'Polymorphic target, e.g. reservation',
  `related_id`      BIGINT UNSIGNED DEFAULT NULL,
  `action_url`      VARCHAR(255) DEFAULT NULL,
  `is_read`         TINYINT(1)   NOT NULL DEFAULT 0,
  `read_at`         DATETIME     DEFAULT NULL,
  `delivery_status` ENUM('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
  `attempts`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `sent_at`         DATETIME     DEFAULT NULL,
  `error_message`   VARCHAR(500) DEFAULT NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_notif_user_unread` (`user_id`, `is_read`, `created_at`),
  KEY `idx_notif_queue`       (`delivery_status`, `channel`, `attempts`),
  KEY `idx_notif_related`     (`related_type`, `related_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECTION 10: FEEDBACK & RATINGS
-- =====================================================================

CREATE TABLE `ratings` (
  `rating_id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id`          BIGINT UNSIGNED DEFAULT NULL,
  `rental_id`           BIGINT UNSIGNED DEFAULT NULL,
  `customer_id`         BIGINT UNSIGNED NOT NULL,
  `driver_id`           BIGINT UNSIGNED DEFAULT NULL,
  `van_id`              BIGINT UNSIGNED DEFAULT NULL,
  `overall_rating`      TINYINT UNSIGNED NOT NULL,
  `punctuality_rating`  TINYINT UNSIGNED DEFAULT NULL,
  `cleanliness_rating`  TINYINT UNSIGNED DEFAULT NULL,
  `driving_rating`      TINYINT UNSIGNED DEFAULT NULL,
  `comment`             TEXT         DEFAULT NULL,
  `is_visible`          TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'Admin can hide abusive reviews',
  `hidden_reason`       VARCHAR(255) DEFAULT NULL,
  `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rating_id`),
  UNIQUE KEY `uq_ratings_trip`    (`booking_id`, `rental_id`) COMMENT 'One rating per trip/rental',
  KEY `idx_ratings_rental`   (`rental_id`),
  KEY `idx_ratings_driver`   (`driver_id`, `is_visible`),
  KEY `idx_ratings_van`      (`van_id`),
  KEY `idx_ratings_customer` (`customer_id`),
  CONSTRAINT `fk_ratings_booking`  FOREIGN KEY (`booking_id`)  REFERENCES `bookings` (`booking_id`)   ON DELETE CASCADE  ON UPDATE RESTRICT,
  CONSTRAINT `fk_ratings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE  ON UPDATE RESTRICT,
  CONSTRAINT `fk_ratings_driver`   FOREIGN KEY (`driver_id`)   REFERENCES `drivers` (`driver_id`)     ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_ratings_van`      FOREIGN KEY (`van_id`)      REFERENCES `vans` (`van_id`)           ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `chk_ratings_overall` CHECK (`overall_rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- General system feedback, separate from per-trip ratings.
CREATE TABLE `feedback` (
  `feedback_id`  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL allows anonymous submissions',
  `category`     ENUM('bug','suggestion','complaint','compliment','other') NOT NULL DEFAULT 'other',
  `subject`      VARCHAR(150) NOT NULL,
  `message`      TEXT         NOT NULL,
  `contact_email` VARCHAR(150) DEFAULT NULL,
  `status`       ENUM('new','in_review','resolved','closed') NOT NULL DEFAULT 'new',
  `handled_by`   BIGINT UNSIGNED DEFAULT NULL,
  `response`     TEXT         DEFAULT NULL,
  `resolved_at`  DATETIME     DEFAULT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`feedback_id`),
  KEY `idx_feedback_status`  (`status`, `created_at`),
  KEY `idx_feedback_user`    (`user_id`),
  KEY `idx_feedback_handler` (`handled_by`),
  CONSTRAINT `fk_feedback_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `fk_feedback_handler` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SECTION 11: SYSTEM, AUDIT & REPORTS
-- =====================================================================

CREATE TABLE `audit_logs` (
  `log_id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     BIGINT UNSIGNED DEFAULT NULL,
  `action`      VARCHAR(80)  NOT NULL COMMENT 'e.g. payment.verified, van.deleted',
  `entity_type` VARCHAR(50)  DEFAULT NULL,
  `entity_id`   BIGINT UNSIGNED DEFAULT NULL,
  `old_values`  JSON         DEFAULT NULL,
  `new_values`  JSON         DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `user_agent`  VARCHAR(255) DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_audit_user`   (`user_id`, `created_at`),
  KEY `idx_audit_entity` (`entity_type`, `entity_id`),
  KEY `idx_audit_action` (`action`, `created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reports` (
  `report_id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_type`   VARCHAR(60)  NOT NULL COMMENT 'e.g. revenue_summary, driver_performance',
  `title`         VARCHAR(150) NOT NULL,
  `parameters`    JSON         DEFAULT NULL,
  `date_from`     DATE         DEFAULT NULL,
  `date_to`       DATE         DEFAULT NULL,
  `file_path`     VARCHAR(255) DEFAULT NULL,
  `file_format`   ENUM('pdf','csv','xlsx') DEFAULT NULL,
  `generated_by`  BIGINT UNSIGNED DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  KEY `idx_reports_type` (`report_type`, `created_at`),
  KEY `idx_reports_user` (`generated_by`),
  CONSTRAINT `fk_reports_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_settings` (
  `setting_id`    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key`   VARCHAR(80)  NOT NULL,
  `setting_value` TEXT         DEFAULT NULL,
  `data_type`     ENUM('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
  `group_name`    VARCHAR(50)  NOT NULL DEFAULT 'general',
  `description`   VARCHAR(255) DEFAULT NULL,
  `is_public`     TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Safe to expose to frontend',
  `updated_by`    BIGINT UNSIGNED DEFAULT NULL,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`),
  KEY `idx_settings_group` (`group_name`),
  KEY `idx_settings_user`  (`updated_by`),
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- END OF SCHEMA
-- =====================================================================


CREATE TABLE IF NOT EXISTS `van_rentals` (
  `rental_id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_code`     VARCHAR(24)  DEFAULT NULL,
  `van_id`             BIGINT UNSIGNED NOT NULL,
  `route_id`           BIGINT UNSIGNED DEFAULT NULL,
  `customer_id`        BIGINT UNSIGNED NOT NULL,
  `start_date`         DATE NOT NULL,
  `end_date`           DATE NOT NULL,
  `pickup_location_id` BIGINT UNSIGNED DEFAULT NULL,
  `days`               SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `price_per_day`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `deposit_required`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`             ENUM('pending','confirmed','active','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status`     ENUM('pending','partially_paid','paid') NOT NULL DEFAULT 'pending',
  `notes`              TEXT DEFAULT NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rental_id`),
  UNIQUE KEY `uq_rentals_ref`       (`reference_code`),
  KEY `idx_rentals_van`            (`van_id`, `start_date`, `end_date`),
  KEY `idx_rentals_customer`       (`customer_id`, `status`),
  KEY `idx_rentals_route`          (`route_id`),
  CONSTRAINT `fk_rentals_van`       FOREIGN KEY (`van_id`)      REFERENCES `vans` (`van_id`)      ON DELETE CASCADE,
  CONSTRAINT `fk_rentals_customer`  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SITRASS -- 03_seed.sql
-- Baseline reference data required for the system to boot.
--
-- !! COORDINATE ACCURACY NOTICE !!
-- The latitude/longitude values below are APPROXIMATE, derived from
-- public gazetteer data for Sibuyan Island, Romblon. Before production
-- launch these MUST be field-verified with a GPS device (or corrected
-- against OpenStreetMap) -- pickup accuracy directly affects whether a
-- driver finds the passenger. Treat these as placeholders, not truth.
-- =====================================================================

-- (InfinityFree: tinanggal ang USE - naka-select na ang database sa phpMyAdmin)

-- ---------------------------------------------------------------------
-- Admin roles (RBAC)
-- ---------------------------------------------------------------------
INSERT INTO `roles` (`role_code`, `role_name`, `description`, `is_system`) VALUES
('super_admin', 'Super Administrator', 'Unrestricted access including backup, restore and role management', 1),
('dispatcher',  'Dispatcher',          'Manages schedules, van assignment, bookings and live tracking',       1),
('finance',     'Finance Officer',     'Verifies payments, issues receipts and generates revenue reports',    1),
('support',     'Support Staff',       'Handles customer accounts, feedback and read-only booking access',    1);

-- ---------------------------------------------------------------------
-- Permissions (module.action). Extend as modules are built.
-- ---------------------------------------------------------------------
INSERT INTO `permissions` (`permission_code`, `module`, `description`) VALUES
('users.view',        'users',    'View user accounts'),
('users.manage',      'users',    'Create, edit, suspend user accounts'),
('drivers.approve',   'drivers',  'Approve or reject driver applications'),
('vans.view',         'vans',     'View fleet'),
('vans.manage',       'vans',     'Add, edit, retire vans'),
('routes.manage',     'routes',   'Manage routes and locations'),
('schedules.manage',  'schedules','Create and cancel trip schedules'),
('bookings.view',     'bookings', 'View all bookings'),
('bookings.manage',   'bookings', 'Override, reassign or cancel bookings'),
('payments.view',     'payments', 'View payment records'),
('payments.verify',   'payments', 'Verify or reject submitted payments'),
('payments.refund',   'payments', 'Process refunds'),
('tracking.view',     'tracking', 'View live driver tracking board'),
('chat.moderate',     'chat',     'View and moderate customer-driver conversations'),
('reports.generate',  'reports',  'Generate and export reports'),
('audit.view',        'audit',    'View audit logs'),
('settings.manage',   'settings', 'Change system settings'),
('backup.manage',     'backup',   'Run database backup and restore');

-- Super admin gets everything.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`role_id`, p.`permission_id`
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.`role_code` = 'super_admin';

-- Dispatcher: operations only, no money, no settings.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`role_id`, p.`permission_id`
FROM `roles` r JOIN `permissions` p
  ON p.`permission_code` IN ('users.view','vans.view','vans.manage','routes.manage',
     'schedules.manage','bookings.view','bookings.manage','tracking.view','chat.moderate')
WHERE r.`role_code` = 'dispatcher';

-- Finance: money and reports only.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`role_id`, p.`permission_id`
FROM `roles` r JOIN `permissions` p
  ON p.`permission_code` IN ('bookings.view','payments.view','payments.verify',
     'payments.refund','reports.generate')
WHERE r.`role_code` = 'finance';

-- Support: read-mostly.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`role_id`, p.`permission_id`
FROM `roles` r JOIN `permissions` p
  ON p.`permission_code` IN ('users.view','users.manage','bookings.view','chat.moderate')
WHERE r.`role_code` = 'support';

-- ---------------------------------------------------------------------
-- Default super-admin account.
-- Email    : admin@sitrass.local
-- Password : Sitrass@2026
-- !! CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN -- do not deploy as-is !!
-- Hash is bcrypt cost 12, PHP password_verify() compatible.
-- ---------------------------------------------------------------------
INSERT INTO `users`
  (`uuid`, `role`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `status`, `email_verified_at`)
VALUES
  (UUID(), 'admin', 'System', 'Administrator', 'admin@sitrass.local', '+639000000000',
   '$2y$12$WDI48X/J0GNCJTEGs9xfJO21HnGZWSBO6918Jetj8Ns3mBfVHMDlu', 'active', NOW());

INSERT INTO `admins` (`user_id`, `role_id`, `employee_number`, `position`)
SELECT u.`user_id`, r.`role_id`, 'EMP-0001', 'System Administrator'
FROM `users` u CROSS JOIN `roles` r
WHERE u.`email` = 'admin@sitrass.local' AND r.`role_code` = 'super_admin';

-- ---------------------------------------------------------------------
-- Payment methods
-- ---------------------------------------------------------------------
INSERT INTO `payment_methods`
  (`method_code`, `method_name`, `is_online`, `requires_proof`, `account_name`, `account_number`, `instructions`, `sort_order`) VALUES
('gcash', 'GCash', 1, 1, 'SITRASS Transport Services', '09XXXXXXXXX',
 'Send the 30% deposit to the GCash number shown, then upload a clear screenshot of the confirmation. Include the reference number. Admin verification usually completes within 24 hours.', 1),
('face_to_face', 'Face-to-Face (Cash)', 0, 0, NULL, NULL,
 'Pay the deposit in cash at the SITRASS terminal or to the assigned driver on boarding. A receipt will be issued upon confirmation.', 2);

-- ---------------------------------------------------------------------
-- Locations -- Sibuyan Island, Romblon (COORDINATES ARE APPROXIMATE)
-- ---------------------------------------------------------------------
INSERT INTO `locations`
  (`name`, `location_type`, `category`, `barangay`, `municipality`, `latitude`, `longitude`, `landmark`, `sort_order`) VALUES
-- Magdiwang
('Ambulong Port',              'both', 'port',        'Ambulong',   'Magdiwang',     12.49509000, 122.48912000, 'Main seaport serving Magdiwang',              10),
('Magdiwang Town Proper',      'both', 'town_proper', 'Poblacion',  'Magdiwang',     12.49132000, 122.51468000, 'Municipal hall area',                         11),
('Mt. Guiting-Guiting Park HQ','both', 'landmark',    'Tampayan',   'Magdiwang',     12.49202000, 122.52893000, 'DENR natural park registration office',       12),
('Cataja Falls Junction',      'both', 'landmark',    'Tampayan',   'Magdiwang',     12.45906000, 122.53076000, 'Trailhead drop-off point',                    13),
-- San Fernando
('Azagra Port',                'both', 'port',        'Azagra',     'San Fernando',  12.27974000, 122.63209000, 'Seaport serving San Fernando',                20),
('San Fernando Town Proper',   'both', 'town_proper', 'Poblacion',  'San Fernando',  12.31450000, 122.59750000, 'Municipal hall area',                         21),
('Cresta de Gallo Jump-off',   'both', 'landmark',    'Azagra',     'San Fernando',  12.29200000, 122.64000000, 'Boat transfer point for island tours',        22),
('Otod',                       'both', 'barangay',    'Otod',       'San Fernando',  12.29837000, 122.65022000, 'Coastal barangay along the circumferential road', 23),
('Taclobo (San Fernando)',     'both', 'barangay',    'Taclobo',    'San Fernando',  12.31886000, 122.57926000, 'Barangay along the east-south road',          24),
-- Cajidiocan
('Cajidiocan Port',            'both', 'port',        'Poblacion',  'Cajidiocan',    12.37120000, 122.68889000, 'Seaport serving Cajidiocan',                  30),
('Cajidiocan Town Proper',     'both', 'town_proper', 'Poblacion',  'Cajidiocan',    12.36846000, 122.68617000, 'Municipal hall area',                         31),
('Lumbang Este',               'both', 'barangay',    'Lumbang Este','Cajidiocan',   12.41703000, 122.66821000, 'Northern barangay of Cajidiocan',             32),
('Danao Norte',                'both', 'barangay',    'Danao Norte','Cajidiocan',    12.44500000, 122.63000000, 'Barangay along the north-east road',          33),
('Sugod',                      'both', 'barangay',    'Sugod',      'Cajidiocan',    12.37887000, 122.68461000, 'Southern barangay of Cajidiocan',             34);

-- ---------------------------------------------------------------------
-- Routes -- inter-municipal legs on the circumferential road.
-- Distances/durations are ESTIMATES pending odometer verification.
-- ---------------------------------------------------------------------
INSERT INTO `routes`
  (`route_code`, `route_name`, `origin_location_id`, `destination_location_id`,
   `distance_km`, `estimated_duration_minutes`, `base_fare`, `fare_per_passenger`, `road_condition`)
SELECT 'MAG-CAJ', 'Magdiwang - Cajidiocan',
       o.`location_id`, d.`location_id`, 32.00, 70, 0.00, 200.00, 'partially_paved'
FROM `locations` o, `locations` d
WHERE o.`name` = 'Magdiwang Town Proper' AND d.`name` = 'Cajidiocan Town Proper';

INSERT INTO `routes`
  (`route_code`, `route_name`, `origin_location_id`, `destination_location_id`,
   `distance_km`, `estimated_duration_minutes`, `base_fare`, `fare_per_passenger`, `road_condition`)
SELECT 'CAJ-MAG', 'Cajidiocan - Magdiwang',
       o.`location_id`, d.`location_id`, 32.00, 70, 0.00, 200.00, 'partially_paved'
FROM `locations` o, `locations` d
WHERE o.`name` = 'Cajidiocan Town Proper' AND d.`name` = 'Magdiwang Town Proper';

INSERT INTO `routes`
  (`route_code`, `route_name`, `origin_location_id`, `destination_location_id`,
   `distance_km`, `estimated_duration_minutes`, `base_fare`, `fare_per_passenger`, `road_condition`)
SELECT 'CAJ-SF', 'Cajidiocan - San Fernando',
       o.`location_id`, d.`location_id`, 26.00, 55, 0.00, 180.00, 'partially_paved'
FROM `locations` o, `locations` d
WHERE o.`name` = 'Cajidiocan Town Proper' AND d.`name` = 'San Fernando Town Proper';

INSERT INTO `routes`
  (`route_code`, `route_name`, `origin_location_id`, `destination_location_id`,
   `distance_km`, `estimated_duration_minutes`, `base_fare`, `fare_per_passenger`, `road_condition`)
SELECT 'SF-CAJ', 'San Fernando - Cajidiocan',
       o.`location_id`, d.`location_id`, 26.00, 55, 0.00, 180.00, 'partially_paved'
FROM `locations` o, `locations` d
WHERE o.`name` = 'San Fernando Town Proper' AND d.`name` = 'Cajidiocan Town Proper';

INSERT INTO `routes`
  (`route_code`, `route_name`, `origin_location_id`, `destination_location_id`,
   `distance_km`, `estimated_duration_minutes`, `base_fare`, `fare_per_passenger`, `road_condition`)
SELECT 'MAG-SF', 'Magdiwang - San Fernando',
       o.`location_id`, d.`location_id`, 45.00, 100, 0.00, 250.00, 'partially_paved'
FROM `locations` o, `locations` d
WHERE o.`name` = 'Magdiwang Town Proper' AND d.`name` = 'San Fernando Town Proper';

INSERT INTO `routes`
  (`route_code`, `route_name`, `origin_location_id`, `destination_location_id`,
   `distance_km`, `estimated_duration_minutes`, `base_fare`, `fare_per_passenger`, `road_condition`)
SELECT 'SF-MAG', 'San Fernando - Magdiwang',
       o.`location_id`, d.`location_id`, 45.00, 100, 0.00, 250.00, 'partially_paved'
FROM `locations` o, `locations` d
WHERE o.`name` = 'San Fernando Town Proper' AND d.`name` = 'Magdiwang Town Proper';

INSERT INTO `routes`
  (`route_code`, `route_name`, `origin_location_id`, `destination_location_id`,
   `distance_km`, `estimated_duration_minutes`, `base_fare`, `fare_per_passenger`, `road_condition`)
SELECT 'AMB-MAG', 'Ambulong Port - Magdiwang Town',
       o.`location_id`, d.`location_id`, 3.50, 12, 0.00, 50.00, 'paved'
FROM `locations` o, `locations` d
WHERE o.`name` = 'Ambulong Port' AND d.`name` = 'Magdiwang Town Proper';

INSERT INTO `routes`
  (`route_code`, `route_name`, `origin_location_id`, `destination_location_id`,
   `distance_km`, `estimated_duration_minutes`, `base_fare`, `fare_per_passenger`, `road_condition`)
SELECT 'MAG-G2', 'Magdiwang Town - Mt. Guiting-Guiting Park HQ',
       o.`location_id`, d.`location_id`, 6.00, 20, 0.00, 100.00, 'rough'
FROM `locations` o, `locations` d
WHERE o.`name` = 'Magdiwang Town Proper' AND d.`name` = 'Mt. Guiting-Guiting Park HQ';

-- ---------------------------------------------------------------------
-- System settings. Business rules live HERE, never hardcoded in PHP.
-- ---------------------------------------------------------------------
INSERT INTO `system_settings`
  (`setting_key`, `setting_value`, `data_type`, `group_name`, `description`, `is_public`) VALUES
('site_name',                  'SITRASS', 'string',  'general',  'Application display name', 1),
('site_tagline',               'Sibuyan Island Transportation Reservation System', 'string', 'general', 'Shown on landing page', 1),
('contact_email',              'support@sitrass.local', 'string', 'general', 'Public support email', 1),
('contact_phone',              '+639000000000', 'string', 'general', 'Public support hotline', 1),
('timezone',                   'Asia/Manila', 'string', 'general', 'Application timezone', 0),
('currency_code',              'PHP', 'string', 'general', 'ISO currency code', 1),

('deposit_percentage',         '30',  'decimal', 'payment', 'Required reservation deposit, percent of total', 1),
('deposit_hold_minutes',       '120', 'integer', 'payment', 'Minutes an unpaid reservation holds its seats before auto-expiry', 0),
('balance_due_hours_before',   '24',  'integer', 'payment', 'Hours before departure that the remaining balance falls due', 1),
('refund_cutoff_hours',        '48',  'integer', 'payment', 'Cancel at least this many hours ahead to qualify for a deposit refund', 1),
('refund_percentage',          '80',  'decimal', 'payment', 'Percent of deposit refunded within the cutoff window', 1),

('booking_min_lead_hours',     '2',   'integer', 'booking', 'Minimum hours between booking time and departure', 1),
('booking_max_advance_days',   '60',  'integer', 'booking', 'How far ahead customers may book', 1),
('max_passengers_per_booking', '15',  'integer', 'booking', 'Upper bound on passengers in a single reservation', 1),
('cancellation_cutoff_hours',  '12',  'integer', 'booking', 'Latest a customer may self-cancel', 1),
('reschedule_cutoff_hours',    '24',  'integer', 'booking', 'Latest a customer may self-reschedule', 1),
('no_show_grace_minutes',      '20',  'integer', 'booking', 'Wait time before a passenger is marked no-show', 0),

('qr_validity_hours',          '24',  'integer', 'qr',      'Hours a booking QR code remains scannable', 0),

('gps_ping_interval_seconds',  '15',  'integer', 'tracking','How often the driver app reports GPS position', 0),
('gps_stale_after_seconds',    '90',  'integer', 'tracking','Marker is flagged stale past this age', 0),
('gps_history_retention_days', '30',  'integer', 'tracking','Days of breadcrumb history kept before pruning', 0),

('map_default_lat',            '12.4200',  'decimal', 'map', 'Sibuyan Island map center latitude',  1),
('map_default_lng',            '122.5800', 'decimal', 'map', 'Sibuyan Island map center longitude', 1),
('map_default_zoom',           '11', 'integer', 'map', 'Initial Leaflet zoom level', 1),
('map_tile_provider',          'openstreetmap', 'string', 'map', 'Tile source: openstreetmap or google', 0),

('max_upload_size_mb',         '5',   'integer', 'uploads', 'Maximum accepted upload size before compression', 0),
('image_compression_quality',  '80',  'integer', 'uploads', 'JPEG quality used when compressing uploads', 0),
('allowed_image_types',        '["jpg","jpeg","png","webp"]', 'json', 'uploads', 'Permitted image extensions', 0),

('login_max_attempts',         '5',   'integer', 'security', 'Failed logins before lockout', 0),
('login_lockout_minutes',      '15',  'integer', 'security', 'Lockout duration after exceeding max attempts', 0),
('session_lifetime_minutes',   '120', 'integer', 'security', 'Idle session expiry', 0),
('password_min_length',        '8',   'integer', 'security', 'Minimum password length', 1),

('sms_enabled',                '1',   'boolean', 'notifications', 'Toggle Semaphore SMS delivery', 0),
('email_enabled',              '1',   'boolean', 'notifications', 'Toggle PHPMailer email delivery', 0),
('maintenance_mode',           '0',   'boolean', 'general', 'Take the site offline for non-admins', 0);

-- =====================================================================
-- END OF SEED
-- =====================================================================

-- FIX LOCATION COORDINATES (OSM-verified)
UPDATE `locations` SET `latitude` = 12.49509000, `longitude` = 122.48912000 WHERE `name` = 'Ambulong Port';
UPDATE `locations` SET `latitude` = 12.49132000, `longitude` = 122.51468000 WHERE `name` = 'Magdiwang Town Proper';
UPDATE `locations` SET `latitude` = 12.49202000, `longitude` = 122.52893000 WHERE `name` = 'Mt. Guiting-Guiting Park HQ';
UPDATE `locations` SET `latitude` = 12.45906000, `longitude` = 122.53076000 WHERE `name` = 'Cataja Falls Junction';
UPDATE `locations` SET `latitude` = 12.27974000, `longitude` = 122.63209000 WHERE `name` = 'Azagra Port';
UPDATE `locations` SET `latitude` = 12.31450000, `longitude` = 122.59750000 WHERE `name` = 'San Fernando Town Proper';
UPDATE `locations` SET `latitude` = 12.29200000, `longitude` = 122.64000000 WHERE `name` = 'Cresta de Gallo Jump-off';
UPDATE `locations` SET `latitude` = 12.29837000, `longitude` = 122.65022000 WHERE `name` = 'Otod';
UPDATE `locations` SET `latitude` = 12.31886000, `longitude` = 122.57926000 WHERE `name` = 'Taclobo (San Fernando)';
UPDATE `locations` SET `latitude` = 12.37120000, `longitude` = 122.68889000 WHERE `name` = 'Cajidiocan Port';
UPDATE `locations` SET `latitude` = 12.36846000, `longitude` = 122.68617000 WHERE `name` = 'Cajidiocan Town Proper';
UPDATE `locations` SET `latitude` = 12.41703000, `longitude` = 122.66821000 WHERE `name` = 'Lumbang Este';
UPDATE `locations` SET `latitude` = 12.37887000, `longitude` = 122.68461000 WHERE `name` = 'Sugod';

SET FOREIGN_KEY_CHECKS = 1;
-- FRESH INSTALL COMPLETE! Admin: admin@sitrass.local / Sitrass@2026
