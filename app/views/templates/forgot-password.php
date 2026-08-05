<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand">Nakalimutan ang Password</div>
<div class="brand-sub">Password Recovery</div>

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
        <label>Email</label>
        <input type="email" name="email" required>
    </div>
    <button type="submit" class="btn">Ipadala ang Reset Link</button>
</form>

<a href="/sitrass/public/auth/login" class="btn-link">Bumalik sa Login</a>

<?php require __DIR__ . '/_auth_footer.php'; ?>