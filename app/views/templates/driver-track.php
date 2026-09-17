<?php require __DIR__ . '/_driver_header.php'; ?>

<div class="card">
    <p><strong><?= t('label_reference') ?>:</strong> <?= htmlspecialchars($booking['reference_code']) ?></p>
    <p><strong><?= t('label_customer') ?>:</strong> <?= htmlspecialchars($booking['customer_name']) ?> (<?= htmlspecialchars($booking['customer_phone']) ?>)</p>
    <p style="margin:0;"><strong><?= t('label_van') ?>:</strong> <?= htmlspecialchars($booking['plate_number']) ?></p>
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
    <strong><?= t('track_arrived') ?></strong><br><?= t('track_arrived_note') ?>
</div>

<div id="trackMap" style="height:400px; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow-sm); margin-bottom:1rem;"></div>

<form method="POST" action="/sitrass/public/driver/endTrip" id="endTripForm" style="display:none; margin-bottom:1rem;" onsubmit="return confirm('I-end trip na ang biyaheng ito?');">
    <?= Csrf::field() ?>
    <input type="hidden" name="booking_id" value="<?= (int)$booking['booking_id'] ?>">
    <button type="submit" class="btn" style="width:100%;"><?= t('btn_end_trip') ?></button>
</form>

<p id="statusText" class="text-sm text-muted"><?= t('track_waiting_location') ?></p>

<a href="/sitrass/public/driver/dashboard" class="btn-link"><?= t('btn_back') ?></a>

<script>
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

const bookingId = <?= json_encode($booking['booking_id']) ?>;
const driverId = <?= json_encode($driverId) ?>;
const pickupLat = <?= json_encode((float)$booking['pickup_lat']) ?>;
const pickupLng = <?= json_encode((float)$booking['pickup_lng']) ?>;
const dropoffLat = <?= json_encode((float)$booking['dropoff_lat']) ?>;
const dropoffLng = <?= json_encode((float)$booking['dropoff_lng']) ?>;

const map = L.map('trackMap').setView([pickupLat, pickupLng], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Marker ng pupuntahan (destination) - malaking pin na laging nakikita
const destIcon = L.divIcon({
    className: '',
    html: '<div style="transform:translate(-50%,-100%);">' +
          '<div style="background:#C41E24; color:#fff; font-size:0.7rem; font-weight:700; padding:0.25rem 0.5rem; border-radius:6px; white-space:nowrap; box-shadow:0 1px 4px rgba(0,0,0,0.3);">' + <?= json_encode(t('track_dest_label')) ?> + '</div>' +
          '<div style="width:0; height:0; border-left:7px solid transparent; border-right:7px solid transparent; border-top:9px solid #C41E24; margin:0 auto;"></div>' +
          '</div>',
    iconSize: [0, 0]
});
L.marker([dropoffLat, dropoffLng], { icon: destIcon }).addTo(map);

// --- Icons ---
function vanIconHtml(headingDeg) {
    const rot = headingDeg || 0;
    return '<div class="map-pulse-marker" style="width:34px;height:34px;">' +
        '<div class="pulse-ring" style="background:transparent;"></div>' +
        '<div style="width:34px;height:34px;background:#1A73E8;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(26,115,232,0.5);display:flex;align-items:center;justify-content:center;transform:rotate(' + rot + 'deg);">' +
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M12 2 L19 21 L12 17 L5 21 Z"/></svg>' +
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
let proxLine = null;
let vanPos = null;
let customerPos = null;
let tripEnded = false;
let gpsInterval = null;
let lastRouteFetch = 0;
let lastRouteOrigin = null;
let fitDone = false;
let arrivedShown = false;

// Iguhit AGAD ang ruta (pickup -> destination) habang naghihintay sa
// live na GPS ng van - para may nakikitang ruta kaagad kapag nag-start.
// Kapag dumating na ang totoong posisyon ng van, dito na muling iguhit.
updateRouteToDestination([pickupLat, pickupLng]);
map.fitBounds([[pickupLat, pickupLng], [dropoffLat, dropoffLng]], { padding: [40, 40] });
fitDone = true;

function haversineKm(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) ** 2 + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLng/2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

// (Walang customer-GPS line - nasa van na ang customer kapag en_route.)

// TUNAY NA RUTA ng kalsada mula sa van papunta sa pupuntahan (OSRM).
// Kung nabigo ito, direktang guhit ang gagamitin bilang fallback.
function drawStraightFallback(fromPos) {
    if (routeLine) map.removeLayer(routeLine);
    routeLine = L.polyline([fromPos, [dropoffLat, dropoffLng]], {
        color: '#2D6A34', weight: 3, dashArray: '8, 8', opacity: 0.75
    }).addTo(map);
    const distKm = haversineKm(fromPos[0], fromPos[1], dropoffLat, dropoffLng);
    document.getElementById('etaDistance').textContent = distKm.toFixed(2) + ' km';
    document.getElementById('etaTime').textContent = '~' + Math.max(1, Math.round((distKm / 25) * 60)) + ' min';
    document.getElementById('etaBanner').style.display = 'flex';
}

function updateRouteToDestination(fromPos) {
    if (tripEnded || !fromPos) return;

    // I-throttle: isang OSRM call kada 30s, o kapag gumalaw nang >150m
    const movedKm = lastRouteOrigin ? haversineKm(lastRouteOrigin[0], lastRouteOrigin[1], fromPos[0], fromPos[1]) : 99;
    if (Date.now() - lastRouteFetch < 30000 && movedKm < 0.15) return;
    lastRouteFetch = Date.now();
    lastRouteOrigin = [fromPos[0], fromPos[1]];

    const url = 'https://router.project-osrm.org/route/v1/driving/' +
        fromPos[1] + ',' + fromPos[0] + ';' + dropoffLng + ',' + dropoffLat +
        '?overview=full&geometries=geojson';
    fetch(url)
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (tripEnded) return;
            if (json.code === 'Ok' && json.routes && json.routes[0]) {
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

// --- Ipadala ang GPS ng driver HABANG nasa page na ito - ito ang
//     pinagmumulan ng live marker ng van sa mapa ng customer ---
function sendMyLocation() {
    if (tripEnded || !navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function (position) {
        if (tripEnded) return;
        vanPos = [position.coords.latitude, position.coords.longitude];
        const headingDeg = position.coords.heading || 0;

        if (!vanMarker) {
            vanMarker = L.marker(vanPos, { icon: L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }) }).addTo(map).bindPopup(<?= json_encode(t('map_you_van')) ?>);
            map.setView(vanPos, 14);
        } else {
            vanMarker.setLatLng(vanPos);
            vanMarker.setIcon(L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }));
        }

        db.ref('driver_locations/' + driverId).set({
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            heading: position.coords.heading || 0,
            speed: position.coords.speed || 0,
            bookingId: bookingId,
            updatedAt: Date.now()
        });

        updateRouteToDestination(vanPos);
        checkArrived();
    }, function () {}, { enableHighAccuracy: true, timeout: 10000 });
}
sendMyLocation();
gpsInterval = setInterval(sendMyLocation, 15000);

const statusText = document.getElementById('statusText');

// Makinig sa sariling lokasyon (driver_locations/{driverId})
db.ref('driver_locations/' + driverId).on('value', function(snapshot) {
    const data = snapshot.val();
    if (!data) return;
    vanPos = [data.lat, data.lng];
    const headingDeg = data.heading || 0;
    const speedKph = (data.speed || 0) * 3.6; // m/s papuntang km/h

    if (!vanMarker) {
        vanMarker = L.marker(vanPos, { icon: L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }) }).addTo(map).bindPopup(<?= json_encode(t('map_you_van')) ?>);
    } else {
        vanMarker.setLatLng(vanPos);
        vanMarker.setIcon(L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }));
    }
    updateRouteAndEta(speedKph);
});

// Tanggal na ang customer-GPS marker: kapag en_route na ay naka-sakay na
// ang customer sa van (na-verify ang boarding sa QR), at hindi accurate
// ang GPS ng cellphone sa loob ng sasakyan. Ang GPS ng driver sa van ang
// tunay na pinagkukunan ng lokasyon, kasama ang ruta papuntang destinasyon.

// Kapag within ~100m na ang van sa destinasyon, magpapakita ng "Arrived"
// na abiso. Manual pa rin ang End Trip ng driver.
function checkArrived() {
    if (arrivedShown || tripEnded || !vanPos) return;
    if (haversineKm(vanPos[0], vanPos[1], dropoffLat, dropoffLng) <= 0.1) {
        arrivedShown = true;
        document.getElementById('arrivedBanner').style.display = 'block';
        var f = document.getElementById('endTripForm');
        if (f) f.style.display = 'block';
        statusText.textContent = <?= json_encode(t('track_arrived_note')) ?>;
    }
}

// --- Kapag tapos na ang biyahe (hindi na en_route): ihinto ang GPS,
//     linisin ang Firebase, mawawala ang ruta/markers, at babalik ang
//     driver sa dashboard ---
function endTracking() {
    if (tripEnded) return;
    tripEnded = true;
    if (gpsInterval) clearInterval(gpsInterval);
    db.ref('driver_locations/' + driverId).remove();
    if (routeLine) map.removeLayer(routeLine);
    document.getElementById('etaBanner').style.display = 'none';
    document.getElementById('arrivedBanner').style.display = 'none';
    var f = document.getElementById('endTripForm');
    if (f) f.style.display = 'none';
    statusText.innerHTML = '<strong>' + <?= json_encode(t('track_trip_ended')) ?> + '</strong><br>' + <?= json_encode(t('track_trip_ended_note')) ?>;
    setTimeout(function () { window.location.href = '/sitrass/public/driver/dashboard'; }, 4000);
}

setInterval(function () {
    fetch('/sitrass/public/driver/tripStatus/' + bookingId)
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d && d.status && d.status !== 'en_route') { endTracking(); }
        })
        .catch(function () {});
}, 20000);
</script>

<?php require __DIR__ . '/_driver_footer.php'; ?>
