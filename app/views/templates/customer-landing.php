<?php require __DIR__ . '/_customer_header.php'; ?>

<?php if ($activeBooking || $activeRental): ?>
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
                <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.5rem;">
                    <?php if (!empty($activeBooking['driver_photo'])): ?>
                        <img src="<?= htmlspecialchars($activeBooking['driver_photo']) ?>" alt="" style="width:44px; height:44px; border-radius:100px; object-fit:cover;">
                    <?php else: ?>
                        <div style="width:44px; height:44px; border-radius:100px; background:var(--white); display:flex; align-items:center; justify-content:center; color:var(--slate);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div style="font-weight:600;"><?= htmlspecialchars($activeBooking['driver_name']) ?></div>
                        <div class="text-sm text-muted"><?= t('driver_info_phone_label') ?>: <?= htmlspecialchars($activeBooking['driver_phone'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="text-sm" style="border-top:1px solid var(--border); padding-top:0.5rem;">
                    <div><?= t('driver_info_van_label') ?>: <?= htmlspecialchars($activeBooking['van_make'] . ' ' . $activeBooking['van_model']) ?> (<?= htmlspecialchars($activeBooking['van_color'] ?: '—') ?>)</div>
                    <div><?= t('driver_info_plate_label') ?>: <strong><?= htmlspecialchars($activeBooking['plate_number']) ?></strong></div>
                </div>
            </div>
            <button type="button" class="btn-ghost" onclick="var p=document.getElementById('driverInfoPanel'); p.style.display = p.style.display === 'none' ? 'block' : 'none';" style="margin-right:0.5rem;"><?= t('btn_driver_info') ?></button>
        <?php endif; ?>

        <a href="/sitrass/public/messages/view/<?= (int)$activeBooking['booking_id'] ?>" class="btn" style="width:auto; padding:0.6rem 1.4rem; text-decoration:none; display:inline-block;"><?= t('btn_chat') ?></a>
    </div>
<?php elseif ($activeRental): ?>
    <div class="card" style="margin-top:1rem;">
        <div class="form-section-title" style="margin-bottom:0.75rem; border:none; padding:0;"><?= t('dashboard_active_rental_title') ?></div>
        <p style="margin:0 0 0.25rem;"><strong><?= htmlspecialchars($activeRental['route_label'] ?? $activeRental['pickup_name'] ?? 'Van Rental') ?></strong></p>
        <p class="text-sm text-muted" style="margin:0 0 0.75rem;"><?= htmlspecialchars($activeRental['reference_code']) ?> &middot; <?= htmlspecialchars($activeRental['make'] . ' ' . $activeRental['model']) ?> (<?= htmlspecialchars($activeRental['plate_number']) ?>)</p>

        <?php if ($activeRental['driver_id']): ?>
            <div id="rentalDriverInfoPanel" style="display:none; background:var(--slate-bg); border-radius:8px; padding:0.75rem 1rem; margin-bottom:0.75rem;">
                <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.5rem;">
                    <?php if (!empty($activeRental['driver_photo'])): ?>
                        <img src="<?= htmlspecialchars($activeRental['driver_photo']) ?>" alt="" style="width:44px; height:44px; border-radius:100px; object-fit:cover;">
                    <?php else: ?>
                        <div style="width:44px; height:44px; border-radius:100px; background:var(--white); display:flex; align-items:center; justify-content:center; color:var(--slate);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div style="font-weight:600;"><?= htmlspecialchars($activeRental['driver_name'] ?? t('no_driver_yet')) ?></div>
                        <div class="text-sm text-muted"><?= t('driver_info_phone_label') ?>: <?= htmlspecialchars($activeRental['driver_phone'] ?? '—') ?></div>
                    </div>
                </div>
                <div class="text-sm" style="border-top:1px solid var(--border); padding-top:0.5rem;">
                    <div><?= t('driver_info_van_label') ?>: <?= htmlspecialchars($activeRental['make'] . ' ' . $activeRental['model']) ?></div>
                    <div><?= t('driver_info_plate_label') ?>: <strong><?= htmlspecialchars($activeRental['plate_number']) ?></strong></div>
                    <div><?= t('rent_th_dates') ?>: <?= htmlspecialchars($activeRental['start_date']) ?> &rarr; <?= htmlspecialchars($activeRental['end_date']) ?></div>
                </div>
            </div>
            <button type="button" class="btn-ghost" onclick="var p=document.getElementById('rentalDriverInfoPanel'); p.style.display = p.style.display === 'none' ? 'block' : 'none';" style="margin-right:0.5rem;"><?= t('btn_driver_info') ?></button>
        <?php endif; ?>

        <!-- Live tracking ng rental: awtomatiko sa mapa sa itaas - wala nang kailangang i-click -->
        <a href="/sitrass/public/messages/viewRental/<?= (int)$activeRental['rental_id'] ?>" class="btn" style="width:auto; padding:0.6rem 1.4rem; text-decoration:none; display:inline-block;"><?= t('btn_chat') ?></a>
    </div>
<?php elseif ($unratedBooking && $unratedBooking['payment_status'] !== 'paid'): ?>
    <div class="card" style="margin-top:1rem; text-align:center;">
        <div class="form-section-title" style="margin-bottom:0.5rem; border:none; padding:0;"><?= t('dashboard_unpaid_title') ?></div>
        <p class="text-sm text-muted" style="margin-bottom:1rem;"><?= t('dashboard_unpaid_desc') ?></p>
        <a href="/sitrass/public/customer/payReservation/<?= htmlspecialchars($unratedBooking['reference_code']) ?>" class="btn" style="width:auto; padding:0.6rem 1.4rem; text-decoration:none; display:inline-block;"><?= t('btn_pay_balance') ?></a>
    </div>
<?php elseif ($unratedBooking): ?>
    <div class="card" style="margin-top:1rem; text-align:center;">
        <div class="form-section-title" style="margin-bottom:0.5rem; border:none; padding:0;"><?= t('dashboard_unrated_title') ?></div>
        <p class="text-sm text-muted" style="margin-bottom:1rem;"><?= t('dashboard_unrated_desc') ?></p>
        <a href="/sitrass/public/customer/rate/<?= (int)$unratedBooking['booking_id'] ?>" class="btn" style="width:auto; padding:0.6rem 1.4rem; text-decoration:none; display:inline-block;"><?= t('btn_rate') ?></a>
    </div>
<?php elseif ($unratedRental && $unratedRental['payment_status'] !== 'paid'): ?>
    <div class="card" style="margin-top:1rem; text-align:center;">
        <div class="form-section-title" style="margin-bottom:0.5rem; border:none; padding:0;"><?= t('dashboard_unpaid_title') ?></div>
        <p class="text-sm text-muted" style="margin-bottom:1rem;"><?= t('dashboard_unpaid_desc') ?></p>
        <a href="/sitrass/public/customer/rentPay/<?= (int)$unratedRental['rental_id'] ?>" class="btn" style="width:auto; padding:0.6rem 1.4rem; text-decoration:none; display:inline-block;"><?= t('btn_pay_balance') ?></a>
    </div>
<?php elseif ($unratedRental): ?>
    <div class="card" style="margin-top:1rem; text-align:center;">
        <div class="form-section-title" style="margin-bottom:0.5rem; border:none; padding:0;"><?= t('dashboard_unrated_title') ?></div>
        <p class="text-sm text-muted" style="margin-bottom:1rem;"><?= t('dashboard_unrated_desc') ?></p>
        <a href="/sitrass/public/customer/rateRental/<?= (int)$unratedRental['rental_id'] ?>" class="btn" style="width:auto; padding:0.6rem 1.4rem; text-decoration:none; display:inline-block;"><?= t('btn_rate') ?></a>
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
function navIconHtml(headingDeg) {
    const rot = headingDeg || 0;
    return '<div class="map-pulse-marker" style="width:34px;height:34px;">' +
        '<div class="pulse-ring" style="background:transparent;"></div>' +
        '<div style="width:34px;height:34px;background:#1A73E8;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(26,115,232,0.5);display:flex;align-items:center;justify-content:center;transform:rotate(' + rot + 'deg);">' +
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="white" stroke="none"><path d="M12 2 L18.5 20 L12 16.2 L5.5 20 Z"/></svg>' +
        '</div></div>';
}

// Normal na van icon (pre-boarding / accepted) - babalik dito pagkatapos
// ng end trip; ang asul na arrow ay para lang sa biyaheng en_route.
function vanIconHtml(headingDeg) {
    const rot = headingDeg || 0;
    return '<div class="map-pulse-marker" style="width:34px;height:34px;">' +
        '<div class="pulse-ring" style="background:transparent;"></div>' +
        '<div style="width:34px;height:34px;background:var(--teal-dark);border-radius:50%;border:3px solid var(--amber-light);display:flex;align-items:center;justify-content:center;transform:rotate(' + rot + 'deg);">' +
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 L19 21 L12 17 L5 21 Z"/></svg>' +
        '</div></div>';
}

let myMarker = null;
let vanMarker = null;
let routeLine = null;
let myPos = null;
let vanPos = null;
let mapCentered = false;

// Ang mapa ay awtomatikong sumusubaybay - para sa shared booking O sa
// van rental (katulad ng customer-track). Para sa rental, kumikilos ang
// mapa na parang "en_route" na biyahe (nasa kalsada na ang van).
const bookingId = <?= json_encode($activeBooking['booking_id'] ?? null) ?>;
const rentalId = <?= json_encode($activeRental['rental_id'] ?? null) ?>;
const isRental = rentalId !== null;
const driverId = <?= json_encode($activeBooking['driver_id'] ?? ($activeRental['driver_id'] ?? null)) ?>;
const bookingStatus = isRental ? 'en_route' : <?= json_encode($activeBooking['status'] ?? null) ?>;

const statusText = document.getElementById('dashStatusText');

// Mga koordenada (pickup -> dropoff) para sa ruta papunta sa pupuntahan -
// galing sa booking o sa ruta ng rental
const pickupLat = <?= json_encode((float)($activeBooking['pickup_lat'] ?? ($activeRental['pickup_lat'] ?? 0))) ?>;
const pickupLng = <?= json_encode((float)($activeBooking['pickup_lng'] ?? ($activeRental['pickup_lng'] ?? 0))) ?>;
const dropoffLat = <?= json_encode((float)($activeBooking['dropoff_lat'] ?? ($activeRental['dropoff_lat'] ?? 0))) ?>;
const dropoffLng = <?= json_encode((float)($activeBooking['dropoff_lng'] ?? ($activeRental['dropoff_lng'] ?? 0))) ?>;

let destMarker = null;
let destRoute = null;
let destCoords = [];
let lastRoutePos = null;
let tripEndedShown = false;

function haversineKm(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) ** 2 + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLng/2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

// Direksyon (bearing) mula point A papunta sa point B, sa degrees
function bearingBetween(lat1, lng1, lat2, lng2) {
    const toRad = function(d) { return d * Math.PI / 180; };
    const y = Math.sin(toRad(lng2 - lng1)) * Math.cos(toRad(lat2));
    const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) - Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(toRad(lng2 - lng1));
    return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
}

// Kung saan nakaturo ang daan malapit sa posisyon ng van - para tumama
// ang arrow sa direksyon ng ruta kahit walang GPS heading
function routeBearingAt(pos) {
    if (!destCoords || destCoords.length < 2) return 0;
    let bestI = 0, bestD = Infinity;
    for (let i = 0; i < destCoords.length; i++) {
        const d = haversineKm(pos[0], pos[1], destCoords[i][0], destCoords[i][1]);
        if (d < bestD) { bestD = d; bestI = i; }
    }
    if (bestI >= destCoords.length - 1) {
        return bearingBetween(destCoords[bestI - 1][0], destCoords[bestI - 1][1], destCoords[bestI][0], destCoords[bestI][1]);
    }
    return bearingBetween(destCoords[bestI][0], destCoords[bestI][1], destCoords[bestI + 1][0], destCoords[bestI + 1][1]);
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

// Berdeng RUTA PAPUNTA SA PUPUNTAHAN (en_route): totoong kalsada via OSRM -
// katulad ng mapa ng driver. Iguguhit agad mula sa pickup, tapos mula sa
// live na posisyon ng van habang gumagalaw ito.
function destIconHtml() {
    return '<div style="display:flex;flex-direction:column;align-items:center;filter:drop-shadow(0 2px 3px rgba(0,0,0,0.35));">' +
        '<div style="background:#C41E24; color:#fff; font-size:0.7rem; font-weight:700; padding:0.25rem 0.5rem; border-radius:6px; white-space:nowrap; margin-bottom:2px;">' + <?= json_encode(t('track_dest_label')) ?> + '</div>' +
        '<svg width="20" height="26" viewBox="0 0 24 32"><path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 20 12 20s12-11 12-20C24 5.4 18.6 0 12 0z" fill="#C41E24"/><circle cx="12" cy="12" r="4.5" fill="white"/></svg>' +
        '</div>';
}

function routeToDest(startPos) {
    if (!startPos || !dropoffLat || !dropoffLng) return;
    lastRoutePos = [startPos[0], startPos[1]];

    fetch('https://router.project-osrm.org/route/v1/driving/' + startPos[1] + ',' + startPos[0] + ';' + dropoffLng + ',' + dropoffLat + '?overview=full&geometries=geojson')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            let coords, distKm, mins;
            if (d && d.code === 'Ok' && d.routes && d.routes[0]) {
                coords = d.routes[0].geometry.coordinates.map(function(c) { return [c[1], c[0]]; });
                distKm = d.routes[0].distance / 1000;
                mins = Math.max(1, Math.round(d.routes[0].duration / 60));
            } else {
                // Fallback: tuwid na linya kapag down ang routing service
                coords = [[startPos[0], startPos[1]], [dropoffLat, dropoffLng]];
                distKm = haversineKm(startPos[0], startPos[1], dropoffLat, dropoffLng);
                mins = Math.max(1, Math.round((distKm / 30) * 60));
            }
            if (destRoute) map.removeLayer(destRoute);
            destRoute = L.polyline(coords, { color: '#188038', weight: 6, opacity: 0.9 }).addTo(map);
            destCoords = coords;

            const etaDistanceEl = document.getElementById('etaDistance');
            const etaTimeEl = document.getElementById('etaTime');
            if (etaDistanceEl) etaDistanceEl.textContent = distKm.toFixed(1) + ' km';
            if (etaTimeEl) etaTimeEl.textContent = '~' + mins + ' min';
            const etaBanner = document.getElementById('etaBanner');
            if (etaBanner) etaBanner.style.display = 'flex';

            // Arrived check: within ~100m ng destinasyon
            if (haversineKm(startPos[0], startPos[1], dropoffLat, dropoffLng) < 0.1) {
                statusText.textContent = <?= json_encode(t('track_arrived')) ?>;
            }
        })
        .catch(function() {
            if (destRoute) map.removeLayer(destRoute);
            destRoute = L.polyline([[startPos[0], startPos[1]], [dropoffLat, dropoffLng]], { color: '#188038', weight: 5, dashArray: '8 8', opacity: 0.8 }).addTo(map);
        });
}

function maybeRedrawDestRoute() {
    if (!vanPos || bookingStatus !== 'en_route') return;
    if (!lastRoutePos || haversineKm(lastRoutePos[0], lastRoutePos[1], vanPos[0], vanPos[1]) > 0.15) {
        routeToDest(vanPos);
    }
}

function sendMyLocation() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function(position) {
        myPos = [position.coords.latitude, position.coords.longitude];

        // Kapag nagsimula na ang biyahe, tanggalin ang person icon ng
        // customer - arrow ng van lang sa linya ng daan ang nakikita
        if (bookingStatus === 'en_route') {
            if (myMarker) { map.removeLayer(myMarker); myMarker = null; }
        } else if (!myMarker) {
            myMarker = L.marker(myPos, { icon: L.divIcon({ className: '', html: personIconHtml('var(--forest)'), iconSize: [26, 26] }) }).addTo(map);
        } else {
            myMarker.setLatLng(myPos);
        }

        if (!mapCentered) {
            map.setView(myPos, 13);
            mapCentered = true;
        }

        // Ang GPS ng customer ay PARA LANG sa pre-boarding (hinihintay pa ang
        // van para mahanap siya ng driver). Kapag nagsimula na ang biyahe,
        // naka-sakay na siya - hindi na accurate ang GPS sa loob ng van.
        if (bookingId && bookingStatus === 'accepted') {
            db.ref('customer_locations/' + bookingId).set({
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                updatedAt: Date.now()
            });
        }

        if (bookingStatus === 'accepted' && vanPos) updateRouteAndEta(null);
    }, function() {
        // walang permission - iwanan ang default view
    }, { enableHighAccuracy: true, timeout: 10000 });
}
sendMyLocation();
if (bookingId && bookingStatus === 'accepted') {
    setInterval(sendMyLocation, 15000);
}

if ((bookingId || isRental) && driverId) {
    statusText.textContent = <?= json_encode(t('map_waiting_driver')) ?>;
    db.ref('driver_locations/' + driverId).on('value', function(snapshot) {
        const data = snapshot.val();
        if (!data) {
            // Nabura ang location node sa Firebase = tapos na ang biyahe
            // (endTrip cleanup). Ibalik ang mapa sa normal: tanggalin ang
            // van marker, mga ruta, at ETA banner.
            if (vanMarker) { map.removeLayer(vanMarker); vanMarker = null; }
            if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
            if (destRoute) { map.removeLayer(destRoute); destRoute = null; }
            if (destMarker) { map.removeLayer(destMarker); destMarker = null; }
            destCoords = [];
            vanPos = null;
            const etaBanner = document.getElementById('etaBanner');
            if (etaBanner) etaBanner.style.display = 'none';
            if (!tripEndedShown) {
                tripEndedShown = true;
                statusText.textContent = <?= json_encode(t('track_trip_ended')) ?>;
            }
            return;
        }

        vanPos = [data.lat, data.lng];
        const headingDeg = data.heading || 0;
        const speedKph = (data.speed || 0) * 3.6;
        const ageSeconds = Math.round((Date.now() - data.updatedAt) / 1000);

        // Ikulong ang arrow sa direksyon ng ruta kapag biyahe na
        let rot = headingDeg;
        if ((!rot || rot === 0) && bookingStatus === 'en_route') {
            rot = routeBearingAt(vanPos);
        }

        // Asul na arrow LANG kapag biyahe na (en_route); normal na van icon
        // kapag papunta pa lang sa pick-up (accepted).
        const vanIcon = (bookingStatus === 'en_route') ? navIconHtml(rot) : vanIconHtml(headingDeg);

        if (!vanMarker) {
            vanMarker = L.marker(vanPos, { icon: L.divIcon({ className: '', html: vanIcon, iconSize: [34, 34] }) }).addTo(map);
            vanMarker.bindTooltip('0.0 km/h', { direction: 'top', offset: [0, -20] });
        } else {
            vanMarker.setLatLng(vanPos);
            vanMarker.setIcon(L.divIcon({ className: '', html: vanIcon, iconSize: [34, 34] }));
        }

        // Hover tooltip: bilis ng van mula sa GPS
        vanMarker.setTooltipContent(speedKph.toFixed(1) + ' km/h');

        statusText.textContent = (ageSeconds > 90)
            ? (<?= json_encode(t('map_stale_prefix')) ?> + ' ' + ageSeconds + 's')
            : (<?= json_encode(t('map_live_prefix')) ?> + ' ' + ageSeconds + ' ' + <?= json_encode(t('map_seconds_ago')) ?>);

        if (bookingStatus === 'accepted') {
            updateRouteAndEta(speedKph);
            if (myPos) {
                map.fitBounds([vanPos, myPos], { padding: [40, 40] });
            } else {
                map.setView(vanPos, 13);
            }
        } else if (bookingStatus === 'en_route') {
            maybeRedrawDestRoute();
            if (destRoute) {
                map.fitBounds([vanPos, [dropoffLat, dropoffLng]], { padding: [40, 40] });
            } else if (myPos) {
                map.fitBounds([vanPos, myPos], { padding: [40, 40] });
            } else {
                map.setView(vanPos, 13);
            }
        }
    });
}

// En route: iguhit AGAD ang berdeng ruta papunta sa destinasyon (mula sa
// pickup habang wala pang GPS ng van), kasama ang malaking "Pupuntahan" pin.
if (bookingStatus === 'en_route' && dropoffLat && dropoffLng) {
    if (myMarker) { map.removeLayer(myMarker); myMarker = null; }
    destMarker = L.marker([dropoffLat, dropoffLng], {
        icon: L.divIcon({ className: '', html: destIconHtml(), iconSize: [26, 62], iconAnchor: [13, 60] })
    }).addTo(map);
    routeToDest([pickupLat, pickupLng]);
    map.setView([pickupLat, pickupLng], 12);
}
</script>

<?php require __DIR__ . '/_customer_footer.php'; ?>