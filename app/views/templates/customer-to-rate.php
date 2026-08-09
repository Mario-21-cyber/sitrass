<?php require __DIR__ . '/_customer_header.php'; ?>

<h2>Mga Biyaheng Puwede Mo Nang I-rate</h2>

<?php if (empty($trips)): ?>
    <p>Wala kang biyaheng naghihintay ng rating sa ngayon.</p>
<?php else: ?>
    <?php foreach ($trips as $t): ?>
        <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem; margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($t['reference_code']) ?></span><br>
                <strong><?= htmlspecialchars($t['pickup_name']) ?> &rarr; <?= htmlspecialchars($t['dropoff_name']) ?></strong><br>
                <span style="font-size:0.85rem;"><?= htmlspecialchars($t['travel_date']) ?> &middot; <?= htmlspecialchars($t['plate_number']) ?>
                    <?php if ($t['driver_name']): ?> &middot; Driver: <?= htmlspecialchars($t['driver_name']) ?><?php endif; ?>
                </span>
            </div>
            <a href="/sitrass/public/customer/rate/<?= (int)$t['booking_id'] ?>" class="btn" style="display:inline-block; width:auto; padding:0.5rem 1.2rem; text-decoration:none;">I-rate</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_customer_footer.php'; ?>