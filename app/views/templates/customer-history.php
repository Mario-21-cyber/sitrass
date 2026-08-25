<?php require __DIR__ . '/_customer_header.php'; ?>

<h2><?= t('history_page_title') ?></h2>

<?php if (empty($reservations)): ?>
    <div class="empty-state"><?= t('history_empty') ?></div>
<?php else: ?>
    <?php foreach ($reservations as $r): ?>
        <div class="card list-card">
            <div>
                <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($r['reference_code']) ?></span><br>
                <span style="font-size:0.85rem;"><?= t('th_date') ?>: <?= htmlspecialchars($r['first_travel_date']) ?> &middot; <?= (int)$r['passenger_count'] ?> <?= t('unit_passengers') ?></span>
            </div>
            <span class="badge <?= $r['status'] === 'completed' ? 'badge-active' : 'badge-pending' ?>"><?= t('status_' . $r['status']) ?></span>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_customer_footer.php'; ?>