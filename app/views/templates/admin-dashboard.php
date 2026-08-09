<?php require __DIR__ . '/_admin_header.php'; ?>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">

    <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem;">
        <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--ocean);">Kita (verified)</div>
        <div style="font-size:1.6rem; font-weight:700; color:var(--teal-dark); font-family:'SF Mono', monospace;">₱<?= number_format($revenueStats['total_verified'], 2) ?></div>
        <div style="font-size:0.8rem; color:var(--forest);">₱<?= number_format($revenueStats['today_verified'], 2) ?> ngayong araw</div>
    </div>

    <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem;">
        <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--ocean);">Mga Reservation</div>
        <div style="font-size:1.6rem; font-weight:700; color:var(--teal-dark);"><?= (int)$reservationStats['total_reservations'] ?></div>
        <div style="font-size:0.8rem;"><?= (int)$reservationStats['pending_count'] ?> pending &middot; <?= (int)$reservationStats['confirmed_count'] ?> confirmed</div>
    </div>

    <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem;">
        <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--ocean);">Mga Van</div>
        <div style="font-size:1.6rem; font-weight:700; color:var(--teal-dark);"><?= (int)$vanStats['total_vans'] ?></div>
        <div style="font-size:0.8rem;"><?= (int)$vanStats['active_count'] ?> active &middot; <?= (int)$vanStats['maintenance_count'] ?> maintenance</div>
    </div>

    <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem;">
        <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--ocean);">Mga User</div>
        <div style="font-size:1.6rem; font-weight:700; color:var(--teal-dark);"><?= (int)$userStats['active_customers'] ?></div>
        <div style="font-size:0.8rem;"><?= (int)$userStats['active_drivers'] ?> driver &middot; <?= (int)$userStats['pending_accounts'] ?> pending</div>
    </div>

    <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem;">
        <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--ocean);">Naghihintay na Payment</div>
        <div style="font-size:1.6rem; font-weight:700; color:var(--amber);"><?= (int)$revenueStats['pending_count'] ?></div>
        <div style="font-size:0.8rem;"><a href="/sitrass/public/payments">Tingnan ang mga ito</a></div>
    </div>

</div>

<h3>Kita sa Nakaraang 7 Araw</h3>

<?php if (empty($dailyRevenue)): ?>
    <p>Wala pang naitalang verified na bayad.</p>
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