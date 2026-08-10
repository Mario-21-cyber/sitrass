<?php require __DIR__ . '/_customer_header.php'; ?>

<h2>I-track ang Biyahe</h2>

<div class="card">
    <p><strong>Reference:</strong> <?= htmlspecialchars($booking['reference_code']) ?></p>
    <p><strong>Van:</strong> <?= htmlspecialchars($booking['plate_number']) ?></p>
    <p style="margin:0;"><strong>Driver:</strong> <?= htmlspecialchars($booking['driver_name'] ?? 'Wala pang driver') ?></p>
</div>

<div id="trackMap" style="height:400px; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow-sm); margin-bottom:1rem;"></div>

<p id="statusText" style="font-size:0.9rem; color:var(--ocean);">Hinihintay ang datos ng lokasyon...</p>

<a href="/sitrass/public/customer/myBookings" class="btn-link">Bumalik</a>

<script>
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

const driverId = <?= json_encode($booking['driver_id']) ?>;
const pickupLat = <?= json_encode((float)$booking['pickup_lat']) ?>;
const pickupLng = <?= json_encode((float)$booking['pickup_lng']) ?>;
const dropoffLat = <?= json_encode((float)$booking['dropoff_lat']) ?>;
const dropoffLng = <?= json_encode((float)$booking['dropoff_lng']) ?>;

const map = L.map('trackMap').setView([pickupLat, pickupLng], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

const pickupIcon = L.divIcon({ className: '', html: '<div style="background:#2D6A34; width:14px; height:14px; border-radius:50%; border:2px solid white;"></div>' });
const dropoffIcon = L.divIcon({ className: '', html: '<div style="background:#C41E24; width:14px; height:14px; border-radius:50%; border:2px solid white;"></div>' });

L.marker([pickupLat, pickupLng], { icon: pickupIcon }).addTo(map).bindPopup('Pickup');
L.marker([dropoffLat, dropoffLng], { icon: dropoffIcon }).addTo(map).bindPopup('Dropoff');

let driverMarker = null;
const statusText = document.getElementById('statusText');

if (driverId) {
    db.ref('driver_locations/' + driverId).on('value', function(snapshot) {
        const data = snapshot.val();

        if (!data) {
            statusText.textContent = 'Wala pang lokasyon na natatanggap mula sa driver.';
            return;
        }

        const ageSeconds = (Date.now() - data.updatedAt) / 1000;
        const pos = [data.lat, data.lng];

        if (!driverMarker) {
            const vanIcon = L.divIcon({
                className: '',
                html: '<div style="background:#1B2A6B; width:20px; height:20px; border-radius:50%; border:3px solid #F2A54A; box-shadow:0 2px 6px rgba(0,0,0,0.3);"></div>'
            });
            driverMarker = L.marker(pos, { icon: vanIcon }).addTo(map).bindPopup('Ang van mo');
            map.setView(pos, 14);
        } else {
            driverMarker.setLatLng(pos);
        }

        if (ageSeconds > 90) {
            statusText.textContent = 'Huling nakita: ' + Math.round(ageSeconds) + ' segundo na ang nakalipas (baka naka-off ang GPS ng driver).';
        } else {
            statusText.textContent = 'Live - na-update ' + Math.round(ageSeconds) + ' segundo ang nakalipas.';
        }
    });
} else {
    statusText.textContent = 'Wala pang naka-assign na driver sa biyaheng ito.';
}
</script>

<?php require __DIR__ . '/_customer_footer.php'; ?>