<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SITRASS Admin') ?></title>
    <link rel="stylesheet" href="/sitrass/public/css/style.css">
</head>
<body>
<?php
    // Simpleng active-page detection batay sa URL - walang kailangang baguhin sa mga controller.
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    function navActive($path, $currentPath, $exact = false) {
        $currentPath = rtrim($currentPath, '/');
        $path = rtrim($path, '/');
        if ($exact) {
            // Tumutugma lang kung DITO nagtatapos ang currentPath - hindi lang basta
            // substring kahit saan (para hindi mag-highlight ang "/payments" kapag
            // nasa "/payments/methods" tayo).
            return substr($currentPath, -strlen($path)) === $path ? ' active' : '';
        }
        return strpos($currentPath, $path) !== false ? ' active' : '';
    }
?>
    <div class="admin-shell">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <div class="sidebar" id="sidebar">
            <img src="/sitrass/public/img/logo.png" alt="SITRASS" class="logo">
            <div class="brand">SITRASS Admin</div>

            <div class="nav-section-label"><?= t('nav_section_overview') ?></div>
            <a class="nav-item<?= navActive('/admin/dashboard', $currentPath) ?>" href="/sitrass/public/admin/dashboard">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                <?= t('nav_dashboard') ?>
            </a>
                        <a class="nav-item<?= navActive('/admin/pending-customers', $currentPath) ?>" href="/sitrass/public/admin/pending-customers">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <?= t('nav_pending_accounts') ?>
            </a>
            <a class="nav-item<?= navActive('/admin/users', $currentPath) ?>" href="/sitrass/public/admin/users">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <?= t('nav_users') ?>
            </a>

            <div class="nav-section-label"><?= t('nav_section_operations') ?></div>
            <a class="nav-item<?= navActive('/vans', $currentPath) ?>" href="/sitrass/public/vans">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                <?= t('nav_vans') ?>
            </a>
            <a class="nav-item<?= navActive('/locations', $currentPath) ?>" href="/sitrass/public/locations">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= t('nav_locations') ?>
            </a>
            <a class="nav-item<?= navActive('/routes', $currentPath) ?>" href="/sitrass/public/routes">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="19" r="3"/><circle cx="18" cy="5" r="3"/><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/></svg>
                <?= t('nav_routes') ?>
            </a>
            <a class="nav-item<?= navActive('/schedules', $currentPath) ?>" href="/sitrass/public/schedules">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <?= t('nav_schedules') ?>
            </a>

            <div class="nav-section-label"><?= t('nav_section_transactions') ?></div>
            <a class="nav-item<?= navActive('/payments', $currentPath, true) ?>" href="/sitrass/public/payments">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                <?= t('nav_payments') ?>
            </a>
            <a class="nav-item<?= navActive('/payments/methods', $currentPath) ?>" href="/sitrass/public/payments/methods">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M2 10h20M6 14h4"/></svg>
                <?= t('nav_payment_methods') ?>
            </a>
            <a class="nav-item<?= navActive('/ratings', $currentPath) ?>" href="/sitrass/public/ratings">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <?= t('nav_ratings') ?>
            </a>

            <div class="nav-section-label"><?= t('nav_section_administration') ?></div>
            <a class="nav-item<?= navActive('/audit', $currentPath) ?>" href="/sitrass/public/audit">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 15h6M9 11h2"/></svg>
                <?= t('nav_audit_logs') ?>
            </a>
            <a class="nav-item<?= navActive('/settings', $currentPath) ?>" href="/sitrass/public/settings">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <?= t('nav_settings') ?>
            </a>
            <a class="nav-item<?= navActive('/feedback', $currentPath) ?>" href="/sitrass/public/feedback">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <?= t('nav_feedback') ?>
            </a>

            <div class="nav-section-label"><?= t('nav_section_account') ?></div>
            <a class="nav-item<?= navActive('/profile', $currentPath) ?>" href="/sitrass/public/profile/edit">
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
                <h2 style="margin:0;"><?= htmlspecialchars($pageHeading ?? '') ?></h2>
                <div class="user-info"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?> (<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)</div>
            </div>