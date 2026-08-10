<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand">Bagong Password</div>
<div class="brand-sub">Password Reset</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/auth/updatePassword">
    <?= Csrf::field() ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div class="field">
        <label for="new_password">Bagong Password</label>
        <input type="password" id="new_password" name="password" required>
    </div>
    <div class="field">
        <label for="new_password_confirm">Kumpirmahin ang Password</label>
        <input type="password" id="new_password_confirm" name="password_confirm" required>
    </div>
    <button type="submit" class="btn">I-update ang Password</button>
</form>

<?php require __DIR__ . '/_auth_footer.php'; ?>