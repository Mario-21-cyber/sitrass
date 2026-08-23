<?php require __DIR__ . '/_customer_header.php'; ?>

<h2><?= t('rate_list_title') ?></h2>

<?php if (empty($trips)): ?>
    <div class="empty-state">Wala kang biyaheng naghihintay ng rating sa ngayon.</div>
<?php else: ?>
    <?php foreach ($trips as $t): ?>
        <div class="card list-card">
            <div>
                <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($t['reference_code']) ?></span><br>
                <strong><?= htmlspecialchars($t['pickup_name']) ?> &rarr; <?= htmlspecialchars($t['dropoff_name']) ?></strong><br>
                <span style="font-size:0.85rem;"><?= htmlspecialchars($t['travel_date']) ?> &middot; <?= htmlspecialchars($t['plate_number']) ?>
                    <?php if ($t['driver_name']): ?> &middot; Driver: <?= htmlspecialchars($t['driver_name']) ?><?php endif; ?>
                </span>
            </div>
            <a href="/sitrass/public/customer/rate/<?= (int)$t['booking_id'] ?>" class="btn" style="display:inline-block; width:auto; padding:0.5rem 1.2rem;"><?= t('btn_rate') ?></a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_customer_footer.php'; ?>