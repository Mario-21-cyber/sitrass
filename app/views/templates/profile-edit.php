<?php
$headerFile = $_SESSION['role'] === 'admin' ? '_admin_header.php' : ($_SESSION['role'] === 'driver' ? '_driver_header.php' : '_customer_header.php');
$footerFile = $_SESSION['role'] === 'admin' ? '_admin_footer.php' : ($_SESSION['role'] === 'driver' ? '_driver_footer.php' : '_customer_footer.php');
require __DIR__ . '/' . $headerFile;
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:flex; gap:2rem; flex-wrap:wrap; align-items:flex-start;">

    <div style="flex:1; min-width:280px;">
        <div class="form-section">
            <div class="form-section-title">Profile Picture</div>
            <div class="card">
                <?php if ($user['profile_picture']): ?>
                    <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile picture" style="width:100px; height:100px; object-fit:cover; border-radius:100px; margin-bottom:1rem; display:block; border:3px solid var(--slate-bg);">
                <?php else: ?>
                    <div style="width:100px; height:100px; border-radius:100px; background:var(--slate-bg); display:flex; align-items:center; justify-content:center; margin-bottom:1rem; color:var(--slate);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:36px;height:36px;"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>
                    </div>
                <?php endif; ?>
                <form method="POST" action="/sitrass/public/profile/uploadPicture" enctype="multipart/form-data">
                    <?= Csrf::field() ?>
                    <div class="field">
                        <label for="pf_picture">Piliin ang bagong larawan</label>
                        <input type="file" id="pf_picture" name="picture" accept="image/jpeg,image/png" required>
                    </div>
                    <button type="submit" class="btn" style="width:auto; padding:0.5rem 1.2rem;">I-upload</button>
                </form>
            </div>
        </div>

        <?php if ($_SESSION['role'] === 'driver'): ?>
            <div class="form-section">
                <div class="form-section-title">Larawan ng Lisensya</div>
                <div class="card">
                    <?php if ($driver && $driver['license_image']): ?>
                        <img src="<?= htmlspecialchars($driver['license_image']) ?>" alt="Larawan ng lisensya" style="width:100%; max-width:220px; border-radius:8px; margin-bottom:1rem; display:block;">
                    <?php else: ?>
                        <p class="text-sm text-muted">Wala pang na-upload na larawan ng lisensya.</p>
                    <?php endif; ?>
                    <form method="POST" action="/sitrass/public/profile/uploadLicense" enctype="multipart/form-data">
                        <?= Csrf::field() ?>
                        <div class="field">
                            <label for="pf_license">Piliin ang larawan ng lisensya</label>
                            <input type="file" id="pf_license" name="license" accept="image/jpeg,image/png" required>
                        </div>
                        <button type="submit" class="btn" style="width:auto; padding:0.5rem 1.2rem;">I-upload</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div style="flex:1; min-width:280px;">
        <div class="form-section">
            <div class="form-section-title">Personal na Impormasyon</div>
            <form method="POST" action="/sitrass/public/profile/update" class="card">
                <?= Csrf::field() ?>
                <div class="field">
                    <label for="pf_first">First Name<span class="required">*</span></label>
                    <input type="text" id="pf_first" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                </div>
                <div class="field">
                    <label for="pf_last">Last Name<span class="required">*</span></label>
                    <input type="text" id="pf_last" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                </div>
                <div class="field">
                    <label for="pf_email">Email (hindi na puwedeng baguhin)</label>
                    <input type="email" id="pf_email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                </div>
                <div class="field">
                    <label for="pf_phone">Phone<span class="required">*</span></label>
                    <input type="text" id="pf_phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                </div>
                <button type="submit" class="btn">I-save ang Impormasyon</button>
            </form>
        </div>

        <div class="form-section">
            <div class="form-section-title">Palitan ang Password</div>
            <form method="POST" action="/sitrass/public/profile/changePassword" class="card">
                <?= Csrf::field() ?>
                <div class="field">
                    <label for="pf_current_pw">Kasalukuyang Password<span class="required">*</span></label>
                    <input type="password" id="pf_current_pw" name="current_password" required>
                </div>
                <div class="field">
                    <label for="pf_new_pw">Bagong Password<span class="required">*</span></label>
                    <input type="password" id="pf_new_pw" name="new_password" required>
                </div>
                <div class="field">
                    <label for="pf_new_pw_confirm">Kumpirmahin ang Bagong Password<span class="required">*</span></label>
                    <input type="password" id="pf_new_pw_confirm" name="new_password_confirm" required>
                </div>
                <button type="submit" class="btn">Palitan ang Password</button>
            </form>
        </div>
    </div>

</div>

<?php require __DIR__ . '/' . $footerFile; ?>