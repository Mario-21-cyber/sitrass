<?php require __DIR__ . '/_admin_header.php'; ?>

<div class="section-heading">
    <h3 style="margin:0;"><?= t('routes_page_title') ?></h3>
    <a href="/sitrass/public/routes/create" class="btn" style="width:auto; padding:0.6rem 1.2rem;">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <?= t('btn_add_route') ?>
    </a>
</div>

<?php if (empty($routes)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--border);margin-bottom:0.75rem;"><circle cx="6" cy="19" r="3"/><circle cx="18" cy="5" r="3"/><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/></svg>
        <div><?= t('empty_no_routes') ?></div>
    </div>
<?php else: ?>
    <table>
        <tr>
            <th><?= t('th_code') ?></th>
            <th><?= t('th_name') ?></th>
            <th><?= t('th_from') ?></th>
            <th><?= t('th_to') ?></th>
            <th><?= t('th_distance') ?></th>
            <th><?= t('th_est_time') ?></th>
        </tr>
        <?php foreach ($routes as $route): ?>
            <tr>
                <td><span class="badge badge-neutral" style="font-family:'SF Mono', monospace;"><?= htmlspecialchars($route['route_code']) ?></span></td>
                <td><?= htmlspecialchars($route['route_name']) ?></td>
                <td><?= htmlspecialchars($route['origin_name']) ?></td>
                <td><?= htmlspecialchars($route['destination_name']) ?></td>
                <td><?= htmlspecialchars($route['distance_km']) ?> km</td>
                <td><?= htmlspecialchars($route['estimated_duration_minutes']) ?> min</td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>
