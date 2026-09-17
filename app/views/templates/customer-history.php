<?php require __DIR__ . '/_customer_header.php'; ?>



<?php if (empty($reservations) && empty($rentals)): ?>
    <div class="empty-state"><?= t('history_empty') ?></div>
<?php else: ?>
    <?php if (!empty($reservations)): ?>
        <div class="section-heading" style="margin-top:0;">
            <h3><?= t('tab_shared_trips') ?></h3>
        </div>
        <?php foreach ($reservations as $r): ?>
            <?php $histStatus = $r['history_status'] ?? $r['status']; ?>
            <div class="card list-card">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($r['reference_code']) ?></span><br>
                    <span style="font-size:0.85rem;">
                        <?= t('th_date') ?>:
                        <?= !empty($r['first_travel_date']) ? htmlspecialchars(date('F j, Y', strtotime($r['first_travel_date']))) : '—' ?>
                        <?php if (!empty($r['first_pickup_time'])): ?>
                            &middot; <?= htmlspecialchars(date('g:i A', strtotime($r['first_pickup_time']))) ?>
                        <?php endif; ?>
                        &middot; <?= (int)$r['passenger_count'] ?> <?= t('unit_passengers') ?>
                    </span>
                </div>
                <span class="badge <?= $histStatus === 'completed' ? 'badge-active' : 'badge-pending' ?>"><?= t('status_' . $histStatus) ?></span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($rentals)): ?>
        <div class="section-heading">
            <h3><?= t('tab_van_rentals') ?></h3>
        </div>
        <?php foreach ($rentals as $r): ?>
            <?php $rentStatus = $r['status']; ?>
            <div class="card list-card">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($r['reference_code'] ?? '—') ?></span><br>
                    <span style="font-size:0.85rem;">
                        <?= t('th_date') ?>:
                        <?= !empty($r['start_date']) ? htmlspecialchars(date('F j, Y', strtotime($r['start_date']))) : '—' ?>
                        <?php if (!empty($r['end_date']) && $r['end_date'] !== $r['start_date']): ?>
                            &rarr; <?= htmlspecialchars(date('F j, Y', strtotime($r['end_date']))) ?>
                        <?php endif; ?>
                        &middot; <?= (int)($r['days'] ?? 1) ?> <?= t('rent_th_days') ?>
                    </span>
                </div>
                <span class="badge <?= $rentStatus === 'completed' ? 'badge-active' : ($rentStatus === 'cancelled' ? 'badge-danger' : 'badge-pending') ?>">
                    <?= $rentStatus === 'active' ? t('rent_status_active_lbl') : t('status_' . $rentStatus) ?>
                </span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/_customer_footer.php'; ?>