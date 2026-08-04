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

CREATE DATABASE IF NOT EXISTS `sitrass_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `sitrass_db`;

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
  `status`               ENUM('active','maintenance','inactive','retired') NOT NULL DEFAULT 'active',
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
  `reservation_id`    BIGINT UNSIGNED NOT NULL,
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
  `booking_id`          BIGINT UNSIGNED NOT NULL,
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
  UNIQUE KEY `uq_ratings_booking` (`booking_id`) COMMENT 'One rating per completed trip',
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
