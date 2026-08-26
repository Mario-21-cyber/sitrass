<?php require __DIR__ . '/_driver_header.php'; ?>



<?php if (empty($bookings)): ?>
    <div class="empty-state"><?= t('driver_history_empty') ?></div>
<?php else: ?>
    <?php foreach ($bookings as $b): ?>
        <div class="card list-card">
            <div>
                <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($b['reference_code']) ?></span><br>
                <strong><?= htmlspecialchars($b['pickup_name']) ?> &rarr; <?= htmlspecialchars($b['dropoff_name']) ?></strong><br>
                <span style="font-size:0.85rem;"><?= htmlspecialchars($b['travel_date']) ?> @ <?= htmlspecialchars($b['pickup_time']) ?> &middot; <?= (int)$b['seats_booked'] ?> <?= t('unit_passengers') ?></span><br>
                <span style="font-size:0.85rem;">Customer: <?= htmlspecialchars($b['customer_name']) ?></span>
            </div>
            <span class="badge <?= $b['status'] === 'completed' ? 'badge-active' : 'badge-pending' ?>"><?= t('status_' . $b['status']) ?></span>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_driver_footer.php'; ?>