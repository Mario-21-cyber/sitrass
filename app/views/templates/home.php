<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand">SITRASS</div>
<div class="brand-sub">Sibuyan Island Transportation</div>

<p style="text-align:center; margin: 1.5rem 0;">
    <?= t('home_tagline') ?>
</p>

<a href="/sitrass/public/auth/login" class="btn" style="display:block; text-align:center; text-decoration:none; margin-bottom:0.75rem;"><?= t('home_login') ?></a>
<a href="/sitrass/public/auth/register" class="btn-link"><?= t('home_register') ?></a>

<?php require __DIR__ . '/_auth_footer.php'; ?>