<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand">Gumawa ng Account</div>
<div class="brand-sub">Customer Registration</div>

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
        <label>First Name</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label>Last Name</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label>Phone (+639XXXXXXXXX)</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label>Password</label>
        <input type="password" name="password" required>
    </div>
    <div class="field">
        <label>Kumpirmahin ang Password</label>
        <input type="password" name="password_confirm" required>
    </div>
    <button type="submit" class="btn">Register</button>
</form>

<a href="/sitrass/public/auth/login" class="btn-link">May account na? Login dito</a>

<?php require __DIR__ . '/_auth_footer.php'; ?>