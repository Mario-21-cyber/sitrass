<?php require __DIR__ . '/_driver_header.php'; ?>

<h2><?= t('nav_my_trips') ?></h2>

<p><a href="/sitrass/public/driver/scanQr" class="btn" style="display:inline-block; width:auto; padding:0.5rem 1.2rem;"><?= t('nav_scan_qr') ?></a></p>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (empty($bookings)): ?>
    <div class="empty-state">Wala ka pang naka-assign na booking sa ngayon.</div>
<?php else: ?>
    <?php foreach ($bookings as $b): ?>
        <div class="card list-card">
            <div>
                <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($b['reference_code']) ?></span><br>
                <strong><?= htmlspecialchars($b['pickup_name']) ?> &rarr; <?= htmlspecialchars($b['dropoff_name']) ?></strong><br>
                <span style="font-size:0.85rem;"><?= htmlspecialchars($b['travel_date']) ?> @ <?= htmlspecialchars($b['pickup_time']) ?> &middot; <?= (int)$b['seats_booked'] ?> <?= t('unit_passengers') ?></span><br>
                <span style="font-size:0.85rem;">Customer: <?= htmlspecialchars($b['customer_name']) ?> (<?= htmlspecialchars($b['customer_phone']) ?>)</span>
            </div>
            <span class="badge <?= in_array($b['status'], ['accepted','en_route','completed']) ? 'badge-active' : 'badge-pending' ?>">
                <?= htmlspecialchars($b['status']) ?>
            </span>

            <div class="actions">
                <?php if ($b['status'] === 'pending'): ?>
                    <form method="POST" action="/sitrass/public/driver/accept" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                        <button type="submit" class="btn">Tanggapin</button>
                    </form>
                    <form method="POST" action="/sitrass/public/driver/reject" style="display:inline;" onsubmit="return confirm('Sigurado kang tanggihan ang booking na ito?');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                        <button type="submit" class="btn-danger" style="border:none; border-radius:6px; cursor:pointer;">Tanggihan</button>
                    </form>
                <?php elseif ($b['status'] === 'accepted'): ?>
                    <form method="POST" action="/sitrass/public/driver/startTrip" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                        <button type="submit" class="btn">Simulan ang Biyahe</button>
                    </form>
                    <a href="/sitrass/public/chat/open/<?= (int)$b['booking_id'] ?>">Chat</a>
                                <?php elseif ($b['status'] === 'en_route'): ?>
                    <form method="POST" action="/sitrass/public/driver/endTrip" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                        <button type="submit" class="btn"><?= t('btn_end_trip') ?></button>
                    </form>
                    <a href="/sitrass/public/driver/trackTrip/<?= (int)$b['booking_id'] ?>"><?= t('link_track_customer') ?></a>
                    <a href="/sitrass/public/chat/open/<?= (int)$b['booking_id'] ?>">Chat</a>
                <?php elseif ($b['status'] === 'completed'): ?>
                    <span style="font-size:0.85rem; color:var(--forest);"><?= t('driver_trip_completed_note') ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

const activeBookingId = <?= json_encode($activeEnRouteBookingId ?? null) ?>;
const driverId = <?= json_encode($driverIdForGps ?? null) ?>;

if (activeBookingId && driverId && navigator.geolocation) {
    function sendLocation() {
        navigator.geolocation.getCurrentPosition(function(position) {
            db.ref('driver_locations/' + driverId).set({
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                heading: position.coords.heading || 0,
                speed: position.coords.speed || 0,
                bookingId: activeBookingId,
                updatedAt: Date.now()
            });
        }, function(error) {
            console.warn('Hindi makuha ang GPS location:', error.message);
        }, { enableHighAccuracy: true, timeout: 10000 });
    }

    sendLocation();
    setInterval(sendLocation, 15000);
}
</script>

<?php require __DIR__ . '/_driver_footer.php'; ?>