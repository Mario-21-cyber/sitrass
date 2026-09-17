-- Backfill script para sa lumang van_rentals na NULL ang reference_code sa LIVE database
UPDATE `van_rentals` 
SET `reference_code` = CONCAT('RNT-', DATE_FORMAT(COALESCE(created_at, NOW()), '%Y%m%d'), '-', LPAD(rental_id, 4, '0'))
WHERE `reference_code` IS NULL OR `reference_code` = '';
