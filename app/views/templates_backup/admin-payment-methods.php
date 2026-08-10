<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (!empty($saved)): ?>
    <div class="alert alert-success">Na-save na ang mga pagbabago.</div>
<?php endif; ?>

<p>Ilagay dito ang tamang GCash account name at number - dito babasa ang customer kung saan magpapadala ng bayad.</p>

<?php foreach ($methods as $m): ?>
    <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.5rem; margin-bottom:1.5rem; max-width:500px;">
        <h3 style="margin-top:0;"><?= htmlspecialchars($m['method_name']) ?></h3>

        <form method="POST" action="/sitrass/public/payments/updateMethod">
            <?= Csrf::field() ?>
            <input type="hidden" name="method_id" value="<?= (int)$m['method_id'] ?>">

            <?php if ($m['is_online']): ?>
                <div class="field">
                    <label>Account Name</label>
                    <input type="text" name="account_name" value="<?= htmlspecialchars($m['account_name'] ?? '') ?>">
                </div>
                <div class="field">
                    <label>Account Number</label>
                    <input type="text" name="account_number" value="<?= htmlspecialchars($m['account_number'] ?? '') ?>">
                </div>
            <?php endif; ?>

            <div class="field">
                <label>Instructions</label>
                <input type="text" name="instructions" value="<?= htmlspecialchars($m['instructions'] ?? '') ?>">
            </div>

            <button type="submit" class="btn" style="width:auto; padding:0.5rem 1.2rem;">I-save</button>
        </form>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>