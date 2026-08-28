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
        <div class="password-field-wrapper">
            <input type="password" id="login_password" name="password" required>
            <button type="button" class="pwd-toggle-btn" onclick="togglePwd('login_password', this)" aria-label="<?= t('btn_show_password') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
        </div>
    </div>
    <button type="submit" class="btn"><?= t('login_submit') ?></button>
</form>

<script>
function togglePwd(inputId, btn) {
    var input = document.getElementById(inputId);
    var showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    btn.innerHTML = showing
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
}
</script>

<a href="/sitrass/public/auth/forgotPassword" class="btn-link"><?= t('login_forgot') ?></a>
<a href="/sitrass/public/auth/register" class="btn-link"><?= t('login_no_account') ?></a>

<?php require __DIR__ . '/_auth_footer.php'; ?>