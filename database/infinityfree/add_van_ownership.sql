-- =====================================================================
-- MIGRATION (LIVE DB): Driver-owned vans (My Vans + Pending Vans)
-- Patakbuhin sa phpMyAdmin: piliin muna ang if0_42853048_sitrass_db,
-- tapos SQL tab, i-paste ang buong block, Go.
-- (Isama rin ang add_van_rentals.sql kung hindi pa naipapatakbo.)
-- =====================================================================

ALTER TABLE `vans`
  MODIFY `status` ENUM('pending','active','maintenance','inactive','retired') NOT NULL DEFAULT 'active',
  ADD COLUMN `driver_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Owning driver (My Vans)' AFTER `van_type`;

ALTER TABLE `vans`
  ADD KEY `idx_vans_driver` (`driver_id`),
  ADD CONSTRAINT `fk_vans_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`) ON DELETE SET NULL;
