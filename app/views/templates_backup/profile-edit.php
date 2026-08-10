<?php
$headerFile = $_SESSION['role'] === 'admin' ? '_admin_header.php' : ($_SESSION['role'] === 'driver' ? '_driver_header.php' : '_customer_header.php');
$footerFile = $_SESSION['role'] === 'admin' ? '_admin_footer.php' : ($_SESSION['role'] === 'driver' ? '_driver_footer.php' : '_customer_footer.php');
require __DIR__ . '/' . $headerFile;
?>

<h2>Aking Profile</h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:flex; gap:2rem; flex-wrap:wrap;">

    <div style="flex:1; min-width:280px;">
        <h3>Profile Picture</h3>
        <div class="card">
            <?php if ($user['profile_picture']): ?>
                <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile picture" style="width:120px; height:120px; object-fit:cover; border-radius:100px; margin-bottom:1rem; display:block;">
            <?php endif; ?>
            <form method="POST" action="/sitrass/public/profile/uploadPicture" enctype="multipart/form-data">
                <?= Csrf::field() ?>
                <div class="field">
                    <input type="file" name="picture" accept="image/jpeg,image/png" required>
                </div>
                <button type="submit" class="btn" style="width:auto; padding:0.5rem 1.2rem;">I-upload</button>
            </form>
        </div>

        <?php if ($_SESSION['role'] === 'driver'): ?>
            <h3>Larawan ng Lisensya</h3>
            <div class="card">
                <?php if ($driver && $driver['license_image']): ?>
                    <img src="<?= htmlspecialchars($driver['license_image']) ?>" alt="Larawan ng lisensya" style="width:200px; border-radius:8px; margin-bottom:1rem; display:block;">
                <?php else: ?>
                    <p style="font-size:0.85rem;">Wala pang na-upload na larawan ng lisensya.</p>
                <?php endif; ?>
                <form method="POST" action="/sitrass/public/profile/uploadLicense" enctype="multipart/form-data">
                    <?= Csrf::field() ?>
                    <div class="field">
                        <input type="file" name="license" accept="image/jpeg,image/png" required>
                    </div>
                    <button type="submit" class="btn" style="width:auto; padding:0.5rem 1.2rem;">I-upload</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div style="flex:1; min-width:280px;">
        <h3>Personal na Impormasyon</h3>
        <form method="POST" action="/sitrass/public/profile/update" class="card">
            <?= Csrf::field() ?>
            <div class="field">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
            </div>
            <div class="field">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
            </div>
            <div class="field">
                <label>Email (hindi na puwedeng baguhin)</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
            </div>
            <div class="field">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
            </div>
            <button type="submit" class="btn">I-save ang Impormasyon</button>
        </form>

        <h3>Palitan ang Password</h3>
        <form method="POST" action="/sitrass/public/profile/changePassword" class="card">
            <?= Csrf::field() ?>
            <div class="field">
                <label>Kasalukuyang Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="field">
                <label>Bagong Password</label>
                <input type="password" name="new_password" required>
            </div>
            <div class="field">
                <label>Kumpirmahin ang Bagong Password</label>
                <input type="password" name="new_password_confirm" required>
            </div>
            <button type="submit" class="btn">Palitan ang Password</button>
        </form>
    </div>

</div>

<?php require __DIR__ . '/' . $footerFile; ?>