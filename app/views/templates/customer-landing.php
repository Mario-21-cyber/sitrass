<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand">Maligayang pagdating, <?= htmlspecialchars($_SESSION['full_name']) ?>!</div>
<div class="brand-sub">SITRASS Customer</div>

<a href="/sitrass/public/customer/search" class="btn" style="display:block; text-align:center; text-decoration:none;">Maghanap ng Biyahe</a>
<a href="/sitrass/public/customer/myBookings" class="btn-link">Aking Mga Booking</a>
<a href="/sitrass/public/auth/logout" class="btn-link">Logout</a>

<?php require __DIR__ . '/_auth_footer.php'; ?>