<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (!empty($saved)): ?>
    <div class="alert alert-success">Na-save na ang mga setting.</div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/settings/update">
    <?= Csrf::field() ?>

    <?php foreach ($grouped as $groupName => $settings): ?>
        <h3 style="text-transform:capitalize;"><?= htmlspecialchars($groupName) ?></h3>
        <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem; margin-bottom:1.5rem;">
            <?php foreach ($settings as $s): ?>
                <div class="field">
                    <label><?= htmlspecialchars($s['description'] ?: $s['setting_key']) ?></label>
                    <?php if ($s['data_type'] === 'boolean'): ?>
                        <select name="setting_<?= (int)$s['setting_id'] ?>">
                            <option value="1" <?= $s['setting_value'] == '1' ? 'selected' : '' ?>>Oo</option>
                            <option value="0" <?= $s['setting_value'] == '0' ? 'selected' : '' ?>>Hindi</option>
                        </select>
                    <?php else: ?>
                        <input type="text" name="setting_<?= (int)$s['setting_id'] ?>" value="<?= htmlspecialchars($s['setting_value']) ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn" style="width:auto; padding:0.7rem 2rem;">I-save Lahat ng Setting</button>
</form>

<?php require __DIR__ . '/_admin_footer.php'; ?>