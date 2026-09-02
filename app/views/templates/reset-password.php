<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand"><?= t('reset_title') ?></div>
<div class="brand-sub"><?= t('title_password_reset') ?></div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/auth/updatePassword">
    <?= Csrf::field() ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div class="field">
        <label for="new_password"><?= t('reset_new_password') ?></label>
        <input type="password" id="new_password" name="password" required>
    </div>
    <div class="field">
        <label for="new_password_confirm"><?= t('reset_confirm_password') ?></label>
        <input type="password" id="new_password_confirm" name="password_confirm" required>
    </div>
    <button type="submit" class="btn"><?= t('reset_submit') ?></button>
</form>

<?php require __DIR__ . '/_auth_footer.php'; ?>
