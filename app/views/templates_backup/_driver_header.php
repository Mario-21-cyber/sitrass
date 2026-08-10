<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SITRASS Driver') ?></title>
    <link rel="stylesheet" href="/sitrass/public/css/style.css">
</head>
<body>
    <div class="top-nav">
        <img src="/sitrass/public/img/logo.png" alt="SITRASS" class="logo">
        <div class="user-info">
            <a href="/sitrass/public/profile/edit"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></a> (Driver)
            &nbsp;|&nbsp;
            <a href="/sitrass/public/auth/logout" style="color:var(--amber-light);">Logout</a>
        </div>
    </div>
    <div class="page-wrap">