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

USE `sitrass_db`;

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
('Ambulong Port',              'both', 'port',        'Ambulong',   'Magdiwang',     12.50361000, 122.51528000, 'Main seaport serving Magdiwang',              10),
('Magdiwang Town Proper',      'both', 'town_proper', 'Poblacion',  'Magdiwang',     12.48861000, 122.52306000, 'Municipal hall area',                         11),
('Mt. Guiting-Guiting Park HQ','both', 'landmark',    'Tampayan',   'Magdiwang',     12.45500000, 122.53500000, 'DENR natural park registration office',       12),
('Cataja Falls Junction',      'both', 'landmark',    'Tampayan',   'Magdiwang',     12.46500000, 122.51000000, 'Trailhead drop-off point',                    13),
-- San Fernando
('Azagra Port',                'both', 'port',        'Azagra',     'San Fernando',  12.32778000, 122.53194000, 'Seaport serving San Fernando',                20),
('San Fernando Town Proper',   'both', 'town_proper', 'Poblacion',  'San Fernando',  12.31750000, 122.55222000, 'Municipal hall area',                         21),
('Cresta de Gallo Jump-off',   'both', 'landmark',    'Azagra',     'San Fernando',  12.32000000, 122.53500000, 'Boat transfer point for island tours',        22),
('Otod',                       'both', 'barangay',    'Otod',       'San Fernando',  12.34000000, 122.58000000, 'Coastal barangay along the circumferential road', 23),
('Taclobo (San Fernando)',     'both', 'barangay',    'Taclobo',    'San Fernando',  12.33000000, 122.61000000, 'Barangay along the east-south road',          24),
-- Cajidiocan
('Cajidiocan Port',            'both', 'port',        'Poblacion',  'Cajidiocan',    12.41778000, 122.68250000, 'Seaport serving Cajidiocan',                  30),
('Cajidiocan Town Proper',     'both', 'town_proper', 'Poblacion',  'Cajidiocan',    12.41111000, 122.64083000, 'Municipal hall area',                         31),
('Lumbang Este',               'both', 'barangay',    'Lumbang Este','Cajidiocan',   12.43000000, 122.65000000, 'Northern barangay of Cajidiocan',             32),
('Danao Norte',                'both', 'barangay',    'Danao Norte','Cajidiocan',    12.44500000, 122.63000000, 'Barangay along the north-east road',          33),
('Sugod',                      'both', 'barangay',    'Sugod',      'Cajidiocan',    12.39000000, 122.62000000, 'Southern barangay of Cajidiocan',             34);

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
