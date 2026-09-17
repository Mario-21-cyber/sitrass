-- =====================================================================
-- SITRASS - FIX NG MGA LOCATION COORDINATES (OpenStreetMap-verified)
-- Patakbuhin sa phpMyAdmin: piliin muna ang if0_42853048_sitrass_db
-- sa kaliwang sidebar, tapos SQL tab, i-paste ang buong block, Go.
-- =====================================================================

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
