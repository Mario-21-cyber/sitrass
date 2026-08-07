<?php require __DIR__ . '/_driver_header.php'; ?>

<h2>Mga Booking Ko</h2>

<p><a href="/sitrass/public/driver/scanQr" class="btn" style="display:inline-block; width:auto; padding:0.5rem 1.2rem; text-decoration:none;">I-verify ang QR Code</a></p>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (empty($bookings)): ?>
    <p>Wala ka pang naka-assign na booking sa ngayon.</p>
<?php else: ?>
    <?php foreach ($bookings as $b): ?>
        <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem; margin-bottom:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:start;">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($b['reference_code']) ?></span><br>
                    <strong><?= htmlspecialchars($b['pickup_name']) ?> &rarr; <?= htmlspecialchars($b['dropoff_name']) ?></strong><br>
                    <span style="font-size:0.85rem;"><?= htmlspecialchars($b['travel_date']) ?> @ <?= htmlspecialchars($b['pickup_time']) ?> &middot; <?= (int)$b['seats_booked'] ?> pasahero</span><br>
                    <span style="font-size:0.85rem;">Customer: <?= htmlspecialchars($b['customer_name']) ?> (<?= htmlspecialchars($b['customer_phone']) ?>)</span>
                </div>
                <span class="badge <?= in_array($b['status'], ['accepted','en_route','completed']) ? 'badge-active' : 'badge-pending' ?>">
                    <?= htmlspecialchars($b['status']) ?>
                </span>
            </div>

            <div style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid var(--border);">
                <?php if ($b['status'] === 'pending'): ?>
                    <form method="POST" action="/sitrass/public/driver/accept" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.4rem 1rem; font-size:0.85rem;">Tanggapin</button>
                    </form>
                    <form method="POST" action="/sitrass/public/driver/reject" style="display:inline;" onsubmit="return confirm('Sigurado kang tanggihan ang booking na ito?');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                        <button type="submit" style="width:auto; padding:0.4rem 1rem; font-size:0.85rem; background:var(--danger); color:#fff; border:none; border-radius:4px; cursor:pointer;">Tanggihan</button>
                    </form>
                <?php elseif ($b['status'] === 'accepted'): ?>
                    <form method="POST" action="/sitrass/public/driver/startTrip" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.4rem 1rem; font-size:0.85rem;">Simulan ang Biyahe</button>
                    </form>
                <?php elseif ($b['status'] === 'en_route'): ?>
                    <form method="POST" action="/sitrass/public/driver/endTrip" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.4rem 1rem; font-size:0.85rem;">Tapusin ang Biyahe</button>
                    </form>
                <?php elseif ($b['status'] === 'completed'): ?>
                    <span style="font-size:0.85rem; color:var(--forest);">Tapos na ang biyaheng ito.</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_driver_footer.php'; ?>