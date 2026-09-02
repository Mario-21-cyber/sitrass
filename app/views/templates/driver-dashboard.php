<?php require __DIR__ . '/_driver_header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<h2><?= t('nav_my_trips') ?></h2>

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
<p id="dashStatusText" class="text-sm text-muted" style="margin:0 0 1.5rem;"></p>

<?php if (!empty($paymentToVerify)): ?>
    <div class="modal-overlay">
        <div class="modal-box">
            <h3><?= t('endtrip_payment_title') ?></h3>
            <p class="text-sm text-muted"><?= t('endtrip_payment_desc') ?></p>
            <div class="field-row"><span class="text-muted"><?= t('th_reference') ?></span><strong><?= htmlspecialchars($paymentToVerify['reference_code']) ?></strong></div>
            <div class="field-row"><span class="text-muted"><?= t('th_customer') ?></span><strong><?= htmlspecialchars($paymentToVerify['customer_name']) ?></strong></div>
            <div class="field-row"><span class="text-muted"><?= t('th_amount') ?></span><strong>₱<?= number_format($paymentToVerify['amount'], 2) ?></strong></div>

            <div class="modal-actions">
                <form method="POST" action="/sitrass/public/driver/verifyPayment" style="flex:1;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="payment_id" value="<?= (int)$paymentToVerify['payment_id'] ?>">
                    <button type="submit" class="btn"><?= t('btn_verify') ?></button>
                </form>
                <form method="POST" action="/sitrass/public/driver/rejectPayment" style="flex:1;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="payment_id" value="<?= (int)$paymentToVerify['payment_id'] ?>">
                    <button type="submit" class="btn-danger" style="width:100%; border:none; border-radius:6px; cursor:pointer;"><?= t('btn_reject') ?></button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($boardingPending)): ?>
    <div class="modal-overlay">
        <div class="modal-box">
            <h3><?= t('scan_confirm_title') ?></h3>
            <div class="field-row"><span class="text-muted"><?= t('scan_confirm_ref') ?></span><strong><?= htmlspecialchars($boardingPending['reference_code']) ?></strong></div>
            <div class="field-row"><span class="text-muted"><?= t('scan_confirm_customer') ?></span><strong><?= htmlspecialchars($boardingPending['customer_name']) ?></strong></div>
            <div class="field-row"><span class="text-muted"><?= t('scan_confirm_seats') ?></span><strong><?= (int)$boardingPending['seats_booked'] ?></strong></div>
            <div class="field-row"><span class="text-muted"><?= t('scan_confirm_payment_status') ?></span><strong><?= t('status_' . $boardingPending['payment_status']) ?></strong></div>
            <?php if ($boardingPending['balance_due'] > 0): ?>
                <div class="field-row"><span class="text-muted"><?= t('scan_confirm_balance') ?></span><strong>₱<?= number_format($boardingPending['balance_due'], 2) ?></strong></div>
            <?php endif; ?>

            <div class="modal-actions">
                <form method="POST" action="/sitrass/public/driver/verifyBoardingConfirm" style="flex:1;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="qr_id" value="<?= (int)$boardingPending['qr_id'] ?>">
                    <button type="submit" class="btn"><?= t('btn_confirm_boarding') ?></button>
                </form>
                <form method="POST" action="/sitrass/public/driver/verifyBoardingCancel" style="flex:1;">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn-ghost" style="width:100%;"><?= t('btn_cancel_scan') ?></button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($activeBooking && $activeBooking['status'] === 'accepted' && $activeBooking['qr_status'] !== 'used'): ?>
    <div class="modal-overlay" id="boardingModal" style="display:none;">
        <div class="modal-box">
            <h3><?= t('boarding_verify_title') ?></h3>

            <div class="qr-tabs">
                <button type="button" id="boardCameraTabBtn" class="qr-tab active" onclick="showBoardTab('camera')"><?= t('qr_camera_tab') ?></button>
                <button type="button" id="boardManualTabBtn" class="qr-tab" onclick="showBoardTab('manual')"><?= t('qr_manual_tab') ?></button>
            </div>

            <form method="POST" action="/sitrass/public/driver/verifyBoarding" id="boardingForm">
                <?= Csrf::field() ?>
                <input type="hidden" name="booking_id" value="<?= (int)$activeBooking['booking_id'] ?>">

                <div id="boardCameraTab">
                    <div id="boardCameraContainer" style="display:none; position:relative; border-radius:var(--radius); overflow:hidden; margin-bottom:0.75rem; background:#000;">
                        <video id="boardVideo" style="width:100%; display:block;" playsinline></video>
                        <canvas id="boardCanvas" style="display:none;"></canvas>
                    </div>
                    <p id="boardCameraStatus" class="text-sm text-muted" style="margin-bottom:0.5rem;"></p>
                    <button type="button" id="boardCameraToggleBtn" class="btn-ghost" onclick="toggleBoardCamera()"><?= t('qr_camera_start') ?></button>
                </div>

                <div id="boardManualTab" style="display:none;">
                    <div class="field">
                        <label><?= t('boarding_verify_input_label') ?></label>
                        <input type="text" name="token" id="boardTokenInput">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn" style="flex:1;"><?= t('btn_confirm') ?></button>
                    <button type="button" class="btn-ghost" style="flex:1;" onclick="closeBoardingModal();"><?= t('btn_cancel') ?></button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($activeBooking): ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="form-section-title" style="margin-bottom:0.75rem; border:none; padding:0;"><?= t('dashboard_active_title') ?></div>
        <p style="margin:0 0 0.25rem;"><strong><?= htmlspecialchars($activeBooking['pickup_name']) ?> &rarr; <?= htmlspecialchars($activeBooking['dropoff_name']) ?></strong></p>
        <p class="text-sm text-muted" style="margin:0 0 0.75rem;"><?= htmlspecialchars($activeBooking['reference_code']) ?> &middot; <?= htmlspecialchars($activeBooking['customer_name']) ?> (<?= htmlspecialchars($activeBooking['customer_phone']) ?>)</p>

        <?php if ($activeBooking['status'] === 'accepted' && $activeBooking['qr_status'] !== 'used'): ?>
            <button type="button" class="btn" style="width:auto; padding:0.6rem 1.4rem;" onclick="openBoardingModal();"><?= t('btn_verify_boarding') ?></button>
        <?php elseif ($activeBooking['status'] === 'accepted'): ?>
            <form method="POST" action="/sitrass/public/driver/startTrip" style="display:inline;">
                <?= Csrf::field() ?>
                <input type="hidden" name="booking_id" value="<?= (int)$activeBooking['booking_id'] ?>">
                <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.4rem;"><?= t('btn_start_trip') ?></button>
            </form>
        <?php elseif ($activeBooking['status'] === 'en_route'): ?>
            <form method="POST" action="/sitrass/public/driver/endTrip" style="display:inline; margin-right:0.5rem;">
                <?= Csrf::field() ?>
                <input type="hidden" name="booking_id" value="<?= (int)$activeBooking['booking_id'] ?>">
                <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.4rem;"><?= t('btn_end_trip') ?></button>
            </form>
        <?php endif; ?>
        <a href="/sitrass/public/chat/open/<?= (int)$activeBooking['booking_id'] ?>" class="btn-ghost" style="display:inline-block; padding:0.6rem 1rem;"><?= t('btn_chat') ?></a>
    </div>
<?php else: ?>
    <p class="text-sm text-muted" style="margin-bottom:1.5rem;"><?= t('driver_no_active_trip') ?></p>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="section-heading" style="margin-top:1.5rem;">
    <h3><?= t('driver_pending_requests_title') ?></h3>
</div>

<?php if (empty($bookings)): ?>
    <div class="empty-state"><?= t('driver_pending_empty') ?></div>
<?php else: ?>
    <?php foreach ($bookings as $b): ?>
        <div class="card list-card">
            <div>
                <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($b['reference_code']) ?></span><br>
                <strong><?= htmlspecialchars($b['pickup_name']) ?> &rarr; <?= htmlspecialchars($b['dropoff_name']) ?></strong><br>
                <span style="font-size:0.85rem;"><?= htmlspecialchars($b['travel_date']) ?> @ <?= htmlspecialchars($b['pickup_time']) ?> &middot; <?= (int)$b['seats_booked'] ?> <?= t('unit_passengers') ?></span><br>
                <span style="font-size:0.85rem;"><?= t('label_customer') ?>: <?= htmlspecialchars($b['customer_name']) ?> (<?= htmlspecialchars($b['customer_phone']) ?>)</span>
            </div>
            <span class="badge badge-pending"><?= t('status_' . $b['status']) ?></span>

            <div class="actions">
                <form method="POST" action="/sitrass/public/driver/accept" style="display:inline;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                    <button type="submit" class="btn"><?= t('btn_accept') ?></button>
                </form>
                <form method="POST" action="/sitrass/public/driver/reject" style="display:inline;" onsubmit="return confirm('Sigurado kang tanggihan ang booking na ito?');">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
                    <button type="submit" class="btn-danger" style="border:none; border-radius:6px; cursor:pointer;"><?= t('btn_reject') ?></button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
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
let customerMarker = null;
let routeLine = null;
let myPos = null;
let customerPos = null;
let mapCentered = false;

const bookingId = <?= json_encode($activeBooking['booking_id'] ?? null) ?>;
const driverId = <?= json_encode($driverIdForGps) ?>;
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
    if (!myPos || !customerPos) return;
    if (routeLine) map.removeLayer(routeLine);
    routeLine = L.polyline([myPos, customerPos], { color: '#4285F4', weight: 5, opacity: 0.85 }).addTo(map);

    const distKm = haversineKm(myPos[0], myPos[1], customerPos[0], customerPos[1]);
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
        const headingDeg = position.coords.heading || 0;

        if (!myMarker) {
            myMarker = L.marker(myPos, { icon: L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }) }).addTo(map);
        } else {
            myMarker.setLatLng(myPos);
            myMarker.setIcon(L.divIcon({ className: '', html: vanIconHtml(headingDeg), iconSize: [30, 30] }));
        }

        if (!mapCentered) {
            map.setView(myPos, 13);
            mapCentered = true;
        }

        if (bookingId && driverId && bookingStatus === 'en_route') {
            db.ref('driver_locations/' + driverId).set({
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                heading: position.coords.heading || 0,
                speed: position.coords.speed || 0,
                bookingId: bookingId,
                updatedAt: Date.now()
            });
        }

        if (customerPos) updateRouteAndEta(null);
    }, function() {
        // walang permission
    }, { enableHighAccuracy: true, timeout: 10000 });
}
sendMyLocation();
if (bookingId && driverId && bookingStatus === 'en_route') {
    setInterval(sendMyLocation, 15000);
}

if (bookingId) {
    statusText.textContent = <?= json_encode(t('map_waiting_customer')) ?>;
    db.ref('customer_locations/' + bookingId).on('value', function(snapshot) {
        const data = snapshot.val();
        if (!data) return;

        customerPos = [data.lat, data.lng];
        const ageSeconds = Math.round((Date.now() - data.updatedAt) / 1000);

        if (!customerMarker) {
            customerMarker = L.marker(customerPos, { icon: L.divIcon({ className: '', html: personIconHtml('var(--forest)'), iconSize: [26, 26] }) }).addTo(map);
        } else {
            customerMarker.setLatLng(customerPos);
        }

        statusText.textContent = (ageSeconds > 90)
            ? (<?= json_encode(t('map_stale_prefix')) ?> + ' ' + ageSeconds + 's')
            : (<?= json_encode(t('map_live_prefix')) ?> + ' ' + ageSeconds + ' ' + <?= json_encode(t('map_seconds_ago')) ?>);

        updateRouteAndEta(null);

        if (myPos) {
            map.fitBounds([myPos, customerPos], { padding: [40, 40] });
        } else {
            map.setView(customerPos, 13);
        }
    });
}

// --- Boarding verification modal: camera + manual ---
function openBoardingModal() {
    const modal = document.getElementById('boardingModal');
    if (modal) modal.style.display = 'flex';
}
function closeBoardingModal() {
    const modal = document.getElementById('boardingModal');
    if (modal) modal.style.display = 'none';
    stopBoardCamera();
}
function showBoardTab(tab) {
    const cameraTab = document.getElementById('boardCameraTab');
    const manualTab = document.getElementById('boardManualTab');
    const cameraBtn = document.getElementById('boardCameraTabBtn');
    const manualBtn = document.getElementById('boardManualTabBtn');
    if (tab === 'camera') {
        cameraTab.style.display = 'block';
        manualTab.style.display = 'none';
        cameraBtn.className = 'qr-tab active';
        manualBtn.className = 'qr-tab';
    } else {
        cameraTab.style.display = 'none';
        manualTab.style.display = 'block';
        cameraBtn.className = 'qr-tab';
        manualBtn.className = 'qr-tab active';
        stopBoardCamera();
    }
}

let boardCameraStream = null;
let boardScanLoopId = null;

function toggleBoardCamera() {
    if (boardCameraStream) {
        stopBoardCamera();
    } else {
        startBoardCamera();
    }
}

function startBoardCamera() {
    const statusEl = document.getElementById('boardCameraStatus');
    const container = document.getElementById('boardCameraContainer');
    const toggleBtn = document.getElementById('boardCameraToggleBtn');
    const video = document.getElementById('boardVideo');

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        statusEl.textContent = <?= json_encode(t('qr_camera_not_supported')) ?>;
        return;
    }

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(stream) {
            boardCameraStream = stream;
            video.srcObject = stream;
            video.setAttribute('playsinline', true);
            video.play();
            container.style.display = 'block';
            toggleBtn.textContent = <?= json_encode(t('qr_camera_stop')) ?>;
            statusEl.textContent = <?= json_encode(t('qr_camera_scanning')) ?>;
            boardScanLoopId = requestAnimationFrame(scanBoardFrame);
        })
        .catch(function() {
            statusEl.textContent = <?= json_encode(t('qr_camera_permission_denied')) ?>;
        });
}

function stopBoardCamera() {
    if (boardScanLoopId) { cancelAnimationFrame(boardScanLoopId); boardScanLoopId = null; }
    if (boardCameraStream) { boardCameraStream.getTracks().forEach(function(t) { t.stop(); }); boardCameraStream = null; }
    const container = document.getElementById('boardCameraContainer');
    if (container) container.style.display = 'none';
    const toggleBtn = document.getElementById('boardCameraToggleBtn');
    if (toggleBtn) toggleBtn.textContent = <?= json_encode(t('qr_camera_start')) ?>;
    const statusEl = document.getElementById('boardCameraStatus');
    if (statusEl) statusEl.textContent = '';
}

function scanBoardFrame() {
    const video = document.getElementById('boardVideo');
    const canvas = document.getElementById('boardCanvas');
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code && code.data) {
            document.getElementById('boardCameraStatus').textContent = <?= json_encode(t('qr_camera_detected')) ?>;
            document.getElementById('boardTokenInput').value = code.data;
            stopBoardCamera();
            document.getElementById('boardingForm').submit();
            return;
        }
    }
    boardScanLoopId = requestAnimationFrame(scanBoardFrame);
}
</script>

<?php require __DIR__ . '/_driver_footer.php'; ?>
