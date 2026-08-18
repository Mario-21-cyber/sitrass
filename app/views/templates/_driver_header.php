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
    function navActiveDrv($path, $currentPath) { return strpos($currentPath, $path) !== false ? ' active' : ''; }
    $pageHeadingD = str_replace(' - SITRASS', '', $pageTitle ?? '');
?>
    <div class="admin-shell">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <div class="sidebar" id="sidebar">
            <img src="/sitrass/public/img/logo.png" alt="SITRASS" class="logo">
            <div class="brand">SITRASS Driver</div>

            <div class="nav-section-label"><?= t('nav_section_overview') ?></div>
            <a class="nav-item<?= navActiveDrv('/driver/dashboard', $currentPath) ?>" href="/sitrass/public/driver/dashboard">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                <?= t('nav_my_trips') ?>
            </a>

            <div class="nav-section-label"><?= t('nav_section_operations') ?></div>
            <a class="nav-item<?= navActiveDrv('/driver/scanQr', $currentPath) ?>" href="/sitrass/public/driver/scanQr">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><line x1="14" y1="14" x2="21" y2="14"/><line x1="14" y1="21" x2="21" y2="21"/><line x1="17" y1="14" x2="17" y2="21"/></svg>
                <?= t('nav_scan_qr') ?>
            </a>

            <div class="nav-section-label"><?= t('nav_section_account') ?></div>
            <a class="nav-item<?= navActiveDrv('/profile', $currentPath) ?>" href="/sitrass/public/profile/edit">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                <?= t('nav_profile') ?>
            </a>
            <a class="nav-item" href="/sitrass/public/auth/logout">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <?= t('nav_logout') ?>
            </a>
        </div>
        <div class="main-content">
            <div class="topbar">
                <button class="menu-toggle" id="menuToggle">☰ Menu</button>
                <h2 style="margin:0;"><?= htmlspecialchars($pageHeadingD) ?></h2>
                <div class="user-info"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?> (Driver)</div>
            </div>