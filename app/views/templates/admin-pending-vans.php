<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message ?? '') ?></div>
<?php endif; ?>

<?php if (empty($vans)): ?>
    <div class="empty-state"><?= t('vans_pending_empty') ?></div>
<?php else: ?>
    <table>
        <tr>
            <th><?= t('th_plate_number') ?></th>
            <th><?= t('th_van') ?></th>
            <th><?= t('th_type') ?></th>
            <th><?= t('th_seats') ?></th>
            <th><?= t('myvans_owner') ?></th>
            <th><?= t('rent_price_label') ?></th>
            <th><?= t('th_action') ?></th>
        </tr>
        <?php foreach ($vans as $van): ?>
            <tr>
                <td style="font-family:'SF Mono', monospace; font-weight:600;"><?= htmlspecialchars($van['plate_number']) ?></td>
                <td><?= htmlspecialchars($van['make'] . ' ' . $van['model']) ?></td>
                <td><span class="badge badge-neutral"><?= t('vantype_' . $van['van_type']) ?></span></td>
                <td><?= (int)$van['seating_capacity'] ?></td>
                <td><?= htmlspecialchars($van['owner_name'] ?: '—') ?></td>
                <td>₱<?= number_format((float)$van['whole_van_day_rate'], 2) ?><?= t('rent_per_day') ?></td>
                <td style="white-space:nowrap;">
                    <form method="POST" action="/sitrass/public/vans/approveVan" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="van_id" value="<?= (int)$van['van_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.35rem 0.9rem; font-size:0.82rem;"><?= t('btn_approve') ?></button>
                    </form>
                    <form method="POST" action="/sitrass/public/vans/removeVan" style="display:inline;" onsubmit="return confirm('Sigurado kang tatanggihan/alisin ang van na ito?');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="van_id" value="<?= (int)$van['van_id'] ?>">
                        <input type="hidden" name="from" value="pending">
                        <button type="submit" class="btn-danger" style="width:auto; padding:0.35rem 0.9rem; font-size:0.82rem; border:none; border-radius:6px; cursor:pointer;"><?= t('btn_reject') ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>