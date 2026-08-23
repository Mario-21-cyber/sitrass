<?php require __DIR__ . '/_admin_header.php'; ?>

<div class="stat-grid">

    <div class="stat-card stat-primary stat-featured">
        <div class="stat-top-row">
            <div class="stat-label"><?= t('dash_revenue') ?></div>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
        </div>
        <div class="stat-value">₱<?= number_format($revenueStats['total_verified'], 2) ?></div>
        <div class="stat-sub highlight">₱<?= number_format($revenueStats['today_verified'], 2) ?> <?= t('dash_revenue_today') ?></div>
    </div>

    <div class="stat-card stat-info">
        <div class="stat-top-row">
            <div class="stat-label"><?= t('dash_reservations') ?></div>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
        </div>
        <div class="stat-value"><?= (int)$reservationStats['total_reservations'] ?></div>
        <div class="stat-sub"><?= (int)$reservationStats['pending_count'] ?> <?= sprintf(t('dash_reservations_sub'), (int)$reservationStats['confirmed_count']) ?></div>
    </div>

    <div class="stat-card stat-primary">
        <div class="stat-top-row">
            <div class="stat-label"><?= t('dash_vans') ?></div>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
            </div>
        </div>
        <div class="stat-value"><?= (int)$vanStats['total_vans'] ?></div>
        <div class="stat-sub"><?= (int)$vanStats['active_count'] ?> <?= sprintf(t('dash_vans_sub'), (int)$vanStats['maintenance_count']) ?></div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-top-row">
            <div class="stat-label"><?= t('dash_users') ?></div>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
        </div>
        <div class="stat-value"><?= (int)$userStats['active_customers'] ?></div>
        <div class="stat-sub"><?= (int)$userStats['active_drivers'] ?> <?= sprintf(t('dash_users_sub'), (int)$userStats['pending_accounts']) ?></div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-top-row">
            <div class="stat-label"><?= t('dash_pending_payment') ?></div>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
        </div>
        <div class="stat-value amber"><?= (int)$revenueStats['pending_count'] ?></div>
        <div class="stat-sub"><a href="/sitrass/public/payments"><?= t('dash_view_these') ?> &rarr;</a></div>
    </div>

</div>

<div class="section-heading">
    <h3><?= t('dash_revenue_7days') ?></h3>
</div>

<?php if (empty($dailyRevenue)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--border);margin-bottom:0.75rem;"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        <div><?= t('dash_no_revenue') ?></div>
    </div>
<?php else: ?>
    <table>
        <tr>
            <th><?= t('th_date') ?></th>
            <th><?= t('th_method') ?></th>
            <th><?= t('th_transaction_count') ?></th>
            <th><?= t('th_total_amount') ?></th>
        </tr>
        <?php foreach ($dailyRevenue as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['revenue_date']) ?></td>
                <td><span class="badge badge-neutral"><?= htmlspecialchars($d['method_code']) ?></span></td>
                <td><?= (int)$d['transaction_count'] ?></td>
                <td>₱<?= number_format($d['net_amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>