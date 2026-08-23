<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (!empty($saved)): ?>
    <div class="alert alert-success">Na-save na ang mga pagbabago.</div>
<?php endif; ?>

<p class="text-muted" style="margin-bottom:var(--space-5);"><?= t('pm_intro') ?></p>

<?php foreach ($methods as $m): ?>
    <div class="form-section">
        <div class="form-section-title"><?= htmlspecialchars($m['method_name']) ?></div>
        <div class="card" style="max-width:500px;">
            <form method="POST" action="/sitrass/public/payments/updateMethod">
                <?= Csrf::field() ?>
                <input type="hidden" name="method_id" value="<?= (int)$m['method_id'] ?>">

                <?php if ($m['is_online']): ?>
                    <div class="field">
                        <label for="pm_name_<?= (int)$m['method_id'] ?>"><?= t('pm_account_name') ?></label>
                        <input type="text" id="pm_name_<?= (int)$m['method_id'] ?>" name="account_name" value="<?= htmlspecialchars($m['account_name'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="pm_number_<?= (int)$m['method_id'] ?>"><?= t('pm_account_number') ?></label>
                        <input type="text" id="pm_number_<?= (int)$m['method_id'] ?>" name="account_number" value="<?= htmlspecialchars($m['account_number'] ?? '') ?>">
                    </div>
                <?php endif; ?>

                <div class="field">
                    <label for="pm_instructions_<?= (int)$m['method_id'] ?>"><?= t('pm_instructions') ?></label>
                    <input type="text" id="pm_instructions_<?= (int)$m['method_id'] ?>" name="instructions" value="<?= htmlspecialchars($m['instructions'] ?? '') ?>">
                </div>

                <button type="submit" class="btn" style="width:auto; padding:0.5rem 1.2rem;"><?= t('btn_save') ?></button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>