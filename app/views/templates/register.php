<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand"><?= t('register_title') ?></div>
<div class="brand-sub"><?= t('register_subtitle') ?></div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="margin:0; padding-left: 1.2rem;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/auth/store">
    <?= Csrf::field() ?>
    <div class="field">
        <label for="reg_first_name"><?= t('register_first_name') ?></label>
        <input type="text" id="reg_first_name" name="first_name" value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="reg_last_name"><?= t('register_last_name') ?></label>
        <input type="text" id="reg_last_name" name="last_name" value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="reg_email"><?= t('register_email') ?></label>
        <input type="email" id="reg_email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="reg_phone"><?= t('register_phone') ?></label>
        <input type="text" id="reg_phone" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="reg_password"><?= t('register_password') ?></label>
        <input type="password" id="reg_password" name="password" required>
    </div>
    <div class="field">
        <label for="reg_password_confirm"><?= t('register_confirm_password') ?></label>
        <input type="password" id="reg_password_confirm" name="password_confirm" required>
    </div>
    <button type="submit" class="btn"><?= t('register_submit') ?></button>
</form>

<a href="/sitrass/public/auth/login" class="btn-link"><?= t('register_has_account') ?></a>
<a href="/sitrass/public/auth/registerDriver" class="btn-link"><?= t('register_driver_link') ?></a>

<?php require __DIR__ . '/_auth_footer.php'; ?>