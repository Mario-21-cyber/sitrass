<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SITRASS') ?></title>
    <link rel="stylesheet" href="/sitrass/public/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-database-compat.js"></script>
    <script src="/sitrass/public/js/firebase-config.js"></script>
</head>
<body>
    <div class="top-nav">
        <img src="/sitrass/public/img/logo.png" alt="SITRASS" class="logo">
        <div class="user-info">
            <a href="/sitrass/public/profile/edit"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></a>
            &nbsp;|&nbsp;
            <a href="/sitrass/public/auth/logout" style="color:var(--amber-light);">Logout</a>
        </div>
    </div>
    <div class="page-wrap">