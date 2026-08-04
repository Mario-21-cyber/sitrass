-- =====================================================================
-- SITRASS -- 02_views.sql
-- Compatibility views (spec parity) + read-model helpers.
-- Views keep dashboard/report queries out of PHP controllers.
-- =====================================================================

USE `sitrass_db`;

-- ---------------------------------------------------------------------
-- Spec-parity views. The spec names PickupLocations and Destinations as
-- separate entities; physically they are one `locations` table. These
-- views give the spec's interface without duplicating coordinate data.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW `pickup_locations` AS
SELECT `location_id`, `name`, `category`, `barangay`, `municipality`,
       `province`, `latitude`, `longitude`, `landmark`, `is_active`, `sort_order`
FROM `locations`
WHERE `location_type` IN ('pickup','both');

CREATE OR REPLACE VIEW `destinations` AS
SELECT `location_id`, `name`, `category`, `barangay`, `municipality`,
       `province`, `latitude`, `longitude`, `landmark`, `is_active`, `sort_order`
FROM `locations`
WHERE `location_type` IN ('destination','both');

-- ---------------------------------------------------------------------
-- vw_user_accounts: flattened login/profile read model.
-- Avoids repeating a 4-table LEFT JOIN in every controller.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW `vw_user_accounts` AS
SELECT
    u.`user_id`,
    u.`uuid`,
    u.`role`,
    CONCAT(u.`first_name`, ' ', u.`last_name`) AS `full_name`,
    u.`email`,
    u.`phone`,
    u.`status`,
    u.`profile_picture`,
    u.`last_login_at`,
    c.`customer_id`,
    d.`driver_id`,
    d.`availability_status`,
    d.`is_approved` AS `driver_is_approved`,
    a.`admin_id`,
    a.`role_id` AS `admin_role_id`,
    u.`created_at`
FROM `users` u
LEFT JOIN `customers` c ON c.`user_id` = u.`user_id`
LEFT JOIN `drivers`   d ON d.`user_id` = u.`user_id`
LEFT JOIN `admins`    a ON a.`user_id` = u.`user_id`
WHERE u.`deleted_at` IS NULL;

-- ---------------------------------------------------------------------
-- vw_available_schedules: what the customer search screen queries.
-- Filters out cancelled/departed trips and full vans in one place, so
-- "sold out" logic can never drift between search, detail, and booking.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW `vw_available_schedules` AS
SELECT
    ts.`schedule_id`,
    ts.`departure_date`,
    ts.`departure_time`,
    ts.`estimated_arrival`,
    ts.`available_seats`,
    ts.`total_seats`,
    ts.`fare_per_seat`,
    ts.`booking_mode`,
    r.`route_id`,
    r.`route_code`,
    r.`route_name`,
    r.`distance_km`,
    r.`estimated_duration_minutes`,
    origin.`location_id`  AS `origin_id`,
    origin.`name`         AS `origin_name`,
    dest.`location_id`    AS `destination_id`,
    dest.`name`           AS `destination_name`,
    v.`van_id`,
    v.`plate_number`,
    v.`make`,
    v.`model`,
    v.`van_type`,
    v.`has_aircon`,
    v.`has_wifi`,
    d.`driver_id`,
    CONCAT(du.`first_name`, ' ', du.`last_name`) AS `driver_name`,
    d.`rating_average`,
    d.`rating_count`,
    (SELECT vi.`image_path` FROM `van_images` vi
      WHERE vi.`van_id` = v.`van_id` AND vi.`is_primary` = 1 LIMIT 1) AS `primary_image`
FROM `trip_schedules` ts
JOIN `routes`    r      ON r.`route_id` = ts.`route_id` AND r.`is_active` = 1
JOIN `locations` origin ON origin.`location_id` = r.`origin_location_id`
JOIN `locations` dest   ON dest.`location_id`   = r.`destination_location_id`
JOIN `vans`      v      ON v.`van_id` = ts.`van_id` AND v.`status` = 'active' AND v.`deleted_at` IS NULL
LEFT JOIN `drivers` d   ON d.`driver_id` = ts.`driver_id`
LEFT JOIN `users`   du  ON du.`user_id` = d.`user_id`
WHERE ts.`status` = 'scheduled'
  AND ts.`available_seats` > 0
  AND TIMESTAMP(ts.`departure_date`, ts.`departure_time`) > NOW();

-- ---------------------------------------------------------------------
-- vw_reservation_summary: one row per reservation for booking history,
-- admin lists, and receipts -- with payment totals already aggregated.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW `vw_reservation_summary` AS
SELECT
    rs.`reservation_id`,
    rs.`reference_code`,
    rs.`customer_id`,
    CONCAT(cu.`first_name`, ' ', cu.`last_name`) AS `customer_name`,
    cu.`email`  AS `customer_email`,
    cu.`phone`  AS `customer_phone`,
    rs.`booking_type`,
    rs.`passenger_count`,
    rs.`is_round_trip`,
    rs.`total_amount`,
    rs.`deposit_required`,
    rs.`amount_paid`,
    rs.`balance_due`,
    rs.`status`,
    rs.`payment_status`,
    rs.`created_at`,
    (SELECT MIN(b.`travel_date`) FROM `bookings` b WHERE b.`reservation_id` = rs.`reservation_id`) AS `first_travel_date`,
    (SELECT COUNT(*)             FROM `bookings` b WHERE b.`reservation_id` = rs.`reservation_id`) AS `leg_count`,
    (SELECT COALESCE(SUM(p.`amount`), 0) FROM `payments` p
       WHERE p.`reservation_id` = rs.`reservation_id` AND p.`status` = 'verified') AS `verified_payments_total`,
    (SELECT COUNT(*) FROM `payments` p
       WHERE p.`reservation_id` = rs.`reservation_id` AND p.`status` = 'pending')  AS `pending_payment_count`
FROM `reservations` rs
JOIN `customers` c  ON c.`customer_id` = rs.`customer_id`
JOIN `users`     cu ON cu.`user_id`    = c.`user_id`;

-- ---------------------------------------------------------------------
-- vw_active_trips: the admin live-tracking board and the customer's
-- "where is my van" screen both read from here.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW `vw_active_trips` AS
SELECT
    b.`booking_id`,
    b.`reservation_id`,
    rs.`reference_code`,
    b.`status`          AS `booking_status`,
    b.`travel_date`,
    b.`pickup_time`,
    b.`trip_started_at`,
    v.`van_id`,
    v.`plate_number`,
    d.`driver_id`,
    CONCAT(du.`first_name`, ' ', du.`last_name`) AS `driver_name`,
    du.`phone`          AS `driver_phone`,
    CONCAT(cu.`first_name`, ' ', cu.`last_name`) AS `customer_name`,
    cu.`phone`          AS `customer_phone`,
    pick.`name`         AS `pickup_name`,
    pick.`latitude`     AS `pickup_lat`,
    pick.`longitude`    AS `pickup_lng`,
    drop_loc.`name`     AS `dropoff_name`,
    drop_loc.`latitude` AS `dropoff_lat`,
    drop_loc.`longitude` AS `dropoff_lng`,
    dcl.`latitude`      AS `current_lat`,
    dcl.`longitude`     AS `current_lng`,
    dcl.`speed_kph`,
    dcl.`heading_degrees`,
    dcl.`recorded_at`   AS `location_updated_at`,
    r.`distance_km`,
    r.`estimated_duration_minutes`
FROM `bookings` b
JOIN `reservations` rs      ON rs.`reservation_id` = b.`reservation_id`
JOIN `customers` c          ON c.`customer_id` = rs.`customer_id`
JOIN `users` cu             ON cu.`user_id` = c.`user_id`
JOIN `vans` v               ON v.`van_id` = b.`van_id`
JOIN `routes` r             ON r.`route_id` = b.`route_id`
JOIN `locations` pick       ON pick.`location_id` = b.`pickup_location_id`
JOIN `locations` drop_loc   ON drop_loc.`location_id` = b.`dropoff_location_id`
LEFT JOIN `drivers` d       ON d.`driver_id` = b.`driver_id`
LEFT JOIN `users` du        ON du.`user_id` = d.`user_id`
LEFT JOIN `driver_current_location` dcl ON dcl.`driver_id` = d.`driver_id`
WHERE b.`status` IN ('accepted','en_route','picked_up');

-- ---------------------------------------------------------------------
-- vw_daily_revenue: dashboard analytics source (verified money only).
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW `vw_daily_revenue` AS
SELECT
    DATE(p.`verified_at`) AS `revenue_date`,
    pm.`method_code`,
    COUNT(*)              AS `transaction_count`,
    SUM(CASE WHEN p.`payment_type` = 'refund' THEN -p.`amount` ELSE p.`amount` END) AS `net_amount`
FROM `payments` p
JOIN `payment_methods` pm ON pm.`method_id` = p.`method_id`
WHERE p.`status` = 'verified' AND p.`verified_at` IS NOT NULL
GROUP BY DATE(p.`verified_at`), pm.`method_code`;

-- =====================================================================
-- END OF VIEWS
-- =====================================================================
