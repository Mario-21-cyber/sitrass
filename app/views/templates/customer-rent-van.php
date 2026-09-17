<?php require __DIR__ . '/_customer_header.php'; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success ?? '') ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error ?? '') ?></div>
<?php endif; ?>

<p class="text-sm text-muted" style="margin-bottom:1rem;"><?= t('rent_intro') ?></p>

<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:1rem; margin-bottom:2rem;">
    <?php foreach ($vans as $v): ?>
        <?php
            $badgeClass = 'badge-active';
            if ($v['rent_status'] !== 'available') { $badgeClass = 'badge-pending'; }
            if ($v['rent_status'] === 'maintenance' || $v['rent_status'] === 'inactive') { $badgeClass = 'badge-neutral'; }
        ?>
        <div class="card" style="padding:0; overflow:hidden; display:flex; flex-direction:column;">
            <?php if (!empty($v['primary_image'])): ?>
                <img src="<?= htmlspecialchars($v['primary_image']) ?>" alt="" style="width:100%; height:140px; object-fit:cover; display:block;">
            <?php else: ?>
                <div style="height:140px; background:var(--slate-bg); display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--slate)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:44px;height:44px;"><path d="M1 8h13v9H1z"/><path d="M14 11h4l3 3v3h-7z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="17.5" cy="18.5" r="2"/></svg>
                </div>
            <?php endif; ?>
            <div style="padding:0.9rem; display:flex; flex-direction:column; flex:1;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:0.4rem;">
                    <strong><?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?></strong>
                    <span class="badge <?= $badgeClass ?>"><?= t('rent_status_' . $v['rent_status']) ?></span>
                </div>
                <div class="text-sm text-muted" style="margin:0.25rem 0 0.5rem; font-family:'SF Mono', monospace;"><?= htmlspecialchars($v['plate_number']) ?></div>
                <div class="text-sm text-muted" style="margin-bottom:0.25rem;">
                    <?= (int)$v['seating_capacity'] ?> <?= t('unit_passengers') ?>
                    <?php if ($v['has_aircon']): ?> &middot; <?= t('label_has_aircon') ?><?php endif; ?>
                    <?php if ($v['has_wifi']): ?> &middot; <?= t('label_has_wifi') ?><?php endif; ?>
                </div>
                <div class="text-sm text-muted" style="margin-bottom:0.5rem;">
                    <?= t('label_driver') ?>: <strong><?= htmlspecialchars($v['driver_name'] ?: '—') ?></strong>
                </div>
                <div style="font-weight:700; margin-bottom:0.75rem;">
                    <?= t('currency_symbol') ?><?= number_format((float)$v['whole_van_day_rate'], 2) ?><span class="text-sm text-muted"> <?= t('rent_per_day') ?></span>
                </div>
                <div style="margin-top:auto;">
                    <?php if ($v['rent_status'] === 'available' && (float)$v['whole_van_day_rate'] > 0): ?>
                        <a href="/sitrass/public/customer/rentVanBook/<?= (int)$v['van_id'] ?>" class="btn" style="text-decoration:none; display:block; text-align:center;"><?= t('rent_btn_book') ?></a>
                    <?php else: ?>
                        <button class="btn" disabled style="opacity:0.5; cursor:not-allowed;"><?= t('rent_unavailable_btn') ?></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/_customer_footer.php'; ?>
