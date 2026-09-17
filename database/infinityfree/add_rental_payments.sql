-- =====================================================================
-- MIGRATION (LIVE DB): Rental payment + QR flow (katulad ng shared)
-- Patakbuhin sa phpMyAdmin: piliin muna ang if0_42853048_sitrass_db,
-- tapos SQL tab, i-paste ang buong block, Go.
-- =====================================================================

-- Ang reservation_id ay pwede nang NULL (may rental payments na)
ALTER TABLE `payments`
  MODIFY `reservation_id` BIGINT UNSIGNED DEFAULT NULL,
  ADD COLUMN `rental_id` BIGINT UNSIGNED DEFAULT NULL AFTER `reservation_id`,
  ADD KEY `idx_payments_rental` (`rental_id`);

-- Van rentals: reference code, payment status, ruta, deposit
ALTER TABLE `van_rentals`
  ADD COLUMN `reference_code` VARCHAR(24) DEFAULT NULL AFTER `rental_id`,
  ADD COLUMN `payment_status` ENUM('pending','partially_paid','paid') NOT NULL DEFAULT 'pending' AFTER `status`,
  ADD COLUMN `route_id` BIGINT UNSIGNED DEFAULT NULL AFTER `van_id`,
  ADD COLUMN `deposit_required` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `total_price`,
  ADD UNIQUE KEY `uq_rentals_ref` (`reference_code`),
  ADD KEY `idx_rentals_route` (`route_id`);
