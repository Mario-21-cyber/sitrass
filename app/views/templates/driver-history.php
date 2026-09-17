<?php require __DIR__ . '/_driver_header.php'; ?>



<?php if (empty($bookings) && empty($rentals)): ?>
    <div class="empty-state"><?= t('driver_history_empty') ?></div>
<?php else: ?>
    <?php if (!empty($bookings)): ?>
        <div class="section-heading" style="margin-top:0;">
            <h3><?= t('tab_shared_trips') ?></h3>
        </div>
        <?php foreach ($bookings as $b): ?>
            <div class="card list-card">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($b['reference_code']) ?></span><br>
                    <strong><?= htmlspecialchars($b['pickup_name']) ?> &rarr; <?= htmlspecialchars($b['dropoff_name']) ?></strong><br>
                    <span style="font-size:0.85rem;"><?= !empty($b['travel_date']) ? htmlspecialchars(date('F j, Y', strtotime($b['travel_date']))) : '—' ?> @ <?= !empty($b['pickup_time']) ? htmlspecialchars(date('g:i A', strtotime($b['pickup_time']))) : '—' ?> &middot; <?= (int)$b['seats_booked'] ?> <?= t('unit_passengers') ?></span><br>
                    <span style="font-size:0.85rem;"><?= t('label_customer') ?>: <?= htmlspecialchars($b['customer_name']) ?></span>
                </div>
                <span class="badge <?= $b['status'] === 'completed' ? 'badge-active' : 'badge-pending' ?>"><?= t('status_' . $b['status']) ?></span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($rentals)): ?>
        <div class="section-heading">
            <h3><?= t('tab_van_rentals') ?></h3>
        </div>
        <?php foreach ($rentals as $r): ?>
            <div class="card list-card">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($r['reference_code'] ?? '—') ?></span><br>
                    <strong><?= htmlspecialchars($r['make'] . ' ' . $r['model']) ?> (<?= htmlspecialchars($r['plate_number']) ?>)</strong><br>
                    <span style="font-size:0.85rem;">
                        <?= !empty($r['start_date']) ? htmlspecialchars(date('F j, Y', strtotime($r['start_date']))) : '—' ?> &rarr;
                        <?= !empty($r['end_date']) ? htmlspecialchars(date('F j, Y', strtotime($r['end_date']))) : '—' ?>
                        &middot; <?= (int)$r['days'] ?> <?= t('rent_th_days') ?>
                    </span><br>
                    <span style="font-size:0.85rem;"><?= t('label_customer') ?>: <?= htmlspecialchars($r['customer_name']) ?></span>
                </div>
                <span class="badge <?= $r['status'] === 'completed' ? 'badge-active' : 'badge-pending' ?>">
                    <?= $r['status'] === 'active' ? t('rent_status_active_lbl') : t('status_' . $r['status']) ?>
                </span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/_driver_footer.php'; ?>
