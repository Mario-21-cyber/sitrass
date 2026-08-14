<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SITRASS') ?></title>
    <link rel="stylesheet" href="/sitrass/public/css/style.css">
</head>
<body>
    <div style="position:fixed; top:1rem; right:1rem; z-index:10; background:var(--white); border:1px solid var(--border); border-radius:100px; padding:0.3rem; box-shadow:var(--shadow-sm); display:flex; gap:0.2rem;">
        <a href="/sitrass/public/lang/set/tl" style="padding:0.3rem 0.7rem; border-radius:100px; font-size:0.78rem; text-decoration:none; <?= Lang::current() === 'tl' ? 'background:var(--teal-dark); color:#fff;' : 'color:var(--slate);' ?>">TL</a>
        <a href="/sitrass/public/lang/set/en" style="padding:0.3rem 0.7rem; border-radius:100px; font-size:0.78rem; text-decoration:none; <?= Lang::current() === 'en' ? 'background:var(--teal-dark); color:#fff;' : 'color:var(--slate);' ?>">EN</a>
    </div>
    <div class="auth-wrapper">
        <div class="auth-card">
            <img src="/sitrass/public/img/logo.png" alt="SITRASS logo" class="logo">