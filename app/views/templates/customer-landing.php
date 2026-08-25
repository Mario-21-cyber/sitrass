<?php require __DIR__ . '/_customer_header.php'; ?>

<p class="text-muted" style="margin-bottom:var(--space-4);"><?= sprintf(t('welcome_message'), htmlspecialchars($_SESSION['full_name'])) ?></p>

<?php if ($activeBooking): ?>
    <div class="eta-banner" id="etaBanner" style="display:none;">
        <div>
            <div class="eta-label"><?= t('eta_distance_label') ?></div>
            <div id="etaDistance" style="font-size:1rem;">—</div>
        </div>
        <div>
            <div class="eta-label"><?= t('eta_time_label') ?></div>
            <div class="eta-value" id="etaTime">—</div>
        </div>
    </div>
<?php endif; ?>

<div id="dashMap" style="height:520px; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow-sm); margin-bottom:1rem;"></div>
<p id="dashStatusText" class="text-sm text-muted" style="margin:0 0 1.5rem;"><?= t('map_own_location') ?></p>

<?php if ($activeBooking): ?>
    <div class="card" style="margin-top:1rem;">
        <div class="form-section-title" style="margin-bottom:0.75rem; border:none; padding:0;"><?= t('dashboard_active_title') ?></div>
        <p style="margin:0 0 0.25rem;"><strong><?= htmlspecialchars($activeBooking['pickup_name']) ?> &rarr; <?= htmlspecialchars($activeBooking['dropoff_name']) ?></strong></p>
        <p class="text-sm text-muted" style="margin:0 0 0.75rem;"><?= htmlspecialchars($activeBooking['reference_code']) ?> &middot; <?= htmlspecialchars($activeBooking['plate_number']) ?></p>
        <?php if ($activeBooking['status'] === 'accepted'): ?>
            <p class="text-sm" style="color:var(--slate); margin-bottom:0.75rem;"><?= t('note_driver_assigned_waiting') ?></p>
        <?php endif; ?>

        <?php if ($activeBooking['driver_id']): ?>
            <div id="driverInfoPanel" style="display:none; background:var(--slate-bg); border-radius:8px; padding:0.75rem 1rem; margin-bottom:0.75rem;">
                <div style="font-weight:600;"><?= htmlspecialchars($activeBooking['driver_name']) ?></div>
                <div class="text-sm text-muted"><?= t('driver_info_phone_label') ?>: <?= htmlspecialchars($activeBooking['driver_phone'] ?? '—') ?></div>
            </div>
            <button type="button" class="btn-ghost" onclick="var p=document.getElementById('driverInfoPanel'); p.style.display = p.style.display === 'none' ? 'block' : 'none';" style="margin-right:0.5rem;"><?= t('btn_driver_info') ?></button>
        <?php endif; ?>

        <a href="/sitrass/public/chat/open/<?= (int)$activeBooking['booking_id'] ?>" class="btn" style="width:auto; padding:0.6rem 1.4rem; text-decoration:none; display:inline-block;"><?= t('btn_chat') ?></a>
    </div>
<?php elseif ($unratedBooking): ?>
    <div class="card" style="margin-top:1rem; text-align:center;">
        <div class="form-section-title" style="margin-bottom:0.5rem; border:none; padding:0;"><?= t('dashboard_unrated_title') ?></div>
        <p class="text-sm text-muted" style="margin-bottom:1rem;"><?= t('dashboard_unrated_desc') ?></p>
        <a href="/sitrass/public/customer/rate/<?= (int)$unratedBooking['booking_id'] ?>" class="btn" style="width:auto; padding:0.6rem 1.4rem; text-decoration:none; display:inline-block;"><?= t('btn_rate') ?></a>
    </div>
<?php else: ?>
    <div class="card" style="margin-top:1rem; text-align:center;">
        <div class="form-section-title" style="margin-bottom:0.5rem; border:none; padding:0;"><?= t('dashboard_no_trip_title') ?></div>
        <p class="text-sm text-muted" style="margin-bottom:1rem;"><?= t('dashboard_no_trip_desc') ?></p>
        <a href="/sitrass/public/customer/search" class="btn" style="width:auto; padding:0.6rem 1.4rem; text-decoration:none; display:inline-block;"><?= t('nav_search') ?></a>
    </div>
<?php endif; ?>

<script>
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

const map = L.map('dashMap').setView([12.4, 122.56], 11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

function personIconHtml(color) {
    return '<div class="map-pulse-marker" style="width:26px;height:26px;">' +
        '<div class="pulse-ring" style="background:transparent;"></div>' +
        '<div style="width:26px;height:26px;background:' + color + ';border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>' +
        '</div></div>';
}
function vanIconHtml(headingDeg) {
    const rot = headingDeg || 0;
    return '<div class="map-pulse-marker" style="width:30px;height:30px;">' +
        '<div class="pulse-ring" style="background:transparent;"></div>' +
        '<div style="width:30px;height:30px;background:var(--teal-dark);border-radius:50%;border:3px solid var(--amber-light);display:flex;align-items:center;justify-content:center;transform:rotate(' + rot + 'deg);">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 L19 21 L12 17 L5 21 Z"/></svg>' +
        '</div></div>';
}

let myMarker = null;
let vanMarker = null;
let routeLine = null;
let myPos = null;
let vanPos = null;
let mapCentered = false;

const bookingId = <?= json_encode($activeBooking['booking_id'] ?? null) ?>;
const driverId = <?= json_encode($activeBooking['driver_id'] ?? null) ?>;
const bookingStatus = <?= json_encode($activeBooking['status'] ?? null) ?>;

const statusText = document.getElementById('dashStatusText');

function haversineKm(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) ** 2 + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLng/2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function updateRouteAndEta(speedKph) {
    if (!vanPos || !myPos) return;
    if (routeLine) map.removeLayer(routeLine);
    routeLine = L.polyline([vanPos, myPos], { color: '#4285F4', weight: 5, opacity: 0.85 }).addTo(map);

    const distKm = haversineKm(vanPos[0], vanPos[1], myPos[0], myPos[1]);
    const etaDistanceEl = document.getElementById('etaDistance');
    const etaTimeEl = document.getElementById('etaTime');
    if (etaDistanceEl) etaDistanceEl.textContent = distKm.toFixed(2) + ' km';

    const effectiveSpeed = (speedKph && speedKph > 3) ? speedKph : 25;
    const etaMinutes = Math.max(1, Math.round((distKm / effectiveSpeed) * 60));
    if (etaTimeEl) etaTimeEl.textContent = '~' + etaMinutes + ' min';
    const etaBanner = document.getElementById('etaBanner');
    if (etaBanner) etaBanner.style.display = 'flex';
}

function sendMyLocation() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function(position) {
        myPos = [position.coords.latitude, position.coords.longitude];

        if (!myMarker) {
            myMarker = L.marker(myPos, { icon: L.divIcon({ className: '', html: personIconHtml('var(--forest)'), iconSize: [26, 26] }) }).addTo(map);
        } else {
            myMarker.setLatLng(myPos);
        }

        if (!mapCentered) {
            map.setView(myPos, 13);
            mapCentered = true;
        }

        if (bookingId && bookingStatus === 'en_route') {
            db.ref('customer_locations/' + bookingId).set({
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                updatedAt: Date.now()
            });
        }

        if (vanPos) updateRouteAndEta(null);
    }, function() {
        // walang permission - iwanan ang default view
    }, { enableHighAccuracy: true, timeout: 10000 });
}
sendMyLocation();
if (bookingId && bookingStatus === 'en_route') {
    setInterval(sendMyLocation, 15000);
}

if (bookingId && driverId) {
    statusText.textContent = <?= json_encode(t('map_waiting_driver')) ?>;
    db.ref('driver_locations/' + driverId).on('value', function(snapshot) {
        const data = snapshot.val();
        if (!data) return;

        vanPos = [data.lat, data.lng];
        const headingDeg = data.heading || 0;
        const speedKph = (data.speed || 0) * 3.6;
        const ageSeconds = Math.round((Date.now() - data.updatedAt) / 1000);

        if (!vanMarker) {
            vanMarker = L.marker(vanPos, { icon: L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }) }).addTo(map);
        } else {
            vanMarker.setLatLng(vanPos);
            vanMarker.setIcon(L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }));
        }

        statusText.textContent = (ageSeconds > 90)
            ? (<?= json_encode(t('map_stale_prefix')) ?> + ' ' + ageSeconds + 's')
            : (<?= json_encode(t('map_live_prefix')) ?> + ' ' + ageSeconds + ' ' + <?= json_encode(t('map_seconds_ago')) ?>);

        updateRouteAndEta(speedKph);

        if (myPos) {
            map.fitBounds([vanPos, myPos], { padding: [40, 40] });
        } else {
            map.setView(vanPos, 13);
        }
    });
}
</script>

<?php require __DIR__ . '/_customer_footer.php'; ?>