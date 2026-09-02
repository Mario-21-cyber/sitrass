<?php require __DIR__ . '/_customer_header.php'; ?>

<h2><?= t('heading_booking_confirmed') ?></h2>

<div class="ticket-stub">
    <div class="stub-label"><?= t('label_reference_code') ?></div>
    <div class="stub-code"><?= htmlspecialchars($reservation['reference_code']) ?></div>
</div>

<div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.5rem;">
    <p><strong><?= t('label_passenger_count') ?>:</strong> <?= (int)$reservation['passenger_count'] ?></p>
    <p><strong><?= t('label_total_amount') ?>:</strong> ₱<?= htmlspecialchars($reservation['total_amount']) ?></p>
    <p><strong>Kailangang Deposit (<?= htmlspecialchars(rtrim(rtrim(number_format($reservation['deposit_percentage'], 2), '0'), '.')) ?>%):</strong> ₱<?= htmlspecialchars($reservation['deposit_required']) ?></p>
    <p><strong><?= t('label_status') ?>:</strong> <span class="badge badge-pending"><?= t('status_' . $reservation['status']) ?></span></p>
</div>

<p style="margin-top:1rem; font-size:0.9rem;">
    Bayaran ang deposit sa loob ng 2 oras para hindi ma-cancel ang reservation. (Payment system, susunod pa itong idadagdag.)
</p>

<a href="/sitrass/public/customer/myBookings" class="btn-link"><?= t('nav_my_bookings') ?></a>

<?php require __DIR__ . '/_customer_footer.php'; ?>
