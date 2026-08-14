<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand"><?= t('forgot_title') ?></div>
<div class="brand-sub"><?= t('forgot_subtitle') ?></div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!empty($resetLink)): ?>
    <div class="alert alert-success">
        <strong>[DEV MODE lang - papalitan ng email sa totoong deployment]</strong><br>
        Reset link: <a href="<?= htmlspecialchars($resetLink) ?>"><?= htmlspecialchars($resetLink) ?></a>
    </div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/auth/sendReset">
    <?= Csrf::field() ?>
    <div class="field">
        <label for="forgot_email"><?= t('login_email') ?></label>
        <input type="email" id="forgot_email" name="email" required>
    </div>
    <button type="submit" class="btn"><?= t('forgot_submit') ?></button>
</form>

<a href="/sitrass/public/auth/login" class="btn-link"><?= t('forgot_back') ?></a>

<?php require __DIR__ . '/_auth_footer.php'; ?>