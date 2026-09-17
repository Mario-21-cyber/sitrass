<?php

// Default na lokal (XAMPP) na database settings.
// Kapag may config/database.local.php (gaya sa live hosting tulad ng
// InfinityFree), i-o-override nito ang mga default sa ibaba - hindi
// kailangang baguhin ang file na ito para mag-deploy.
$config = [
    'host' => 'localhost',
    'dbname' => 'sitrass_db',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
];

$localFile = __DIR__ . '/database.local.php';
if (is_file($localFile)) {
    $config = array_merge($config, require $localFile);
}

return $config;
