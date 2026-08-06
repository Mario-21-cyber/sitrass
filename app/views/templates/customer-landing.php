<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand">Maligayang pagdating, <?= htmlspecialchars($_SESSION['full_name']) ?>!</div>
<div class="brand-sub">SITRASS Customer</div>

<p style="text-align:center; margin: 1.5rem 0;">
    Malapit nang magamit ang pag-book ng biyahe dito. Balikan mo ito paglaon.
</p>

<a href="/sitrass/public/auth/logout" class="btn-link">Logout</a>

<?php require __DIR__ . '/_auth_footer.php'; ?>