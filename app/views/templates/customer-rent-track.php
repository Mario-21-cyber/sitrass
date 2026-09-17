<?php require __DIR__ . '/_customer_header.php'; ?>

<div class="card">
    <p><strong><?= t('label_reference') ?>:</strong> <?= htmlspecialchars($rental['reference_code'] ?? '—') ?></p>
    <p><strong><?= t('label_van') ?>:</strong> <?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?> (<?= htmlspecialchars($rental['plate_number']) ?>)</p>
    <p style="margin:0;"><strong><?= t('label_driver') ?>:</strong> <?= htmlspecialchars($rental['driver_name'] ?? t('no_driver_yet')) ?>
        <?php if (!empty($rental['driver_phone'])): ?> &middot; <?= htmlspecialchars($rental['driver_phone']) ?><?php endif; ?></p>
</div>

<div class="eta-banner" id="etaBanner" style="display:none;">
    <div style="flex-basis:100%; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.05em; opacity:0.85;">
        <?= t('track_eta_dest_label') ?>
    </div>
    <div>
        <div class="eta-label"><?= t('eta_distance_label') ?></div>
        <div id="etaDistance" style="font-size:1rem;">—</div>
    </div>
    <div>
        <div class="eta-label"><?= t('eta_time_label') ?></div>
        <div class="eta-value" id="etaTime">—</div>
    </div>
</div>

<div class="alert alert-success" id="arrivedBanner" style="display:none;">
    <strong><?= t('track_arrived') ?></strong>
</div>

<div id="trackMap" style="height:400px; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow-sm); margin-bottom:1rem;"></div>

<p id="statusText" class="text-sm text-muted"><?= t('track_waiting_location') ?></p>

<div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
    <a href="/sitrass/public/messages/viewRental/<?= (int)$rental['rental_id'] ?>" class="btn" style="width:auto; padding:0.6rem 1.4rem;"><?= t('btn_chat') ?></a>
    <a href="/sitrass/public/customer/myBookings?tab=rentals" class="btn-link"><?= t('btn_back') ?></a>
</div>

<script>
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

const rentalId = <?= json_encode((int)$rental['rental_id']) ?>;
const driverId = <?= json_encode($rental['driver_id'] ?? null) ?>;
const pickupLat = <?= json_encode((float)($rental['pickup_lat'] ?? 0)) ?>;
const pickupLng = <?= json_encode((float)($rental['pickup_lng'] ?? 0)) ?>;
const dropoffLat = <?= json_encode((float)($rental['dropoff_lat'] ?? 0)) ?>;
const dropoffLng = <?= json_encode((float)($rental['dropoff_lng'] ?? 0)) ?>;

const map = L.map('trackMap').setView([pickupLat, pickupLng], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// --- Icons: van (may direksyon) at "Pupuntahan" na pin ---
function vanIconHtml(headingDeg) {
    const rot = headingDeg || 0;
    return '<div class="map-pulse-marker" style="width:34px;height:34px;">' +
        '<div class="pulse-ring" style="background:transparent;"></div>' +
        '<div style="width:34px;height:34px;background:#1A73E8;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(26,115,232,0.5);display:flex;align-items:center;justify-content:center;transform:rotate(' + rot + 'deg);">' +
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M12 2 L19 21 L12 17 L5 21 Z"/></svg>' +
        '</div></div>';
}
function destIconHtml() {
    return '<div style="display:flex;flex-direction:column;align-items:center;filter:drop-shadow(0 2px 3px rgba(0,0,0,0.35));">' +
        '<div style="background:#C41E24; color:#fff; font-size:0.7rem; font-weight:700; padding:0.25rem 0.5rem; border-radius:6px; white-space:nowrap; margin-bottom:2px;">' + <?= json_encode(t('track_dest_label')) ?> + '</div>' +
        '<svg width="20" height="26" viewBox="0 0 24 32"><path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 20 12 20s12-11 12-20C24 5.4 18.6 0 12 0z" fill="#C41E24"/><circle cx="12" cy="12" r="4.5" fill="white"/></svg>' +
        '</div>';
}
function haversineKm(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) ** 2 + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLng/2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

let vanMarker = null;
let routeLine = null;
let vanPos = null;
let tripEnded = false;

// --- Marker ng destinasyon (Pupuntahan) at pickup point ---
if (dropoffLat && dropoffLng) {
    L.marker([dropoffLat, dropoffLng], {
        icon: L.divIcon({ className: '', html: destIconHtml(), iconSize: [26, 62], iconAnchor: [13, 60] })
    }).addTo(map);
}
if (pickupLat && pickupLng) {
    L.marker([pickupLat, pickupLng], {
        icon: L.divIcon({ className: '', html: '<div style="background:#2D6A34; width:12px; height:12px; border-radius:50%; border:2px solid white;"></div>', iconSize: [16, 16] })
    }).addTo(map).bindPopup(<?= json_encode(t('rent_pickup_location')) ?>);
}

const statusText = document.getElementById('statusText');

// --- Ruta ng van papunta sa destinasyon (totoong kalsada via OSRM) ---
function drawStraightFallback(fromPos) {
    if (tripEnded) return;
    if (routeLine) map.removeLayer(routeLine);
    routeLine = L.polyline([fromPos, [dropoffLat, dropoffLng]], { color: '#2D6A34', weight: 4, dashArray: '8 8', opacity: 0.8 }).addTo(map);
}
function updateRouteToDestination(fromPos) {
    if (!fromPos || !dropoffLat || !dropoffLng) return;
    fetch('https://router.project-osrm.org/route/v1/driving/' + fromPos[1] + ',' + fromPos[0] + ';' + dropoffLng + ',' + dropoffLat + '?overview=full&geometries=geojson')
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (tripEnded) return;
            if (json && json.code === 'Ok' && json.routes && json.routes[0]) {
                const route = json.routes[0];
                const coords = route.geometry.coordinates.map(function (c) { return [c[1], c[0]]; });
                if (routeLine) map.removeLayer(routeLine);
                routeLine = L.polyline(coords, { color: '#2D6A34', weight: 4, opacity: 0.85 }).addTo(map);
                document.getElementById('etaDistance').textContent = (route.distance / 1000).toFixed(2) + ' km';
                document.getElementById('etaTime').textContent = '~' + Math.max(1, Math.round(route.duration / 60)) + ' min';
                document.getElementById('etaBanner').style.display = 'flex';
            } else {
                drawStraightFallback(fromPos);
            }
        })
        .catch(function () { drawStraightFallback(fromPos); });
}
function checkArrived() {
    if (vanPos && dropoffLat && dropoffLng && haversineKm(vanPos[0], vanPos[1], dropoffLat, dropoffLng) < 0.1) {
        document.getElementById('arrivedBanner').style.display = 'block';
    }
}

// --- Makinig sa lokasyon ng van (GPS ng driver) ---
if (driverId) {
    db.ref('driver_locations/' + driverId).on('value', function(snapshot) {
        const data = snapshot.val();
        if (!data) {
            statusText.textContent = <?= json_encode(t('track_waiting_driver_location')) ?>;
            return;
        }

        vanPos = [data.lat, data.lng];
        const headingDeg = data.heading || 0;
        const ageSeconds = (Date.now() - data.updatedAt) / 1000;

        if (!vanMarker) {
            vanMarker = L.marker(vanPos, { icon: L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }) }).addTo(map).bindPopup('Ang van mo');
            map.setView(vanPos, 14);
        } else {
            vanMarker.setLatLng(vanPos);
            vanMarker.setIcon(L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }));
        }

        statusText.textContent = ageSeconds > 90
            ? 'Huling nakita: ' + Math.round(ageSeconds) + 's ang nakalipas (baka naka-off ang GPS ng driver).'
            : 'Live - na-update ' + Math.round(ageSeconds) + 's ang nakalipas.';

        updateRouteToDestination(vanPos);
        checkArrived();
    });
} else {
    statusText.textContent = 'Wala pang naka-assign na driver sa rental na ito.';
}

// --- Kapag tapos na ang rental: ihinto ang tracking at bumalik ---
function endTracking() {
    if (tripEnded) return;
    tripEnded = true;
    if (routeLine) map.removeLayer(routeLine);
    document.getElementById('etaBanner').style.display = 'none';
    statusText.innerHTML = '<strong>' + <?= json_encode(t('track_trip_ended')) ?> + '</strong><br>' + <?= json_encode(t('track_trip_ended_note')) ?>;
    setTimeout(function () { window.location.href = '/sitrass/public/customer/myBookings?tab=rentals'; }, 5000);
}

setInterval(function () {
    fetch('/sitrass/public/customer/rentalStatus/' + rentalId)
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d && d.status && d.status !== 'active') { endTracking(); }
        })
        .catch(function () {});
}, 20000);
</script>

<?php require __DIR__ . '/_customer_footer.php'; ?>