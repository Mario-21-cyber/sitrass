<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand">Mag-register bilang Driver</div>
<div class="brand-sub">Driver Application</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="margin:0; padding-left: 1.2rem;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/auth/storeDriver">
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
        <label>License Number</label>
        <input type="text" name="license_number" value="<?= htmlspecialchars($old['license_number'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label>License Expiry</label>
        <input type="date" name="license_expiry" value="<?= htmlspecialchars($old['license_expiry'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label>Years of Experience</label>
        <input type="number" name="years_experience" value="<?= htmlspecialchars($old['years_experience'] ?? '0') ?>" min="0">
    </div>
    <div class="field">
        <label>Password</label>
        <input type="password" name="password" required>
    </div>
    <div class="field">
        <label>Kumpirmahin ang Password</label>
        <input type="password" name="password_confirm" required>
    </div>
    <button type="submit" class="btn">Mag-apply bilang Driver</button>
</form>

<a href="/sitrass/public/auth/login" class="btn-link">May account na? Login dito</a>

<?php require __DIR__ . '/_auth_footer.php'; ?>