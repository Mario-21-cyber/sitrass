<?php
$headerFile = $_SESSION['role'] === 'admin' ? '_admin_header.php' : ($_SESSION['role'] === 'driver' ? '_driver_header.php' : '_customer_header.php');
$footerFile = $_SESSION['role'] === 'admin' ? '_admin_footer.php' : ($_SESSION['role'] === 'driver' ? '_driver_footer.php' : '_customer_footer.php');
require __DIR__ . '/' . $headerFile;
?>

<div style="max-width:560px; margin:0 auto;">

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

        <div class="form-section">
        <div class="form-section-title"><?= t('profile_picture_title') ?></div>
        <div class="card" style="text-align:center;">
            <?php if ($user['profile_picture']): ?>
                <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="<?= t('profile_picture_title') ?>" style="width:100px; height:100px; object-fit:cover; border-radius:100px; margin:0 auto 1rem; display:block; border:3px solid var(--slate-bg);">
            <?php else: ?>
                <div style="width:100px; height:100px; border-radius:100px; background:var(--slate-bg); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; color:var(--slate);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:36px;height:36px;"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                </div>
            <?php endif; ?>
            <form method="POST" action="/sitrass/public/profile/uploadPicture" enctype="multipart/form-data">
                <?= Csrf::field() ?>
                <div class="field" style="text-align:left;">
                    <label for="pf_picture"><?= t('profile_pick_new_photo') ?></label>
                    <input type="file" id="pf_picture" name="picture" accept="image/jpeg,image/png" required>
                </div>
                <button type="submit" class="btn" style="width:auto; padding:0.5rem 1.2rem;"><?= t('btn_upload') ?></button>
            </form>
        </div>
    </div>

    <?php if ($_SESSION['role'] === 'driver'): ?>
                <div class="form-section">
            <div class="form-section-title"><?= t('license_section_title') ?></div>
            <div class="card" style="text-align:center;">
                <?php if ($driver && $driver['license_image']): ?>
                    <img src="<?= htmlspecialchars($driver['license_image']) ?>" alt="<?= t('license_section_title') ?>" style="width:100%; max-width:220px; border-radius:8px; margin-bottom:1rem; display:block; margin-left:auto; margin-right:auto;">
                <?php else: ?>
                    <p class="text-sm text-muted"><?= t('license_no_upload') ?></p>
                <?php endif; ?>
                <form method="POST" action="/sitrass/public/profile/uploadLicense" enctype="multipart/form-data">
                    <?= Csrf::field() ?>
                    <div class="field" style="text-align:left;">
                        <label for="pf_license"><?= t('license_pick_photo') ?></label>
                        <input type="file" id="pf_license" name="license" accept="image/jpeg,image/png" required>
                    </div>
                    <button type="submit" class="btn" style="width:auto; padding:0.5rem 1.2rem;"><?= t('btn_upload') ?></button>
                </form>
            </div>
        </div>
    <?php endif; ?>

        <div class="form-section">
        <div class="section-heading" style="margin-bottom:var(--space-3);">
            <div class="form-section-title" style="margin-bottom:0; border-bottom:none; padding-bottom:0;"><?= t('profile_personal_info') ?></div>
            <button type="button" id="editToggleBtn" class="btn-ghost" onclick="enableEdit()">
                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:2px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
                <?= t('btn_edit') ?>
            </button>
        </div>
        <form method="POST" action="/sitrass/public/profile/update" class="card" id="profileForm">
            <?= Csrf::field() ?>
            <div class="field">
                <label for="pf_first"><?= t('label_first_name') ?><span class="required">*</span></label>
                <input type="text" id="pf_first" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required readonly>
            </div>
            <div class="field">
                <label for="pf_last"><?= t('label_last_name') ?><span class="required">*</span></label>
                <input type="text" id="pf_last" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required readonly>
            </div>
            <div class="field">
                <label for="pf_email"><?= t('label_email_readonly') ?></label>
                <input type="email" id="pf_email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            </div>
            <div class="field">
                <label for="pf_phone"><?= t('label_phone') ?><span class="required">*</span></label>
                <input type="text" id="pf_phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required readonly>
            </div>
            <div id="profileFormActions" style="display:none; gap:0.5rem;">
                <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.5rem;"><?= t('btn_save_information') ?></button>
                <button type="button" class="btn-ghost" onclick="cancelEdit()"><?= t('btn_cancel') ?></button>
            </div>
        </form>
    </div>

    <div class="form-section">
        <div class="form-section-title"><?= t('profile_language') ?></div>
        <form method="POST" action="/sitrass/public/profile/setLanguage" class="card">
            <?= Csrf::field() ?>
            <div class="field">
                <label for="pf_lang"><?= t('profile_language') ?></label>
                <select id="pf_lang" name="lang">
                    <option value="tl" <?= Lang::current() === 'tl' ? 'selected' : '' ?>><?= t('profile_language_tl') ?></option>
                    <option value="en" <?= Lang::current() === 'en' ? 'selected' : '' ?>><?= t('profile_language_en') ?></option>
                </select>
            </div>
            <button type="submit" class="btn"><?= t('profile_language_save') ?></button>
        </form>
    </div>

                    <div class="form-section">
            <div class="form-section-title"><?= t('profile_password_title') ?></div>
            <form method="POST" action="/sitrass/public/profile/changePassword" class="card">
                <?= Csrf::field() ?>
                <div class="field">
                    <label for="pf_current_pw"><?= t('label_current_password') ?><span class="required">*</span></label>
                    <div class="password-field-wrapper">
                        <input type="password" id="pf_current_pw" name="current_password" required>
                        <button type="button" class="pwd-toggle-btn" onclick="togglePwd('pf_current_pw', this)" aria-label="<?= t('btn_show_password') ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="field">
                    <label for="pf_new_pw"><?= t('label_new_password') ?><span class="required">*</span></label>
                    <div class="password-field-wrapper">
                        <input type="password" id="pf_new_pw" name="new_password" required>
                        <button type="button" class="pwd-toggle-btn" onclick="togglePwd('pf_new_pw', this)" aria-label="<?= t('btn_show_password') ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="field">
                    <label for="pf_new_pw_confirm"><?= t('label_confirm_new_password') ?><span class="required">*</span></label>
                    <div class="password-field-wrapper">
                        <input type="password" id="pf_new_pw_confirm" name="new_password_confirm" required>
                        <button type="button" class="pwd-toggle-btn" onclick="togglePwd('pf_new_pw_confirm', this)" aria-label="<?= t('btn_show_password') ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn"><?= t('btn_change_password') ?></button>
            </form>
        </div>

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

</div>

<script>
function enableEdit() {
    document.getElementById('pf_first').removeAttribute('readonly');
    document.getElementById('pf_last').removeAttribute('readonly');
    document.getElementById('pf_phone').removeAttribute('readonly');
    document.getElementById('profileFormActions').style.display = 'flex';
    document.getElementById('editToggleBtn').style.display = 'none';
    document.getElementById('pf_first').focus();
}

function cancelEdit() {
    // I-reload ang page para ibalik ang orihinal na values, tapusin ang edit mode
    window.location.reload();
}
</script>

<?php require __DIR__ . '/' . $footerFile; ?>
