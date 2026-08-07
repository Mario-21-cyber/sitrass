<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SITRASS Driver') ?></title>
    <link rel="stylesheet" href="/sitrass/public/css/style.css">
</head>
<body>
    <div style="background:var(--teal-dark); padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center;">
        <img src="/sitrass/public/img/logo.png" alt="SITRASS" style="height:40px;">
        <div style="color:var(--sand); font-size:0.9rem;">
            <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?> (Driver)
            &nbsp;|&nbsp;
            <a href="/sitrass/public/auth/logout" style="color:var(--amber);">Logout</a>
        </div>
    </div>
    <div style="max-width:900px; margin:2rem auto; padding:0 1rem;"></div>