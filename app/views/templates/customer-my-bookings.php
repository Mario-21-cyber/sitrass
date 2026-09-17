<?php require __DIR__ . '/_customer_header.php'; ?>

<?php
$activeTab = $_GET['tab'] ?? 'bookings';
?>

<div class="tabs" style="margin-bottom:1rem;">
    <a href="/sitrass/public/customer/myBookings?tab=bookings" class="tab-link <?= $activeTab === 'bookings' ? 'active' : '' ?>"><?= t('tab_shared_trips') ?></a>
    <a href="/sitrass/public/customer/myBookings?tab=rentals" class="tab-link <?= $activeTab === 'rentals' ? 'active' : '' ?>"><?= t('tab_van_rentals') ?></a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message ?? '') ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error ?? '') ?></div>
<?php endif; ?>

<?php if ($activeTab === 'rentals'): ?>
    <?php if (empty($myRentals)): ?>
        <p class="text-sm text-muted"><?= t('rent_no_rentals') ?></p>
    <?php else: ?>
        <?php foreach ($myRentals as $r): ?>
            <?php
            // Parehong lohika ng Shared Trips: kung wala pang bayad,
            // ipakita ang deposit; kung may balance, iyon; kung buo, "Bayad na nang Buo."
            $verifiedTotal = (float)array_sum(array_column($r['payment_history'] ?? [], 'amount'));
            $balanceDue = max(0, round((float)$r['total_price'] - $verifiedTotal, 2));
            $paymentStarted = $verifiedTotal > 0 || $r['payment_status'] !== 'pending';
            if (!$paymentStarted) {
                $displayLabel = t('amount_due_label');
                $displayAmount = (float)$r['deposit_required'];
            } elseif ($balanceDue > 0.005) {
                $displayLabel = t('amount_due_label');
                $displayAmount = $balanceDue;
            } else {
                $displayLabel = t('amount_paid_full_label');
                $displayAmount = (float)$r['total_price'];
            }
            $badgeClass = match($r['status']) {
                'pending' => 'badge-pending',
                'active' => 'badge-active',
                'confirmed' => 'badge-info',
                'completed' => 'badge-neutral',
                'cancelled' => 'badge-danger',
                default => 'badge-neutral',
            };
            $badgeLabel = $r['status'] === 'active' ? t('rent_status_active_lbl') : t('status_' . $r['status']);
            ?>
            <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem; margin-bottom:1rem;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($r['reference_code'] ?? '—') ?></span><br>
                        <span style="font-size:0.85rem;"><?= t('th_date') ?>: <?= htmlspecialchars($r['start_date'] ?? '—') ?> <?php if (!empty($r['end_date']) && $r['end_date'] !== $r['start_date']): ?>&rarr; <?= htmlspecialchars($r['end_date']) ?><?php endif; ?> &middot; <?= (int)($r['days'] ?? 1) ?> <?= t('rent_th_days') ?></span>
                    </div>
                    <div style="text-align:right;">
                        <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span><br>
                        <span class="text-sm text-muted" style="display:block;"><?= $displayLabel ?></span>
                        <span style="font-size:0.9rem; font-weight:600;">₱<?= number_format($displayAmount, 2) ?></span>
                    </div>
                </div>
                <?php if (!empty($r['payment_history'])): ?>
                    <div class="action-row">
                        <div class="text-sm" style="font-weight:600; margin-bottom:0.4rem;"><?= t('payment_history_title') ?></div>
                        <?php foreach ($r['payment_history'] as $p): ?>
                            <div style="display:flex; justify-content:space-between; font-size:0.82rem; padding:0.25rem 0;">
                                <span class="text-muted">
                                    <?= t('payment_type_' . $p['payment_type']) ?> &middot; <?= htmlspecialchars($p['method_name'] ?? '') ?> &middot; <?= t('label_verified_on') ?> <?= htmlspecialchars($p['verified_at'] ?? '') ?>
                                </span>
                                <span style="font-weight:600;">₱<?= number_format((float)$p['amount'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (in_array($r['status'], ['pending', 'confirmed'])): ?>
                    <div class="action-row">
                        <?php if (!$paymentStarted): ?>
                            <a href="/sitrass/public/customer/rentPay/<?= (int)$r['rental_id'] ?>" class="btn-sm btn-primary"><?= t('btn_pay') ?></a>
                        <?php else: ?>
                            <a href="/sitrass/public/customer/viewRentalQr/<?= (int)$r['rental_id'] ?>" class="btn-sm btn-primary"><?= t('btn_view_qr') ?></a>
                            <?php if ($balanceDue > 0.005): ?>
                                <a href="/sitrass/public/customer/rentPay/<?= (int)$r['rental_id'] ?>" class="btn-sm btn-outline"><?= t('btn_pay_balance') ?></a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <form method="POST" action="/sitrass/public/customer/cancelRental" style="display:inline;" onsubmit="return confirm('Sigurado kang kanselahin ang rental na ito?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="rental_id" value="<?= (int)$r['rental_id'] ?>">
                            <button type="submit" class="btn-sm btn-danger"><?= t('btn_cancel') ?></button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($r['status'] === 'active'): ?>
                    <?php // Nasa biyahe na: driver info + live track + chat (katulad ng shared booking) ?>
                    <div class="action-row">
                        <div class="text-sm" style="margin-bottom:0.5rem;">
                            <strong><?= t('label_driver') ?>:</strong> <?= htmlspecialchars($r['driver_name'] ?? t('no_driver_yet')) ?>
                            <?php if (!empty($r['driver_phone'])): ?>
                                &middot; <?= htmlspecialchars($r['driver_phone']) ?>
                            <?php endif; ?>
                        </div>
                        <a href="/sitrass/public/customer/trackRental/<?= (int)$r['rental_id'] ?>" class="btn-sm btn-primary"><?= t('rent_track_btn') ?></a>
                        <a href="/sitrass/public/messages/viewRental/<?= (int)$r['rental_id'] ?>" class="btn-sm btn-outline"><?= t('btn_chat') ?></a>
                    </div>
                <?php endif; ?>

                <?php if ($r['status'] === 'completed' && $balanceDue > 0.005): ?>
                    <div class="action-row">
                        <a href="/sitrass/public/customer/rentPay/<?= (int)$r['rental_id'] ?>" class="btn-sm btn-primary"><?= t('btn_pay_balance') ?> (₱<?= number_format($balanceDue, 2) ?>)</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php else: ?>
<?php if (empty($reservations)): ?>
    <p><?= t('empty_no_bookings') ?> <a href="/sitrass/public/customer/search"><?= t('link_find_trip') ?></a>.</p>
<?php else: ?>
    <?php foreach ($reservations as $r): ?>
        <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem; margin-bottom:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($r['reference_code']) ?></span><br>
                                        <span style="font-size:0.85rem;"><?= t('th_date') ?>: <?= htmlspecialchars($r['first_travel_date'] ?? '—') ?> &middot; <?= (int)$r['passenger_count'] ?> <?= t('unit_passengers') ?></span>
                </div>
                                <?php
                    // Iisang lohika para sa lahat ng reservation, hindi na isa-isang
                    // kaso: kung wala pang bayad, ipakita ang kailangang deposit;
                    // kung may natitirang balance, ipakita iyon; kung bayad na nang
                    // buo, ipakita ang total bilang "Bayad na nang Buo."
                    if ($r['payment_status'] === 'pending') {
                        $displayLabel = t('amount_due_label');
                        $displayAmount = $r['deposit_required'];
                    } elseif ($r['balance_due'] > 0) {
                        $displayLabel = t('amount_due_label');
                        $displayAmount = $r['balance_due'];
                    } else {
                        $displayLabel = t('amount_paid_full_label');
                        $displayAmount = $r['total_amount'];
                    }
                ?>
                                <div style="text-align:right;">
                    <span class="badge <?= $r['status'] === 'confirmed' ? 'badge-active' : 'badge-pending' ?>"><?= t('status_' . $r['status']) ?></span><br>
                    <span class="text-sm text-muted" style="display:block;"><?= $displayLabel ?></span>
                    <span style="font-size:0.9rem; font-weight:600;">₱<?= htmlspecialchars($displayAmount ?? '') ?></span>
                </div>
            </div>

            <?php if (!empty($r['payment_history'])): ?>
                <div class="action-row">
                    <div class="text-sm" style="font-weight:600; margin-bottom:0.4rem;"><?= t('payment_history_title') ?></div>
                    <?php foreach ($r['payment_history'] as $p): ?>
                        <div style="display:flex; justify-content:space-between; font-size:0.82rem; padding:0.25rem 0;">
                            <span class="text-muted">
                                <?= t('payment_type_' . $p['payment_type']) ?>
                                &middot; <?= htmlspecialchars($p['method_name']) ?>
                                &middot; <?= t('label_verified_on') ?> <?= htmlspecialchars($p['verified_at']) ?>
                            </span>
                            <span style="font-weight:600;">₱<?= number_format($p['amount'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

                        <?php if (in_array($r['status'], ['pending', 'confirmed']) && !in_array($r['first_booking_status'] ?? '', ['en_route', 'completed'])): ?>
                <div class="action-row">
                    <?php if ($r['payment_status'] === 'pending'): ?>
                        <a href="/sitrass/public/customer/payReservation/<?= htmlspecialchars($r['reference_code']) ?>" class="btn-sm btn-primary"><?= t('btn_pay') ?></a>
                    <?php else: ?>
                        <a href="/sitrass/public/customer/viewQr/<?= htmlspecialchars($r['reference_code']) ?>" class="btn-sm btn-primary"><?= t('btn_view_qr') ?></a>
                        <?php if ($r['balance_due'] > 0): ?>
                            <a href="/sitrass/public/customer/payReservation/<?= htmlspecialchars($r['reference_code']) ?>" class="btn-sm btn-outline"><?= t('btn_pay_balance') ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($r['first_booking_id']) && in_array($r['first_booking_status'] ?? '', ['accepted','en_route'])): ?>
                        <a href="/sitrass/public/messages/view/<?= (int)$r['first_booking_id'] ?>" class="btn-sm btn-outline"><?= t('btn_chat') ?></a>
                    <?php endif; ?>
                    <a href="/sitrass/public/customer/rescheduleBooking/<?= htmlspecialchars($r['reference_code']) ?>" class="btn-sm btn-outline"><?= t('btn_reschedule') ?></a>
                    <form method="POST" action="/sitrass/public/customer/cancelBooking" style="display:inline;" onsubmit="return confirm('Sigurado kang kanselahin ang booking na ito?');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="reference_code" value="<?= htmlspecialchars($r['reference_code']) ?>">
                        <button type="submit" class="btn-sm btn-danger"><?= t('btn_cancel') ?></button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/_customer_footer.php'; ?>
