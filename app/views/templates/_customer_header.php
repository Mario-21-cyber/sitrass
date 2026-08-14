<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SITRASS') ?></title>
    <link rel="stylesheet" href="/sitrass/public/css/style.css">
</head>
<body>
<?php
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    function navActiveCust($path, $currentPath) { return strpos($currentPath, $path) !== false ? ' active' : ''; }
    $pageHeadingC = str_replace(' - SITRASS', '', $pageTitle ?? '');
?>
    <div class="admin-shell">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <div class="sidebar" id="sidebar">
            <img src="/sitrass/public/img/logo.png" alt="SITRASS" class="logo">
            <div class="brand">SITRASS</div>

            <div class="nav-section-label">Overview</div>
            <a class="nav-item<?= navActiveCust('/auth/loggedin', $currentPath) ?>" href="/sitrass/public/auth/loggedin">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                Dashboard
            </a>

            <div class="nav-section-label">Biyahe</div>
            <a class="nav-item<?= navActiveCust('/customer/search', $currentPath) ?>" href="/sitrass/public/customer/search">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Maghanap ng Biyahe
            </a>
            <a class="nav-item<?= navActiveCust('/customer/myBookings', $currentPath) ?>" href="/sitrass/public/customer/myBookings">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Aking Mga Booking
            </a>
            <a class="nav-item<?= navActiveCust('/customer/toRate', $currentPath) ?>" href="/sitrass/public/customer/toRate">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Mag-rate ng Biyahe
            </a>

            <div class="nav-section-label">Suporta</div>
            <a class="nav-item<?= navActiveCust('/customer/feedback', $currentPath) ?>" href="/sitrass/public/customer/feedback">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Magbigay ng Feedback
            </a>

            <div class="nav-section-label">Account</div>
            <a class="nav-item<?= navActiveCust('/profile', $currentPath) ?>" href="/sitrass/public/profile/edit">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                Aking Profile
            </a>
            <a class="nav-item" href="/sitrass/public/auth/logout">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
        <div class="main-content">
            <div class="topbar">
                <button class="menu-toggle" id="menuToggle">☰ Menu</button>
                <h2 style="margin:0;"><?= htmlspecialchars($pageHeadingC) ?></h2>
                <div class="user-info"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
            </div>