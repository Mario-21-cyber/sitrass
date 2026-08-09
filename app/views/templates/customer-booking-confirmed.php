<?php require __DIR__ . '/_customer_header.php'; ?>

<h2>Nakumpirma ang Booking!</h2>

<div class="ticket-stub">
    <div class="stub-label">Reference Code</div>
    <div class="stub-code"><?= htmlspecialchars($reservation['reference_code']) ?></div>
</div>

<div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.5rem;">
    <p><strong>Bilang ng Pasahero:</strong> <?= (int)$reservation['passenger_count'] ?></p>
    <p><strong>Kabuuang Halaga:</strong> ₱<?= htmlspecialchars($reservation['total_amount']) ?></p>
    <p><strong>Kailangang Deposit (<?= htmlspecialchars(rtrim(rtrim(number_format($reservation['deposit_percentage'], 2), '0'), '.')) ?>%):</strong> ₱<?= htmlspecialchars($reservation['deposit_required']) ?></p>
    <p><strong>Status:</strong> <span class="badge badge-pending"><?= htmlspecialchars($reservation['status']) ?></span></p>
</div>

<p style="margin-top:1rem; font-size:0.9rem;">
    Bayaran ang deposit sa loob ng 2 oras para hindi ma-cancel ang reservation. (Payment system, susunod pa itong idadagdag.)
</p>

<a href="/sitrass/public/customer/myBookings" class="btn-link">Tingnan ang Aking Mga Booking</a>

<?php require __DIR__ . '/_customer_footer.php'; ?>