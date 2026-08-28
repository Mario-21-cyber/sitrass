<?php require __DIR__ . '/_customer_header.php'; ?>



<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (empty($reservations)): ?>
    <p>Wala ka pang booking. <a href="/sitrass/public/customer/search">Maghanap ng biyahe</a>.</p>
<?php else: ?>
    <?php foreach ($reservations as $r): ?>
        <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem; margin-bottom:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($r['reference_code']) ?></span><br>
                    <span style="font-size:0.85rem;"><?= t('th_date') ?>: <?= htmlspecialchars($r['first_travel_date']) ?> &middot; <?= (int)$r['passenger_count'] ?> <?= t('unit_passengers') ?></span>
                </div>
                <div style="text-align:right;">
                    <span class="badge <?= $r['status'] === 'confirmed' ? 'badge-active' : 'badge-pending' ?>"><?= t('status_' . $r['status']) ?></span><br>
                    <span style="font-size:0.85rem;">₱<?= htmlspecialchars($r['total_amount']) ?></span>
                </div>
            </div>

                        <?php if (in_array($r['status'], ['pending', 'confirmed']) && !in_array($r['first_booking_status'] ?? '', ['en_route', 'completed'])): ?>
                <div style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid var(--border);">
                    <?php if ($r['payment_status'] === 'pending'): ?>
                        <a href="/sitrass/public/customer/payReservation/<?= htmlspecialchars($r['reference_code']) ?>" class="btn" style="display:inline-block; width:auto; padding:0.3rem 0.8rem; text-decoration:none; font-size:0.8rem;"><?= t('btn_pay') ?></a>
                    <?php else: ?>
                        <a href="/sitrass/public/customer/viewQr/<?= htmlspecialchars($r['reference_code']) ?>" class="btn" style="display:inline-block; width:auto; padding:0.3rem 0.8rem; text-decoration:none; font-size:0.8rem;"><?= t('btn_view_qr') ?></a>
                        <?php if ($r['balance_due'] > 0): ?>
                            <a href="/sitrass/public/customer/payReservation/<?= htmlspecialchars($r['reference_code']) ?>" style="display:inline-block; padding:0.3rem 0.8rem; text-decoration:none; font-size:0.8rem; margin-left:0.3rem;"><?= t('btn_pay_balance') ?></a>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($r['first_booking_id']) && in_array($r['first_booking_status'] ?? '', ['accepted','en_route'])): ?>
                        <a href="/sitrass/public/chat/open/<?= (int)$r['first_booking_id'] ?>" style="display:inline-block; padding:0.3rem 0.8rem; text-decoration:none; font-size:0.8rem; margin-left:0.4rem;"><?= t('btn_chat') ?></a>
                    <?php endif; ?>
                    <a href="/sitrass/public/customer/rescheduleBooking/<?= htmlspecialchars($r['reference_code']) ?>" style="display:inline-block; padding:0.3rem 0.8rem; text-decoration:none; font-size:0.8rem; margin-left:0.4rem;"><?= t('btn_reschedule') ?></a>
                    <form method="POST" action="/sitrass/public/customer/cancelBooking" style="display:inline;" onsubmit="return confirm('Sigurado kang kanselahin ang booking na ito?');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="reference_code" value="<?= htmlspecialchars($r['reference_code']) ?>">
                        <button type="submit" style="width:auto; padding:0.3rem 0.8rem; font-size:0.8rem; margin-left:0.4rem; background:var(--danger); color:#fff; border:none; border-radius:4px; cursor:pointer;"><?= t('btn_cancel') ?></button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_customer_footer.php'; ?>