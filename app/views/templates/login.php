<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand">SITRASS</div>
<div class="brand-sub">Sibuyan Island Transportation</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/auth/authenticate">
    <?= Csrf::field() ?>
    <div class="field">
        <label for="login_email">Email</label>
        <input type="email" id="login_email" name="email" required>
    </div>
    <div class="field">
        <label for="login_password">Password</label>
        <input type="password" id="login_password" name="password" required>
    </div>
    <button type="submit" class="btn">Login</button>
</form>

<a href="/sitrass/public/auth/forgotPassword" class="btn-link">Nakalimutan ang password?</a>
<a href="/sitrass/public/auth/register" class="btn-link">Wala pang account? Mag-register</a>

<?php require __DIR__ . '/_auth_footer.php'; ?>