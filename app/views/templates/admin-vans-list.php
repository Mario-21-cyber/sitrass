<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (!empty($_SESSION['van_action_msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['van_action_msg']) ?></div>
    <?php unset($_SESSION['van_action_msg']); ?>
<?php endif; ?>

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
            <?php $vid = (int)$van['van_id']; ?>
            <tr>
                <td style="font-family:'SF Mono', monospace;">
                    <input form="vanf_<?= $vid ?>" type="text" name="plate_number" value="<?= htmlspecialchars($van['plate_number']) ?>" disabled class="van-cell" style="max-width:120px;">
                </td>
                <td>
                    <input form="vanf_<?= $vid ?>" type="text" name="make" value="<?= htmlspecialchars($van['make']) ?>" disabled class="van-cell" style="max-width:88px;">
                    <input form="vanf_<?= $vid ?>" type="text" name="model" value="<?= htmlspecialchars($van['model']) ?>" disabled class="van-cell" style="max-width:88px;">
                </td>
                <td>
                    <select form="vanf_<?= $vid ?>" name="van_type" disabled class="van-cell">
                        <option value="standard" <?= $van['van_type'] === 'standard' ? 'selected' : '' ?>><?= t('vantype_standard') ?></option>
                        <option value="premium" <?= $van['van_type'] === 'premium' ? 'selected' : '' ?>><?= t('vantype_premium') ?></option>
                        <option value="tourist" <?= $van['van_type'] === 'tourist' ? 'selected' : '' ?>><?= t('vantype_tourist') ?></option>
                    </select>
                </td>
                <td>
                    <input form="vanf_<?= $vid ?>" type="number" name="seating_capacity" value="<?= (int)$van['seating_capacity'] ?>" min="1" max="30" disabled class="van-cell" style="max-width:70px;">
                </td>
                <td>
                    <form method="POST" action="/sitrass/public/vans/toggleStatus" style="margin:0;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="van_id" value="<?= $vid ?>">
                        <select name="status" onchange="this.form.submit()" class="van-cell">
                            <option value="active" <?= $van['status'] === 'active' ? 'selected' : '' ?>><?= t('status_active') ?></option>
                            <option value="maintenance" <?= $van['status'] === 'maintenance' ? 'selected' : '' ?>><?= t('status_maintenance') ?></option>
                            <?php if ($van['status'] === 'inactive'): ?>
                                <option value="inactive" selected><?= t('status_inactive') ?></option>
                            <?php endif; ?>
                        </select>
                    </form>
                </td>
                <td style="white-space:nowrap;">
                    <form id="vanf_<?= $vid ?>" method="POST" action="/sitrass/public/vans/updateVan" style="display:none;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="van_id" value="<?= $vid ?>">
                    </form>
                    <form id="vanrm_<?= $vid ?>" method="POST" action="/sitrass/public/vans/removeVan" style="display:none;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="van_id" value="<?= $vid ?>">
                    </form>
                    <button type="submit" form="vanf_<?= $vid ?>" id="vansave_<?= $vid ?>" class="btn" style="display:none; width:auto; padding:0.35rem 0.9rem; font-size:0.82rem;"><?= t('btn_save') ?></button>
                    <button type="button" id="vanedit_<?= $vid ?>" class="btn-ghost" onclick="vanEditOn(<?= $vid ?>)" style="white-space:nowrap;">✎ <?= t('btn_edit') ?></button>
                    <button type="button" class="btn-ghost" style="color:var(--danger);" onclick="vanRemove(<?= $vid ?>)" title="<?= t('btn_remove') ?>">🗑</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<script>
function vanEditOn(id) {
    document.querySelectorAll('input[form="vanf_' + id + '"], select[form="vanf_' + id + '"]').forEach(function(el) { el.disabled = false; });
    document.getElementById('vanedit_' + id).style.display = 'none';
    document.getElementById('vansave_' + id).style.display = 'inline-block';
}
function vanRemove(id) {
    if (!confirm('Sigurado kang alisin ang van na ito?')) return;
    document.getElementById('vanrm_' + id).submit();
}
</script>

<style>
.van-cell { padding: 0.3rem; border: 1px solid var(--border); border-radius: 4px; background: var(--white); font-size: 0.85rem; }
.van-cell:disabled { border-color: transparent; background: transparent; padding-left: 0; padding-right: 0; }
</style>

<?php require __DIR__ . '/_admin_footer.php'; ?>
