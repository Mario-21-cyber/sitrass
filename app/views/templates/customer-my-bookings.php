<?php require __DIR__ . '/_customer_header.php'; ?>

<h2>Aking Mga Booking</h2>

<?php if (empty($reservations)): ?>
    <p>Wala ka pang booking. <a href="/sitrass/public/customer/search">Maghanap ng biyahe</a>.</p>
<?php else: ?>
    <?php foreach ($reservations as $r): ?>
        <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem; margin-bottom:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($r['reference_code']) ?></span><br>
                    <span style="font-size:0.85rem;">Petsa: <?= htmlspecialchars($r['first_travel_date']) ?> &middot; <?= (int)$r['passenger_count'] ?> pasahero</span>
                </div>
                <div style="text-align:right;">
                    <span class="badge <?= $r['status'] === 'confirmed' ? 'badge-active' : 'badge-pending' ?>"><?= htmlspecialchars($r['status']) ?></span><br>
                    <span style="font-size:0.85rem;">₱<?= htmlspecialchars($r['total_amount']) ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_customer_footer.php'; ?>