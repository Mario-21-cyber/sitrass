<?php require __DIR__ . '/_driver_header.php'; ?>

<h2>Subaybayan ang Customer</h2>

<div class="card">
    <p><strong>Reference:</strong> <?= htmlspecialchars($booking['reference_code']) ?></p>
    <p><strong>Customer:</strong> <?= htmlspecialchars($booking['customer_name']) ?> (<?= htmlspecialchars($booking['customer_phone']) ?>)</p>
    <p style="margin:0;"><strong>Van:</strong> <?= htmlspecialchars($booking['plate_number']) ?></p>
</div>

<div class="eta-banner" id="etaBanner" style="display:none;">
    <div>
        <div class="eta-label">Tinatayang Layo</div>
        <div id="etaDistance" style="font-size:1rem;">—</div>
    </div>
    <div>
        <div class="eta-label">Tinatayang Oras ng Pagdating (ETA)</div>
        <div class="eta-value" id="etaTime">—</div>
    </div>
</div>

<div id="trackMap" style="height:400px; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow-sm); margin-bottom:1rem;"></div>

<p id="statusText" class="text-sm text-muted">Hinihintay ang datos ng lokasyon...</p>

<a href="/sitrass/public/driver/dashboard" class="btn-link">Bumalik</a>

<script>
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

const bookingId = <?= json_encode($booking['booking_id']) ?>;
const driverId = <?= json_encode($driverId) ?>;
const pickupLat = <?= json_encode((float)$booking['pickup_lat']) ?>;
const pickupLng = <?= json_encode((float)$booking['pickup_lng']) ?>;

const map = L.map('trackMap').setView([pickupLat, pickupLng], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// --- Icons ---
function vanIconHtml(headingDeg) {
    const rot = headingDeg || 0;
    return '<div class="map-pulse-marker" style="width:30px;height:30px;">' +
        '<div class="pulse-ring" style="background:transparent;"></div>' +
        '<div style="width:30px;height:30px;background:var(--teal-dark);border-radius:50%;border:3px solid var(--amber-light);display:flex;align-items:center;justify-content:center;transform:rotate(' + rot + 'deg);">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 L19 21 L12 17 L5 21 Z"/></svg>' +
        '</div></div>';
}
function personIconHtml() {
    return '<div class="map-pulse-marker" style="width:26px;height:26px;">' +
        '<div class="pulse-ring" style="background:transparent;"></div>' +
        '<div style="width:26px;height:26px;background:var(--forest);border-radius:50%;border:3px solid white;display:flex;align-items:center;justify-content:center;">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>' +
        '</div></div>';
}

let vanMarker = null;
let customerMarker = null;
let routeLine = null;
let vanPos = null;
let customerPos = null;

function haversineKm(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) ** 2 + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLng/2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function updateRouteAndEta(speedKph) {
    if (!vanPos || !customerPos) return;

    if (routeLine) map.removeLayer(routeLine);
    routeLine = L.polyline([vanPos, customerPos], {
        color: '#C41E24', weight: 3, dashArray: '8, 8', opacity: 0.8
    }).addTo(map);

    const distKm = haversineKm(vanPos[0], vanPos[1], customerPos[0], customerPos[1]);
    document.getElementById('etaDistance').textContent = distKm.toFixed(2) + ' km';

    const effectiveSpeed = (speedKph && speedKph > 3) ? speedKph : 25; // default estimate kung nakatigil o walang datos
    const etaMinutes = Math.max(1, Math.round((distKm / effectiveSpeed) * 60));
    document.getElementById('etaTime').textContent = '~' + etaMinutes + ' min';
    document.getElementById('etaBanner').style.display = 'flex';
}

const statusText = document.getElementById('statusText');

// Makinig sa sariling lokasyon (driver_locations/{driverId})
db.ref('driver_locations/' + driverId).on('value', function(snapshot) {
    const data = snapshot.val();
    if (!data) return;
    vanPos = [data.lat, data.lng];
    const headingDeg = data.heading || 0;
    const speedKph = (data.speed || 0) * 3.6; // m/s papuntang km/h

    if (!vanMarker) {
        vanMarker = L.marker(vanPos, { icon: L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }) }).addTo(map).bindPopup('Ikaw (Van)');
    } else {
        vanMarker.setLatLng(vanPos);
        vanMarker.setIcon(L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }));
    }
    updateRouteAndEta(speedKph);
});

// Makinig sa lokasyon ng customer (customer_locations/{bookingId})
db.ref('customer_locations/' + bookingId).on('value', function(snapshot) {
    const data = snapshot.val();
    if (!data) {
        statusText.textContent = 'Hinihintay pa ang lokasyon ng customer...';
        return;
    }
    customerPos = [data.lat, data.lng];

    if (!customerMarker) {
        customerMarker = L.marker(customerPos, { icon: L.divIcon({ className: '', html: personIconHtml(), iconSize: [26, 26] }) }).addTo(map).bindPopup('Customer');
    } else {
        customerMarker.setLatLng(customerPos);
    }

    const ageSeconds = (Date.now() - data.updatedAt) / 1000;
    statusText.textContent = ageSeconds > 90
        ? 'Huling nakita ang customer: ' + Math.round(ageSeconds) + 's ang nakalipas.'
        : 'Live ang lokasyon ng customer.';

    updateRouteAndEta(null);

    if (vanPos && customerPos) {
        map.fitBounds([vanPos, customerPos], { padding: [40, 40] });
    }
});
</script>

<?php require __DIR__ . '/_driver_footer.php'; ?>