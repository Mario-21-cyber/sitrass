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
        <label for="d_first_name">First Name</label>
        <input type="text" id="d_first_name" name="first_name" value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="d_last_name">Last Name</label>
        <input type="text" id="d_last_name" name="last_name" value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="d_email">Email</label>
        <input type="email" id="d_email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="d_phone">Phone (+639XXXXXXXXX)</label>
        <input type="text" id="d_phone" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="d_license_number">License Number</label>
        <input type="text" id="d_license_number" name="license_number" value="<?= htmlspecialchars($old['license_number'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="d_license_expiry">License Expiry</label>
        <input type="date" id="d_license_expiry" name="license_expiry" value="<?= htmlspecialchars($old['license_expiry'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="d_years_experience">Years of Experience</label>
        <input type="number" id="d_years_experience" name="years_experience" value="<?= htmlspecialchars($old['years_experience'] ?? '0') ?>" min="0">
    </div>
    <div class="field">
        <label for="d_password">Password</label>
        <input type="password" id="d_password" name="password" required>
    </div>
    <div class="field">
        <label for="d_password_confirm">Kumpirmahin ang Password</label>
        <input type="password" id="d_password_confirm" name="password_confirm" required>
    </div>
    <button type="submit" class="btn">Mag-apply bilang Driver</button>
</form>

<a href="/sitrass/public/auth/login" class="btn-link">May account na? Login dito</a>

<?php require __DIR__ . '/_auth_footer.php'; ?>