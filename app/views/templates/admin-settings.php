<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (!empty($saved)): ?>
    <div class="alert alert-success">Na-save na ang mga setting.</div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/settings/update">
    <?= Csrf::field() ?>

    <?php foreach ($grouped as $groupName => $settings): ?>
        <div class="form-section">
            <div class="form-section-title" style="text-transform:capitalize;"><?= htmlspecialchars($groupName) ?></div>
            <div class="card">
                <?php foreach ($settings as $s): ?>
                    <div class="field">
                        <label for="setting_<?= (int)$s['setting_id'] ?>"><?= htmlspecialchars($s['description'] ?: $s['setting_key']) ?></label>
                        <?php if ($s['data_type'] === 'boolean'): ?>
                            <select id="setting_<?= (int)$s['setting_id'] ?>" name="setting_<?= (int)$s['setting_id'] ?>">
                                <option value="1" <?= $s['setting_value'] == '1' ? 'selected' : '' ?>>Oo</option>
                                <option value="0" <?= $s['setting_value'] == '0' ? 'selected' : '' ?>>Hindi</option>
                            </select>
                        <?php else: ?>
                            <input type="text" id="setting_<?= (int)$s['setting_id'] ?>" name="setting_<?= (int)$s['setting_id'] ?>" value="<?= htmlspecialchars($s['setting_value']) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn" style="width:auto; padding:0.75rem 2rem;">I-save Lahat ng Setting</button>
</form>

<?php require __DIR__ . '/_admin_footer.php'; ?>