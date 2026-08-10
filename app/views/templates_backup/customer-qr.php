<?php require __DIR__ . '/_customer_header.php'; ?>

<h2>QR Code para sa Booking</h2>

<div class="ticket-stub">
    <div class="stub-label">Reference Code</div>
    <div class="stub-code"><?= htmlspecialchars($reservation['reference_code']) ?></div>
</div>

<?php if ($qr['raw_token']): ?>
    <div class="card" style="text-align:center; padding:2rem;">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($qr['raw_token']) ?>" alt="QR code para sa booking" style="margin-bottom:1rem;">
        <p style="font-size:0.9rem; color:var(--ocean); margin:0;">Ipakita ito sa driver kapag sumakay ka na.</p>
    </div>
<?php else: ?>
    <div class="alert alert-error">Hindi ma-display ang QR code. Kontakin ang admin.</div>
<?php endif; ?>

<div class="card">
    <p><strong>Petsa:</strong> <?= htmlspecialchars($booking['travel_date']) ?> @ <?= htmlspecialchars($booking['pickup_time']) ?></p>
    <p><strong>Bilang ng Pasahero:</strong> <?= (int)$booking['seats_booked'] ?></p>
    <p style="margin:0;"><strong>Status:</strong> <span class="badge <?= $qr['status'] === 'active' ? 'badge-active' : 'badge-pending' ?>"><?= htmlspecialchars($qr['status']) ?></span></p>
</div>

<a href="/sitrass/public/customer/myBookings" class="btn-link">Bumalik</a>

<?php require __DIR__ . '/_customer_footer.php'; ?>