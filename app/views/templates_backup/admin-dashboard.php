<?php require __DIR__ . '/_admin_header.php'; ?>

<div class="stat-grid">

    <div class="stat-card">
        <div class="stat-label">Kita (verified)</div>
        <div class="stat-value">₱<?= number_format($revenueStats['total_verified'], 2) ?></div>
        <div class="stat-sub highlight">₱<?= number_format($revenueStats['today_verified'], 2) ?> ngayong araw</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Mga Reservation</div>
        <div class="stat-value"><?= (int)$reservationStats['total_reservations'] ?></div>
        <div class="stat-sub"><?= (int)$reservationStats['pending_count'] ?> pending &middot; <?= (int)$reservationStats['confirmed_count'] ?> confirmed</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Mga Van</div>
        <div class="stat-value"><?= (int)$vanStats['total_vans'] ?></div>
        <div class="stat-sub"><?= (int)$vanStats['active_count'] ?> active &middot; <?= (int)$vanStats['maintenance_count'] ?> maintenance</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Mga User</div>
        <div class="stat-value"><?= (int)$userStats['active_customers'] ?></div>
        <div class="stat-sub"><?= (int)$userStats['active_drivers'] ?> driver &middot; <?= (int)$userStats['pending_accounts'] ?> pending</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Naghihintay na Payment</div>
        <div class="stat-value amber"><?= (int)$revenueStats['pending_count'] ?></div>
        <div class="stat-sub"><a href="/sitrass/public/payments">Tingnan ang mga ito</a></div>
    </div>

</div>

<h3>Kita sa Nakaraang 7 Araw</h3>

<?php if (empty($dailyRevenue)): ?>
    <div class="empty-state">Wala pang naitalang verified na bayad.</div>
<?php else: ?>
    <table>
        <tr>
            <th>Petsa</th>
            <th>Paraan</th>
            <th>Bilang ng Transaksyon</th>
            <th>Kabuuang Halaga</th>
        </tr>
        <?php foreach ($dailyRevenue as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['revenue_date']) ?></td>
                <td><?= htmlspecialchars($d['method_code']) ?></td>
                <td><?= (int)$d['transaction_count'] ?></td>
                <td>₱<?= number_format($d['net_amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>