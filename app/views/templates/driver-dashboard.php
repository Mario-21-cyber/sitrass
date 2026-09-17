<?php require __DIR__ . '/_driver_header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

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

<?php if (!empty($rentalPaymentToVerify)): ?>
    <div class="modal-overlay">
        <div class="modal-box">
            <h3><?= t('endtrip_payment_title') ?></h3>
            <p class="text-sm text-muted"><?= t('endtrip_payment_desc') ?></p>
            <div class="field-row"><span class="text-muted"><?= t('th_reference') ?></span><strong><?= htmlspecialchars($rentalPaymentToVerify['reference_code']) ?></strong></div>
            <div class="field-row"><span class="text-muted"><?= t('th_customer') ?></span><strong><?= htmlspecialchars($rentalPaymentToVerify['customer_name']) ?></strong></div>
            <div class="field-row"><span class="text-muted"><?= t('label_payment_method') ?? 'Payment Method' ?></span><strong><?= htmlspecialchars($rentalPaymentToVerify['method_name']) ?></strong></div>
            <div class="field-row"><span class="text-muted"><?= t('th_amount') ?></span><strong>₱<?= number_format($rentalPaymentToVerify['amount'], 2) ?></strong></div>

            <div class="modal-actions">
                <form method="POST" action="/sitrass/public/driver/verifyPayment" style="flex:1;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="payment_id" value="<?= (int)$rentalPaymentToVerify['payment_id'] ?>">
                    <button type="submit" class="btn"><?= t('btn_verify') ?></button>
                </form>
                <form method="POST" action="/sitrass/public/driver/rejectPayment" style="flex:1;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="payment_id" value="<?= (int)$rentalPaymentToVerify['payment_id'] ?>">
                    <button type="submit" class="btn-danger" style="width:100%; border:none; border-radius:6px; cursor:pointer;"><?= t('btn_reject') ?></button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($rentalPaymentsToVerify)): ?>
    <div class="card" style="margin-bottom:1rem; border:1px solid var(--border);">
        <h3 style="margin-bottom:0.5rem;"><?= t('rent_f2f_verify_title') ?></h3>
        <p class="text-sm text-muted" style="margin-bottom:0.75rem;"><?= t('rent_f2f_verify_desc') ?></p>
        <?php foreach ($rentalPaymentsToVerify as $rp): ?>
            <div style="background:var(--bg); border-radius:6px; padding:0.75rem; margin-bottom:0.5rem; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($rp['reference_code']) ?></span><br>
                    <span class="text-sm"><?= htmlspecialchars($rp['customer_name']) ?> &middot; <?= htmlspecialchars($rp['plate_number'] ?? '') ?></span><br>
                    <span class="text-sm text-muted"><?= htmlspecialchars($rp['method_name'] ?? '') ?> &middot; <?= t('th_amount') ?>: ₱<?= number_format($rp['amount'], 2) ?></span>
                </div>
                <div style="display:flex; gap:0.4rem;">
                    <form method="POST" action="/sitrass/public/driver/verifyPayment" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int)$rp['payment_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.35rem 0.9rem; font-size:0.8rem;"><?= t('btn_verify') ?></button>
                    </form>
                    <form method="POST" action="/sitrass/public/driver/rejectPayment" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int)$rp['payment_id'] ?>">
                        <button type="submit" class="btn-danger" style="width:auto; padding:0.35rem 0.9rem; font-size:0.8rem; border:none; border-radius:6px; cursor:pointer;"><?= t('btn_reject') ?></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
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

<?php if (!empty($rentalPickupPending)): ?>
    <div class="modal-overlay">
        <div class="modal-box">
            <h3><?= t('rent_confirm_title') ?></h3>
            <div class="field-row"><span class="text-muted"><?= t('scan_confirm_ref') ?></span><strong><?= htmlspecialchars($rentalPickupPending['reference_code']) ?></strong></div>
            <div class="field-row"><span class="text-muted"><?= t('scan_confirm_customer') ?></span><strong><?= htmlspecialchars($rentalPickupPending['customer_name']) ?></strong></div>
            <div class="field-row"><span class="text-muted"><?= t('label_phone') ?></span><strong><?= htmlspecialchars($rentalPickupPending['customer_phone']) ?></strong></div>
            <div class="field-row"><span class="text-muted"><?= t('rent_th_dates') ?></span><strong><?= htmlspecialchars($rentalPickupPending['dates']) ?> (<?= (int)$rentalPickupPending['days'] ?> <?= t('rent_th_days') ?>)</strong></div>
            <div class="field-row"><span class="text-muted"><?= t('scan_confirm_payment_status') ?></span><strong><?= t('status_' . $rentalPickupPending['payment_status']) ?></strong></div>
            <?php if ((float)$rentalPickupPending['balance_due'] > 0.005): ?>
                <div class="field-row"><span class="text-muted"><?= t('scan_confirm_balance') ?></span><strong>₱<?= number_format($rentalPickupPending['balance_due'], 2) ?></strong></div>
            <?php endif; ?>

            <div class="modal-actions">
                <form method="POST" action="/sitrass/public/driver/verifyRentalPickupConfirm" style="flex:1;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="rental_id" value="<?= (int)$rentalPickupPending['rental_id'] ?>">
                    <button type="submit" class="btn"><?= t('rent_confirm_btn') ?></button>
                </form>
                <form method="POST" action="/sitrass/public/driver/verifyRentalPickupCancel" style="flex:1;">
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
                        <div style="position:absolute; inset:0; border:3px solid var(--amber-light); border-radius:var(--radius); pointer-events:none; opacity:0.6;"></div>
                    </div>
                    <p id="boardCameraStatus" class="text-sm text-muted" style="text-align:center; margin-bottom:0.75rem;"></p>
                    <button type="button" id="boardCameraToggleBtn" class="btn" onclick="toggleBoardCamera()" style="width:100%; display:flex; align-items:center; justify-content:center; gap:0.5rem; padding:0.6rem 1rem;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        <span id="boardCameraToggleLabel"><?= t('qr_camera_start') ?></span>
                    </button>
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

<!-- Rental Pickup Verification Modal (Camera + Manual QR scan) -->
<div class="modal-overlay" id="rentalPickupModal" style="display:none;">
    <div class="modal-box">
        <h3><?= t('rent_pickup_modal_title') ?></h3>

        <div class="qr-tabs">
            <button type="button" id="rentalCameraTabBtn" class="qr-tab active" onclick="showRentalTab('camera')"><?= t('qr_camera_tab') ?></button>
            <button type="button" id="rentalManualTabBtn" class="qr-tab" onclick="showRentalTab('manual')"><?= t('qr_manual_tab') ?></button>
        </div>

        <form method="POST" action="/sitrass/public/driver/verifyRentalPickup" id="rentalPickupForm">
            <?= Csrf::field() ?>

            <div id="rentalCameraTab">
                <div id="rentalCameraContainer" style="display:none; position:relative; border-radius:var(--radius); overflow:hidden; margin-bottom:0.75rem; background:#000;">
                    <video id="rentalVideo" style="width:100%; display:block;" playsinline></video>
                    <canvas id="rentalCanvas" style="display:none;"></canvas>
                    <div style="position:absolute; inset:0; border:3px solid var(--amber-light); border-radius:var(--radius); pointer-events:none; opacity:0.6;"></div>
                </div>
                <p id="rentalCameraStatus" class="text-sm text-muted" style="text-align:center; margin-bottom:0.75rem;"></p>
                <button type="button" id="rentalCameraToggleBtn" class="btn" onclick="toggleRentalCamera()" style="width:100%; display:flex; align-items:center; justify-content:center; gap:0.5rem; padding:0.6rem 1rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <span id="rentalCameraToggleLabel"><?= t('qr_camera_start') ?></span>
                </button>
            </div>

            <div id="rentalManualTab" style="display:none;">
                <div class="field">
                    <label><?= t('rent_pickup_ph') ?></label>
                    <input type="text" name="reference_code" id="rentalRefInput" placeholder="RNT-20260909-XXXX" style="font-family:'SF Mono', monospace; text-transform:uppercase;">
                </div>
            </div>

            <div class="modal-actions" style="margin-top:1rem;">
                <button type="submit" class="btn" style="flex:1;"><?= t('rent_pickup_btn') ?></button>
                <button type="button" class="btn-ghost" style="flex:1;" onclick="closeRentalModal();"><?= t('btn_cancel') ?></button>
            </div>
        </form>
    </div>
</div>

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
        <a href="/sitrass/public/messages/view/<?= (int)$activeBooking['booking_id'] ?>" class="btn-ghost" style="display:inline-block; padding:0.6rem 1rem;"><?= t('btn_chat') ?></a>
    </div>
<?php elseif (empty($activeRental)): ?>
    <p class="text-sm text-muted" style="margin-bottom:1.5rem;"><?= t('driver_no_active_trip') ?></p>
<?php endif; ?>

<?php if (!empty($activeRental)): ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="form-section-title" style="margin-bottom:0.75rem; border:none; padding:0;"><?= t('dashboard_active_title') ?></div>
        <?php if (!empty($activeRental['route_label'])): ?>
            <p style="margin:0 0 0.25rem;"><strong><?= htmlspecialchars($activeRental['route_label']) ?></strong></p>
        <?php endif; ?>
        <p class="text-sm text-muted" style="margin:0 0 0.75rem;">
            <?= htmlspecialchars($activeRental['reference_code']) ?> &middot; <?= htmlspecialchars($activeRental['customer_name']) ?> (<?= htmlspecialchars($activeRental['customer_phone']) ?>)<br>
            <?= htmlspecialchars($activeRental['make'] . ' ' . $activeRental['model']) ?> (<?= htmlspecialchars($activeRental['plate_number']) ?>) &middot; <?= htmlspecialchars($activeRental['start_date']) ?> &rarr; <?= htmlspecialchars($activeRental['end_date']) ?>
        </p>
        <form method="POST" action="/sitrass/public/driver/endRental" style="display:inline;">
            <?= Csrf::field() ?>
            <input type="hidden" name="rental_id" value="<?= (int)$activeRental['rental_id'] ?>">
            <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.4rem;"><?= t('btn_end_rental') ?></button>
        </form>
        <a href="/sitrass/public/messages/viewRental/<?= (int)$activeRental['rental_id'] ?>" class="btn-ghost" style="display:inline-block; padding:0.6rem 1rem;"><?= t('btn_chat') ?></a>
    </div>
<?php endif; ?>

<?php if (!empty($rentalPickups)): ?>
    <div class="card" style="margin-bottom:1.5rem; border-left:4px solid var(--amber, #A6650C);">
        <div class="form-section-title" style="margin-bottom:0.75rem; border:none; padding:0;"><?= t('rent_pickup_title') ?></div>
        <?php foreach ($rentalPickups as $rp): ?>
            <div style="padding:0.5rem 0; border-bottom:1px solid var(--border); margin-bottom:0.5rem;">
                <strong style="font-family:'SF Mono', monospace;"><?= htmlspecialchars($rp['reference_code'] ?? '—') ?></strong>
                — <?= htmlspecialchars($rp['make'] . ' ' . $rp['model']) ?> (<?= htmlspecialchars($rp['plate_number']) ?>)<br>
                <span class="text-sm text-muted"><?= t('label_customer') ?>: <?= htmlspecialchars($rp['customer_name']) ?> · <?= htmlspecialchars($rp['start_date']) ?> → <?= htmlspecialchars($rp['end_date']) ?></span>
            </div>
        <?php endforeach; ?>
        <button type="button" class="btn" style="width:auto; padding:0.6rem 1.4rem; margin-right:0.5rem;" onclick="openRentalModal();"><?= t('btn_verify') ?></button>
        <?php if (count($rentalPickups) === 1): ?>
            <a href="/sitrass/public/messages/viewRental/<?= (int)$rentalPickups[0]['rental_id'] ?>" class="btn-ghost" style="display:inline-block; padding:0.6rem 1rem;"><?= t('btn_chat') ?></a>
        <?php endif; ?>
        <p class="text-sm text-muted" style="margin:0.5rem 0 0;"><?= t('rent_pickup_note') ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message ?? '') ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error ?? '') ?></div>
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
function navIconHtml(headingDeg) {
    const rot = headingDeg || 0;
    return '<div class="map-pulse-marker" style="width:34px;height:34px;">' +
        '<div class="pulse-ring" style="background:transparent;"></div>' +
        '<div style="width:34px;height:34px;background:#1A73E8;border-radius:50%;border:3px solid white;box-shadow:0 2px 6px rgba(26,115,232,0.5);display:flex;align-items:center;justify-content:center;transform:rotate(' + rot + 'deg);">' +
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="white" stroke="none"><path d="M12 2 L18.5 20 L12 16.2 L5.5 20 Z"/></svg>' +
        '</div></div>';
}

function destIconHtml() {
    return '<div style="display:flex;flex-direction:column;align-items:center;filter:drop-shadow(0 2px 3px rgba(0,0,0,0.35));">' +
        '<div style="background:#C41E24; color:#fff; font-size:0.7rem; font-weight:700; padding:0.25rem 0.5rem; border-radius:6px; white-space:nowrap; margin-bottom:2px;">' + <?= json_encode(t('track_dest_label')) ?> + '</div>' +
        '<svg width="20" height="26" viewBox="0 0 24 32"><path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 20 12 20s12-11 12-20C24 5.4 18.6 0 12 0z" fill="#C41E24"/><circle cx="12" cy="12" r="4.5" fill="white"/></svg>' +
        '</div>';
}

// Normal na van icon (pre-booking / accepted) - babalik dito pagkatapos
// ng end trip; ang asul na arrow ay para lang sa biyaheng en_route.
function vanIconHtmlTeal(headingDeg) {
    const rot = headingDeg || 0;
    return '<div class="map-pulse-marker" style="width:34px;height:34px;">' +
        '<div class="pulse-ring" style="background:transparent;"></div>' +
        '<div style="width:34px;height:34px;background:var(--teal-dark);border-radius:50%;border:3px solid var(--amber-light);display:flex;align-items:center;justify-content:center;transform:rotate(' + rot + 'deg);">' +
        '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 8h13v9H1z"/><path d="M14 11h4l3 3v3h-7z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="17.5" cy="18.5" r="2"/></svg>' +
        '</div></div>';
}

let myMarker = null;
let customerMarker = null;
let routeLine = null;
let myPos = null;
let customerPos = null;
let mapCentered = false;

<?php
// Kung walang aktibong shared booking pero may aktibong RENTAL, ang mapa ay
// gagamit ng rental data: en_route mode na may ruta papunta sa destinasyon
// (pathway) at malaking "Pupuntahan" na pin - katulad ng shared booking.
$mapBooking = $activeBooking;
if (empty($mapBooking) && !empty($activeRental)) {
    $mapBooking = [
        'booking_id' => null,
        'status' => 'en_route',
        'pickup_lat' => $activeRental['pickup_lat'] ?? null,
        'pickup_lng' => $activeRental['pickup_lng'] ?? null,
        'dropoff_lat' => $activeRental['dropoff_lat'] ?? null,
        'dropoff_lng' => $activeRental['dropoff_lng'] ?? null,
    ];
}
?>

const bookingId = <?= json_encode($mapBooking['booking_id'] ?? null) ?>;
const rentalId = <?= json_encode($activeRental['rental_id'] ?? null) ?>;
const driverId = <?= json_encode($driverIdForGps) ?>;
let bookingStatus = <?= json_encode($mapBooking['status'] ?? null) ?>;

const statusText = document.getElementById('dashStatusText');

// Mga koordenada ng biyahe (pickup -> dropoff/destinasyon) para sa ruta
const pickupLat = <?= json_encode((float)($mapBooking['pickup_lat'] ?? 0)) ?>;
const pickupLng = <?= json_encode((float)($mapBooking['pickup_lng'] ?? 0)) ?>;
const dropoffLat = <?= json_encode((float)($mapBooking['dropoff_lat'] ?? 0)) ?>;
const dropoffLng = <?= json_encode((float)($mapBooking['dropoff_lng'] ?? 0)) ?>;

let destMarker = null;
let destRoute = null;
let destCoords = [];
let lastRoutePos = null;
let tripOver = false;

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

// Kung saan nakaturo ang daan malapit sa posisyon - para tumama ang arrow
// sa direksyon ng ruta kahit walang GPS heading (hal. desktop)
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

// RUTA PAPUNTA SA PUPUNTAHAN (en_route): totoong kalsada via OSRM.
// Iguguhit agad mula sa pickup sa page load, tapos muling iguguhit
// mula sa live na posisyon ng van habang gumagalaw ito.
function routeToDest(startPos) {
    if (!startPos || !dropoffLat || !dropoffLng) return;
    lastRoutePos = [startPos[0], startPos[1]];

    fetch('https://router.project-osrm.org/route/v1/driving/' + startPos[1] + ',' + startPos[0] + ';' + dropoffLng + ',' + dropoffLat + '?overview=full&geometries=geojson')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (tripOver) return;
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
            if (tripOver) return;
            if (destRoute) map.removeLayer(destRoute);
            destRoute = L.polyline([[startPos[0], startPos[1]], [dropoffLat, dropoffLng]], { color: '#188038', weight: 5, dashArray: '8 8', opacity: 0.8 }).addTo(map);
        });
}

function maybeRedrawDestRoute() {
    if (!myPos || bookingStatus !== 'en_route') return;
    if (!lastRoutePos || haversineKm(lastRoutePos[0], lastRoutePos[1], myPos[0], myPos[1]) > 0.15) {
        routeToDest(myPos);
    }
}

function sendMyLocation() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function(position) {
        myPos = [position.coords.latitude, position.coords.longitude];
        const headingDeg = position.coords.heading || 0;

        // Direksyon ng arrow: GPS heading kung mayroon, kung wala ay ang
        // direksyon ng ruta (en_route) para tumama sa daan
        let rotDeg = headingDeg;
        if ((!rotDeg || rotDeg === 0) && bookingStatus === 'en_route') {
            rotDeg = routeBearingAt(myPos);
        }

        // Asul na arrow LANG kapag biyahe na (en_route); balik sa normal na
        // van icon kapag pre-boarding pa lang o pagkatapos ng end trip.
        const driverIconHtml = (bookingStatus === 'en_route') ? navIconHtml(rotDeg) : vanIconHtmlTeal(headingDeg);

        if (!myMarker) {
            myMarker = L.marker(myPos, { icon: L.divIcon({ className: '', html: driverIconHtml, iconSize: [34, 34] }) }).addTo(map);
            myMarker.bindTooltip('0.0 km/h', { direction: 'top', offset: [0, -20] });
        } else {
            myMarker.setLatLng(myPos);
            myMarker.setIcon(L.divIcon({ className: '', html: driverIconHtml, iconSize: [34, 34] }));
        }

        // Hover tooltip: bilis mula sa GPS ng driver
        const nowKph = (position.coords.speed !== null && position.coords.speed !== undefined) ? position.coords.speed * 3.6 : 0;
        myMarker.setTooltipContent(nowKph.toFixed(1) + ' km/h');

        // Live status mula sa sariling GPS habang biyahe
        // (hindi na "Waiting for customer location..." kapag en_route)
        if (bookingStatus === 'en_route') {
            statusText.textContent = <?= json_encode(t('map_live_prefix')) ?> + ' 0 ' + <?= json_encode(t('map_seconds_ago')) ?>;
        }

        if (!mapCentered) {
            map.setView(myPos, 13);
            mapCentered = true;
        }

        // Isusulat ang GPS kapag accepted (papunta sa pick-up), en_route (biyahe na),
        // o may aktibong RENTAL (nasa biyahe na ang van sa customer).
        if ((bookingId || rentalId) && driverId && (bookingStatus === 'en_route' || bookingStatus === 'accepted')) {
            db.ref('driver_locations/' + driverId).set({
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                heading: position.coords.heading || 0,
                speed: position.coords.speed || 0,
                bookingId: bookingId || (rentalId ? 'rental-' + rentalId : null),
                updatedAt: Date.now()
            });
        }

        if (bookingStatus === 'accepted' && customerPos) {
            updateRouteAndEta(null);
        } else if (bookingStatus === 'en_route') {
            maybeRedrawDestRoute();
        }
    }, function() {
        // walang permission
    }, { enableHighAccuracy: true, timeout: 10000 });
}
sendMyLocation();
if ((bookingId || rentalId) && driverId && (bookingStatus === 'en_route' || bookingStatus === 'accepted')) {
    setInterval(sendMyLocation, 15000);
}

// Sa rental mode: walang customer GPS na sinusubaybayan - ang van lang.
// Ipaalam na live ang tracking mula sa van mismo.
if (!bookingId && rentalId) {
    statusText.textContent = <?= json_encode(t('rent_map_live')) ?>;
}

if (bookingId) {
    statusText.textContent = <?= json_encode(t('map_waiting_customer')) ?>;
    db.ref('customer_locations/' + bookingId).on('value', function(snapshot) {
        const data = snapshot.val();
        if (!data) return;

        // Kapag nagsimula na ang biyahe, naka-sakay na ang customer sa van -
        // hindi na kailangan (at hindi na accurate) ang GPS ng cellphone nito.
        if (bookingStatus !== 'accepted') return;

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

// --- En route: iguhit AGAD ang ruta papunta sa pupuntahan (mula sa pickup
//     habang wala pang GPS fix), kasama ang malaking "Pupuntahan" na pin ---
if (bookingStatus === 'en_route' && dropoffLat && dropoffLng) {
    destMarker = L.marker([dropoffLat, dropoffLng], {
        icon: L.divIcon({ className: '', html: destIconHtml(), iconSize: [26, 62], iconAnchor: [13, 60] })
    }).addTo(map);
    routeToDest([pickupLat, pickupLng]);
    map.setView([pickupLat, pickupLng], 12);
}

// --- Trip status polling (kada ~20s): kapag tapos na ang biyahe, maglilinis
//     ang mapa at mawawala ang lahat ng marker; kapag bagong nagsimula,
//     lilipat ang mapa sa "ruta papunta sa destinasyon" na mode ---
if (bookingId) {
    setInterval(function() {
        if (tripOver) return;
        fetch('/sitrass/public/driver/tripStatus/' + bookingId, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                const s = (d && d.status) ? d.status : null;
                if (!s || tripOver) return;

                if (s !== 'accepted' && s !== 'en_route') {
                    // Tapos na/cancelled: linisin ang lahat
                    tripOver = true;
                    if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
                    if (destRoute) { map.removeLayer(destRoute); destRoute = null; }
                    if (destMarker) { map.removeLayer(destMarker); destMarker = null; }
                    if (customerMarker) { map.removeLayer(customerMarker); customerMarker = null; }
                    if (myMarker) { map.removeLayer(myMarker); myMarker = null; }
                    const etaBanner = document.getElementById('etaBanner');
                    if (etaBanner) etaBanner.style.display = 'none';
                    statusText.textContent = <?= json_encode(t('track_trip_ended')) ?>;
                    setTimeout(function() { window.location.reload(); }, 4000);
                } else if (s === 'en_route' && bookingStatus !== 'en_route') {
                    // Bagong nagsimula ang biyahe: tanggalin ang customer marker,
                    // ilagay ang destinasyon + ruta
                    bookingStatus = 'en_route';
                    if (customerMarker) { map.removeLayer(customerMarker); customerMarker = null; }
                    if (routeLine) { map.removeLayer(routeLine); routeLine = null; }
                    if (!destMarker && dropoffLat && dropoffLng) {
                        destMarker = L.marker([dropoffLat, dropoffLng], {
                            icon: L.divIcon({ className: '', html: destIconHtml(), iconSize: [26, 62], iconAnchor: [13, 60] })
                        }).addTo(map);
                    }
                    if (myPos) { routeToDest(myPos); } else { routeToDest([pickupLat, pickupLng]); }
                }
            })
            .catch(function() {});
    }, 20000);
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
            var sp = toggleBtn.querySelector('span');
            if (sp) sp.textContent = <?= json_encode(t('qr_camera_stop')) ?>;
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
    if (toggleBtn) { var sp = toggleBtn.querySelector('span'); if (sp) sp.textContent = <?= json_encode(t('qr_camera_start')) ?>; }
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

// --- Rental pickup verification modal: camera + manual ---
function openRentalModal() {
    const modal = document.getElementById('rentalPickupModal');
    if (modal) modal.style.display = 'flex';
    showRentalTab('camera');
    startRentalCamera();
}
function closeRentalModal() {
    const modal = document.getElementById('rentalPickupModal');
    if (modal) modal.style.display = 'none';
    stopRentalCamera();
}
function showRentalTab(tab) {
    const cameraTab = document.getElementById('rentalCameraTab');
    const manualTab = document.getElementById('rentalManualTab');
    const cameraBtn = document.getElementById('rentalCameraTabBtn');
    const manualBtn = document.getElementById('rentalManualTabBtn');
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
        stopRentalCamera();
        document.getElementById('rentalRefInput').focus();
    }
}
let rentalCameraStream = null;
let rentalScanLoopId = null;
function toggleRentalCamera() {
    if (rentalCameraStream) { stopRentalCamera(); } else { startRentalCamera(); }
}
function startRentalCamera() {
    const statusEl = document.getElementById('rentalCameraStatus');
    const container = document.getElementById('rentalCameraContainer');
    const toggleBtn = document.getElementById('rentalCameraToggleBtn');
    const video = document.getElementById('rentalVideo');
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        statusEl.textContent = <?= json_encode(t('qr_camera_not_supported')) ?>;
        return;
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(stream) {
            rentalCameraStream = stream;
            video.srcObject = stream;
            video.setAttribute('playsinline', true);
            video.play();
            container.style.display = 'block';
            document.getElementById('rentalCameraToggleLabel').textContent = <?= json_encode(t('qr_camera_stop')) ?>;
            statusEl.textContent = <?= json_encode(t('qr_camera_scanning')) ?>;
            rentalScanLoopId = requestAnimationFrame(scanRentalFrame);
        })
        .catch(function() {
            statusEl.textContent = <?= json_encode(t('qr_camera_permission_denied')) ?>;
        });
}
function stopRentalCamera() {
    if (rentalScanLoopId) { cancelAnimationFrame(rentalScanLoopId); rentalScanLoopId = null; }
    if (rentalCameraStream) { rentalCameraStream.getTracks().forEach(function(t) { t.stop(); }); rentalCameraStream = null; }
    const container = document.getElementById('rentalCameraContainer');
    if (container) container.style.display = 'none';
    const toggleBtn = document.getElementById('rentalCameraToggleBtn');
    if (toggleBtn) {
        const lbl = document.getElementById('rentalCameraToggleLabel');
        if (lbl) lbl.textContent = <?= json_encode(t('qr_camera_start')) ?>;
    }
    const statusEl = document.getElementById('rentalCameraStatus');
    if (statusEl) statusEl.textContent = '';
}
function scanRentalFrame() {
    const video = document.getElementById('rentalVideo');
    const canvas = document.getElementById('rentalCanvas');
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code && code.data) {
            document.getElementById('rentalCameraStatus').textContent = <?= json_encode(t('qr_camera_detected')) ?>;
            document.getElementById('rentalRefInput').value = code.data.trim();
            stopRentalCamera();
            document.getElementById('rentalPickupForm').submit();
            return;
        }
    }
    rentalScanLoopId = requestAnimationFrame(scanRentalFrame);
}
</script>

<?php require __DIR__ . '/_driver_footer.php'; ?>
