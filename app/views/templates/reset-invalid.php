<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand"><?= t('title_invalid_expired_link') ?></div>
<div class="brand-sub"><?= t('title_password_reset') ?></div>

<p><?= t('reset_request_new_link') ?></p>

<a href="/sitrass/public/auth/forgotPassword" class="btn-link"><?= t('btn_back') ?></a>

<?php require __DIR__ . '/_auth_footer.php'; ?>
