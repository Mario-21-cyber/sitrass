<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SITRASS Admin') ?></title>
    <link rel="stylesheet" href="/sitrass/public/css/style.css">
</head>
<body>
    <div class="admin-shell">
        <div class="sidebar">
            <img src="/sitrass/public/img/logo.png" alt="SITRASS" class="logo">
            <div class="brand" style="color:#fff; font-size:1.1rem;">SITRASS Admin</div>
            <br>
            <a href="/sitrass/public/admin/dashboard">Dashboard</a>
<a href="/sitrass/public/admin/pending-customers">Mga Naghihintay na Account</a>
<a href="/sitrass/public/vans">Mga Van</a>
<a href="/sitrass/public/locations">Mga Lokasyon</a>
<a href="/sitrass/public/routes">Mga Ruta</a>
<a href="/sitrass/public/schedules">Mga Schedule</a>
<a href="/sitrass/public/payments">Mga Payment</a>
<a href="/sitrass/public/payments/methods">Mga Paraan ng Bayad</a>
<a href="/sitrass/public/auth/logout">Logout</a>
        </div>
        <div class="main-content">
            <div class="topbar">
                <h2 style="margin:0;"><?= htmlspecialchars($pageHeading ?? '') ?></h2>
                <div class="user-info"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?> (<?= htmlspecialchars($_SESSION['role'] ?? '') ?>)</div>
            </div>