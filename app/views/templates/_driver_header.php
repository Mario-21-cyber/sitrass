<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SITRASS Driver') ?></title>
    <link rel="stylesheet" href="/sitrass/public/css/style.css">
</head>
<body>
<?php
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    function navActiveD($path, $currentPath) { return strpos($currentPath, $path) !== false ? ' active' : ''; }
?>
    <div class="top-nav">
        <img src="/sitrass/public/img/logo.png" alt="SITRASS" class="logo">
        <div class="nav-links">
            <a class="<?= navActiveD('/driver/dashboard', $currentPath) ?>" href="/sitrass/public/driver/dashboard">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                Mga Booking Ko
            </a>
            <a class="<?= navActiveD('/driver/scanQr', $currentPath) ?>" href="/sitrass/public/driver/scanQr">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><line x1="14" y1="14" x2="21" y2="14"/><line x1="14" y1="21" x2="21" y2="21"/><line x1="17" y1="14" x2="17" y2="21"/></svg>
                I-verify ang QR
            </a>
        </div>
        <div class="user-info">
            <a href="/sitrass/public/profile/edit"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></a> (Driver)
            &nbsp;|&nbsp;
            <a href="/sitrass/public/auth/logout" style="color:var(--amber-light);">Logout</a>
        </div>
    </div>
    <div class="page-wrap">