<?php require __DIR__ . '/_driver_header.php'; ?>

<h2><?= t('nav_scan_qr') ?></h2>

<?php if (!empty($result)): ?>
    <div class="alert <?= $result['success'] ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars($result['message']) ?>
    </div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/driver/verifyQr" style="max-width:400px;">
    <?= Csrf::field() ?>
    <div class="field">
        <label><?= t('qr_input_label') ?></label>
        <input type="text" name="token" required autofocus>
    </div>
    <button type="submit" class="btn"><?= t('btn_verify') ?></button>
</form>

<p style="font-size:0.85rem; color:var(--ocean); margin-top:1rem;">
    <?= t('qr_helper_text') ?>
</p>

<a href="/sitrass/public/driver/dashboard" class="btn-link"><?= t('link_back_dashboard') ?></a>

<?php require __DIR__ . '/_driver_footer.php'; ?>