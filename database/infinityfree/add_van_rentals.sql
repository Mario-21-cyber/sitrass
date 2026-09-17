-- =====================================================================
-- MIGRATION (LIVE DB): Rent a Van feature
-- Patakbuhin sa phpMyAdmin: piliin muna ang if0_42853048_sitrass_db,
-- tapos SQL tab, i-paste ang buong block, Go.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `van_rentals` (
  `rental_id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `van_id`             BIGINT UNSIGNED NOT NULL,
  `customer_id`        BIGINT UNSIGNED NOT NULL,
  `start_date`         DATE NOT NULL,
  `end_date`           DATE NOT NULL,
  `pickup_location_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Suggested meeting point',
  `days`               SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `price_per_day`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`             ENUM('pending','confirmed','active','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes`              TEXT DEFAULT NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rental_id`),
  KEY `idx_rentals_van`      (`van_id`, `start_date`, `end_date`),
  KEY `idx_rentals_customer` (`customer_id`, `status`),
  CONSTRAINT `fk_rentals_van`      FOREIGN KEY (`van_id`)      REFERENCES `vans` (`van_id`)      ON DELETE CASCADE,
  CONSTRAINT `fk_rentals_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
