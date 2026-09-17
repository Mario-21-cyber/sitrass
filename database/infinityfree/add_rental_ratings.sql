-- =====================================================================
-- MIGRATION (LIVE DB): Rental ratings (rate flow katulad ng shared)
-- Patakbuhin sa phpMyAdmin: piliin muna ang if0_42853048_sitrass_db,
-- tapos SQL tab, i-paste ang buong block, Go.
-- =====================================================================

-- Ang booking_id ay pwede nang NULL (may rental ratings na)
ALTER TABLE `ratings`
  MODIFY `booking_id` BIGINT UNSIGNED DEFAULT NULL,
  ADD COLUMN `rental_id` BIGINT UNSIGNED DEFAULT NULL AFTER `booking_id`,
  ADD KEY `idx_ratings_rental` (`rental_id`);
