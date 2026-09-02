<?php require __DIR__ . '/_admin_header.php'; ?>

<div class="section-heading">
    <h3 style="margin:0;"><?= t('page_fleet_overview') ?></h3>
    <a href="/sitrass/public/vans/create" class="btn" style="width:auto; padding:0.6rem 1.2rem;">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <?= t('btn_add_van') ?>
    </a>
</div>

<?php if (empty($vans)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--border);margin-bottom:0.75rem;"><path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
        <div><?= t('empty_no_vans') ?></div>
        <div class="text-sm"><?= t('empty_no_vans_sub') ?></div>
    </div>
<?php else: ?>
    <table>
        <tr>
            <th><?= t('th_plate_number') ?></th>
            <th><?= t('th_van') ?></th>
            <th><?= t('th_type') ?></th>
            <th><?= t('th_seats') ?></th>
            <th><?= t('th_status') ?></th>
            <th><?= t('th_action') ?></th>
        </tr>
        <?php foreach ($vans as $van): ?>
            <tr>
                <td style="font-family:'SF Mono', monospace; font-weight:600;"><?= htmlspecialchars($van['plate_number']) ?></td>
                <td><?= htmlspecialchars($van['make'] . ' ' . $van['model']) ?></td>
                <td><span class="badge badge-neutral"><?= t('vantype_' . $van['van_type']) ?></span></td>
                <td><?= (int)$van['seating_capacity'] ?></td>
                <td>
                    <span class="badge <?= $van['status'] === 'active' ? 'badge-active' : 'badge-pending' ?>">
                        <?= t('status_' . $van['status']) ?>
                    </span>
                </td>
                <td>
                    <a href="/sitrass/public/vans/images/<?= (int)$van['van_id'] ?>" class="btn-ghost" style="margin-right:0.5rem;"><?= t('link_images') ?></a>
                    <?php if ($van['status'] === 'active'): ?>
                        <form method="POST" action="/sitrass/public/vans/toggleStatus" style="display:inline;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="van_id" value="<?= (int)$van['van_id'] ?>">
                            <input type="hidden" name="status" value="maintenance">
                            <button type="submit" class="btn-ghost" style="color:#A6650C !important;"><?= t('btn_set_maintenance') ?></button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/sitrass/public/vans/toggleStatus" style="display:inline;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="van_id" value="<?= (int)$van['van_id'] ?>">
                            <input type="hidden" name="status" value="active">
                            <button type="submit" class="btn-ghost" style="color:var(--forest) !important;"><?= t('btn_set_active') ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>
