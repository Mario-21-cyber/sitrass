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
    function navActiveC($path, $currentPath) { return strpos($currentPath, $path) !== false ? ' active' : ''; }
?>
    <div class="top-nav">
        <img src="/sitrass/public/img/logo.png" alt="SITRASS" class="logo">
        <div class="nav-links">
            <a class="<?= navActiveC('/customer/search', $currentPath) ?>" href="/sitrass/public/customer/search">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Maghanap
            </a>
            <a class="<?= navActiveC('/customer/myBookings', $currentPath) ?>" href="/sitrass/public/customer/myBookings">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Mga Booking
            </a>
            <a class="<?= navActiveC('/customer/toRate', $currentPath) ?>" href="/sitrass/public/customer/toRate">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Mag-rate
            </a>
            <a class="<?= navActiveC('/customer/feedback', $currentPath) ?>" href="/sitrass/public/customer/feedback">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Feedback
            </a>
        </div>
        <div class="user-info">
            <a href="/sitrass/public/profile/edit"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></a>
            &nbsp;|&nbsp;
            <a href="/sitrass/public/auth/logout" style="color:var(--amber-light);">Logout</a>
        </div>
    </div>
    <div class="page-wrap">