<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand">SITRASS</div>
<div class="brand-sub"><?= t('login_title') ?></div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/auth/authenticate">
    <?= Csrf::field() ?>
    <div class="field">
        <label for="login_email"><?= t('login_email') ?></label>
        <input type="email" id="login_email" name="email" required>
    </div>
    <div class="field">
        <label for="login_password"><?= t('login_password') ?></label>
        <input type="password" id="login_password" name="password" required>
    </div>
    <button type="submit" class="btn"><?= t('login_submit') ?></button>
</form>

<a href="/sitrass/public/auth/forgotPassword" class="btn-link"><?= t('login_forgot') ?></a>
<a href="/sitrass/public/auth/register" class="btn-link"><?= t('login_no_account') ?></a>

<?php require __DIR__ . '/_auth_footer.php'; ?>