<?php require __DIR__ . '/_auth_header.php'; ?>

<div class="brand"><?= t('reg_combined_title') ?></div>

<div class="reg-tabs">
    <button type="button" id="tabCustomerBtn" class="reg-tab-btn <?= ($activeTab ?? 'customer') === 'customer' ? 'active' : '' ?>" onclick="showRegTab('customer')"><?= t('reg_tab_customer') ?></button>
    <button type="button" id="tabDriverBtn" class="reg-tab-btn <?= ($activeTab ?? 'customer') === 'driver' ? 'active' : '' ?>" onclick="showRegTab('driver')"><?= t('reg_tab_driver') ?></button>
</div>

<!-- === CUSTOMER PANEL === -->
<div id="customerPanel" class="reg-form-panel" style="<?= ($activeTab ?? 'customer') === 'driver' ? 'display:none;' : '' ?>">
    <div class="brand-sub"><?= t('register_subtitle') ?></div>

    <?php if (!empty($errors) && ($activeTab ?? 'customer') === 'customer'): ?>
        <div class="alert alert-error">
            <ul style="margin:0; padding-left: 1.2rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error ?? '') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/sitrass/public/auth/store">
        <?= Csrf::field() ?>
        <div class="field">
            <label for="reg_first_name"><?= t('register_first_name') ?></label>
            <input type="text" id="reg_first_name" name="first_name" value="<?= htmlspecialchars(($activeTab ?? 'customer') === 'customer' ? ($old['first_name'] ?? '') : '') ?>" required>
        </div>
        <div class="field">
            <label for="reg_last_name"><?= t('register_last_name') ?></label>
            <input type="text" id="reg_last_name" name="last_name" value="<?= htmlspecialchars(($activeTab ?? 'customer') === 'customer' ? ($old['last_name'] ?? '') : '') ?>" required>
        </div>
        <div class="field">
            <label for="reg_email"><?= t('register_email') ?></label>
            <input type="email" id="reg_email" name="email" value="<?= htmlspecialchars(($activeTab ?? 'customer') === 'customer' ? ($old['email'] ?? '') : '') ?>" required>
        </div>
        <div class="field">
            <label for="reg_phone"><?= t('register_phone') ?></label>
            <input type="text" id="reg_phone" name="phone" value="<?= htmlspecialchars(($activeTab ?? 'customer') === 'customer' ? ($old['phone'] ?? '') : '') ?>" required>
        </div>
        <div class="field">
            <label for="reg_password"><?= t('register_password') ?></label>
            <div class="password-field-wrapper">
                <input type="password" id="reg_password" name="password" required>
                <button type="button" class="pwd-toggle-btn" onclick="togglePwd('reg_password', this)" aria-label="<?= t('btn_show_password') ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
        <div class="field">
            <label for="reg_password_confirm"><?= t('register_confirm_password') ?></label>
            <div class="password-field-wrapper">
                <input type="password" id="reg_password_confirm" name="password_confirm" required>
                <button type="button" class="pwd-toggle-btn" onclick="togglePwd('reg_password_confirm', this)" aria-label="<?= t('btn_show_password') ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
        <button type="submit" class="btn"><?= t('register_submit') ?></button>
    </form>
</div>

<!-- === DRIVER PANEL === -->
<div id="driverPanel" class="reg-form-panel" style="<?= ($activeTab ?? 'customer') === 'driver' ? '' : 'display:none;' ?>">
    <div class="brand-sub"><?= t('dreg_subtitle') ?></div>

    <?php if (!empty($errors) && ($activeTab ?? 'customer') === 'driver'): ?>
        <div class="alert alert-error">
            <ul style="margin:0; padding-left: 1.2rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error ?? '') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/sitrass/public/auth/storeDriver">
        <?= Csrf::field() ?>
        <div class="field">
            <label for="d_first_name"><?= t('register_first_name') ?></label>
            <input type="text" id="d_first_name" name="first_name" value="<?= htmlspecialchars(($activeTab ?? 'customer') === 'driver' ? ($old['first_name'] ?? '') : '') ?>" required>
        </div>
        <div class="field">
            <label for="d_last_name"><?= t('register_last_name') ?></label>
            <input type="text" id="d_last_name" name="last_name" value="<?= htmlspecialchars(($activeTab ?? 'customer') === 'driver' ? ($old['last_name'] ?? '') : '') ?>" required>
        </div>
        <div class="field">
            <label for="d_email"><?= t('register_email') ?></label>
            <input type="email" id="d_email" name="email" value="<?= htmlspecialchars(($activeTab ?? 'customer') === 'driver' ? ($old['email'] ?? '') : '') ?>" required>
        </div>
        <div class="field">
            <label for="d_phone"><?= t('register_phone') ?></label>
            <input type="text" id="d_phone" name="phone" value="<?= htmlspecialchars(($activeTab ?? 'customer') === 'driver' ? ($old['phone'] ?? '') : '') ?>" required>
        </div>
        <div class="field">
            <label for="d_license_number"><?= t('dreg_license_number') ?></label>
            <input type="text" id="d_license_number" name="license_number" value="<?= htmlspecialchars(($activeTab ?? 'customer') === 'driver' ? ($old['license_number'] ?? '') : '') ?>" required>
        </div>
        <div class="field">
            <label for="d_license_expiry"><?= t('dreg_license_expiry') ?></label>
            <input type="date" id="d_license_expiry" name="license_expiry" value="<?= htmlspecialchars(($activeTab ?? 'customer') === 'driver' ? ($old['license_expiry'] ?? '') : '') ?>" required>
        </div>
        <div class="field">
            <label for="d_years_experience"><?= t('dreg_experience') ?></label>
            <input type="number" id="d_years_experience" name="years_experience" value="<?= htmlspecialchars(($activeTab ?? 'customer') === 'driver' ? ($old['years_experience'] ?? '0') : '0') ?>" min="0">
        </div>
        <div class="field">
            <label for="d_password"><?= t('register_password') ?></label>
            <div class="password-field-wrapper">
                <input type="password" id="d_password" name="password" required>
                <button type="button" class="pwd-toggle-btn" onclick="togglePwd('d_password', this)" aria-label="<?= t('btn_show_password') ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
        <div class="field">
            <label for="d_password_confirm"><?= t('register_confirm_password') ?></label>
            <div class="password-field-wrapper">
                <input type="password" id="d_password_confirm" name="password_confirm" required>
                <button type="button" class="pwd-toggle-btn" onclick="togglePwd('d_password_confirm', this)" aria-label="<?= t('btn_show_password') ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
        <button type="submit" class="btn"><?= t('dreg_submit') ?></button>
    </form>
</div>

<a href="/sitrass/public/auth/login" class="btn-link"><?= t('register_has_account') ?></a>

<script>
function showRegTab(tab) {
    var customerPanel = document.getElementById('customerPanel');
    var driverPanel = document.getElementById('driverPanel');
    var customerBtn = document.getElementById('tabCustomerBtn');
    var driverBtn = document.getElementById('tabDriverBtn');

    if (tab === 'customer') {
        customerPanel.style.display = 'block';
        driverPanel.style.display = 'none';
        customerBtn.className = 'reg-tab-btn active';
        driverBtn.className = 'reg-tab-btn';
    } else {
        customerPanel.style.display = 'none';
        driverPanel.style.display = 'block';
        customerBtn.className = 'reg-tab-btn';
        driverBtn.className = 'reg-tab-btn active';
    }
}

function togglePwd(inputId, btn) {
    var input = document.getElementById(inputId);
    var showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    btn.innerHTML = showing
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
}
</script>

<?php require __DIR__ . '/_auth_footer.php'; ?>