<?php require __DIR__ . '/_customer_header.php'; ?>

<h2><?= t('heading_qr_booking') ?></h2>

<div class="ticket-stub">
    <div class="stub-label"><?= t('label_reference_code') ?></div>
    <div class="stub-code"><?= htmlspecialchars($reservation['reference_code']) ?></div>
</div>

<?php if ($qr['raw_token']): ?>
    <div class="card" style="text-align:center; padding:2rem;">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($qr['raw_token']) ?>" alt="<?= t('qr_alt_booking') ?>" style="margin-bottom:1rem;">
        <p style="font-size:0.9rem; color:var(--ocean); margin:0;"><?= t('qr_show_to_driver') ?></p>
    </div>
<?php else: ?>
    <div class="alert alert-error"><?= t('qr_display_error') ?></div>
<?php endif; ?>

<div class="card">
    <p><strong><?= t('th_date') ?>:</strong> <?= htmlspecialchars($booking['travel_date']) ?> @ <?= htmlspecialchars($booking['pickup_time']) ?></p>
    <p><strong><?= t('label_passenger_count') ?>:</strong> <?= (int)$booking['seats_booked'] ?></p>
    <p style="margin:0;"><strong><?= t('label_status') ?>:</strong> <span class="badge <?= $qr['status'] === 'active' ? 'badge-active' : 'badge-pending' ?>"><?= t($qr['status'] === 'active' ? 'status_active_qr' : 'status_inactive_qr') ?></span></p>
</div>

<a href="/sitrass/public/customer/myBookings" class="btn-link"><?= t('btn_back') ?></a>

<?php require __DIR__ . '/_customer_footer.php'; ?>
