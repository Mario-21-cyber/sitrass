<?php require __DIR__ . '/_admin_header.php'; ?>

<p><a href="/sitrass/public/vans">&larr; Bumalik sa listahan ng Van</a></p>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/vans/uploadImage" enctype="multipart/form-data" style="margin-bottom:2rem;">
    <?= Csrf::field() ?>
    <input type="hidden" name="van_id" value="<?= (int)$van['van_id'] ?>">
    <div class="field">
        <label>Mag-upload ng Larawan (JPEG, PNG, o WebP, max 5MB)</label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
    </div>
    <button type="submit" class="btn" style="width:auto; padding:0.6rem 1.2rem;">I-upload</button>
</form>

<?php if (empty($images)): ?>
    <p>Wala pang larawan ang van na ito.</p>
<?php else: ?>
    <div style="display:flex; flex-wrap:wrap; gap:1rem;">
        <?php foreach ($images as $img): ?>
            <div style="border:1px solid var(--border); border-radius:8px; padding:0.75rem; width:220px;">
                <img src="<?= htmlspecialchars($img['thumbnail_path']) ?>" alt="Larawan ng van <?= htmlspecialchars($van['plate_number']) ?>" style="width:100%; border-radius:4px; display:block; margin-bottom:0.5rem;">

                <?php if ($img['is_primary']): ?>
                    <span class="badge badge-active">Primary</span>
                <?php else: ?>
                    <form method="POST" action="/sitrass/public/vans/setPrimaryImage" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="image_id" value="<?= (int)$img['image_id'] ?>">
                        <input type="hidden" name="van_id" value="<?= (int)$van['van_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.2rem 0.6rem; font-size:0.75rem;">Gawing Primary</button>
                    </form>
                <?php endif; ?>

                <form method="POST" action="/sitrass/public/vans/deleteImage" style="display:inline; margin-left:0.3rem;" onsubmit="return confirm('Sigurado kang burahin ang larawang ito?');">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="image_id" value="<?= (int)$img['image_id'] ?>">
                    <input type="hidden" name="van_id" value="<?= (int)$van['van_id'] ?>">
                    <button type="submit" style="width:auto; padding:0.2rem 0.6rem; font-size:0.75rem; background:var(--danger); color:#fff; border:none; border-radius:4px; cursor:pointer;">Burahin</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>