<?php require __DIR__ . '/_customer_header.php'; ?>

<h2><?= t('rent_qr_title') ?></h2>

<div class="ticket-stub">
    <div class="stub-label"><?= t('rent_th_ref') ?></div>
    <div class="stub-code"><?= htmlspecialchars($rental['reference_code'] ?? '—') ?></div>
</div>

<div class="card" style="text-align:center; padding:2rem;">
    <div id="rentalQrContainer" style="display:inline-block; margin-bottom:1rem; background:#fff; padding:10px; border-radius:8px;">
        <img id="rentalQrImg" 
             src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($rental['reference_code'] ?? '') ?>" 
             alt="Rental QR" 
             style="width:250px; height:250px; display:block;"
             onerror="this.onerror=null; this.src='https://chart.googleapis.com/chart?cht=qr&chs=250x250&chl=<?= urlencode($rental['reference_code'] ?? '') ?>';">
    </div>
    <p style="font-size:0.9rem; color:var(--ocean); margin:0;"><?= t('rent_qr_note') ?></p>
</div>

<div class="card">
    <p><strong><?= t('rent_th_van') ?>:</strong> <?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?> (<?= htmlspecialchars($rental['plate_number']) ?>)</p>
    <?php if (!empty($rental['route_label'])): ?>
        <p><strong><?= t('rent_route') ?>:</strong> <?= htmlspecialchars($rental['route_label']) ?></p>
    <?php endif; ?>
    <p><strong><?= t('rent_th_dates') ?>:</strong> <?= htmlspecialchars($rental['start_date']) ?> &rarr; <?= htmlspecialchars($rental['end_date']) ?> (<?= (int)$rental['days'] ?> <?= t('rent_th_days') ?>)</p>
    <?php if (!empty($rental['pickup_name'])): ?>
        <p><strong><?= t('rent_pickup_location') ?>:</strong> <?= htmlspecialchars($rental['pickup_name']) ?></p>
    <?php endif; ?>
    <p style="margin:0;"><strong><?= t('label_status') ?>:</strong>
        <span class="badge <?= $rental['status'] === 'active' ? 'badge-active' : 'badge-pending' ?>">
            <?= t($rental['status'] === 'active' ? 'rent_status_active_lbl' : 'status_confirmed') ?>
        </span>
    </p>
</div>

<a href="/sitrass/public/customer/myBookings?tab=rentals" class="btn-link"><?= t('btn_back') ?></a>

<?php require __DIR__ . '/_customer_footer.php'; ?>