<?php require __DIR__ . '/_driver_header.php'; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="margin:0; padding-left:1.2rem;">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e ?? '') ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success ?? '') ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem;">
    <div class="form-section-title" style="margin-bottom:0.75rem; border:none; padding:0;"><?= t('myvans_add_title') ?></div>
    <form method="POST" action="/sitrass/public/driver/storeMyVan" style="max-width:520px;">
        <?= Csrf::field() ?>
        <div class="field-row" style="display:flex; gap:0.75rem;">
            <div class="field" style="flex:1;">
                <label><?= t('th_plate_number') ?></label>
                <input type="text" name="plate_number" value="<?= htmlspecialchars($old['plate_number'] ?? '') ?>" required placeholder="ABC-1234">
            </div>
            <div class="field" style="flex:1;">
                <label><?= t('th_type') ?></label>
                <select name="van_type" required>
                    <option value="standard" <?= ($old['van_type'] ?? '') === 'standard' ? 'selected' : '' ?>><?= t('vantype_standard') ?></option>
                    <option value="premium" <?= ($old['van_type'] ?? '') === 'premium' ? 'selected' : '' ?>><?= t('vantype_premium') ?></option>
                    <option value="tourist" <?= ($old['van_type'] ?? '') === 'tourist' ? 'selected' : '' ?>><?= t('vantype_tourist') ?></option>
                </select>
            </div>
        </div>
        <div class="field-row" style="display:flex; gap:0.75rem;">
            <div class="field" style="flex:1;">
                <label><?= t('th_make') ?></label>
                <input type="text" name="make" value="<?= htmlspecialchars($old['make'] ?? '') ?>" required placeholder="Toyota">
            </div>
            <div class="field" style="flex:1;">
                <label><?= t('th_model') ?></label>
                <input type="text" name="model" value="<?= htmlspecialchars($old['model'] ?? '') ?>" required placeholder="HiAce Commuter">
            </div>
        </div>
        <div class="field-row" style="display:flex; gap:0.75rem;">
            <div class="field" style="flex:1;">
                <label><?= t('label_year') ?></label>
                <input type="number" name="year_model" value="<?= htmlspecialchars($old['year_model'] ?? '') ?>" min="1990" max="2030">
            </div>
            <div class="field" style="flex:1;">
                <label><?= t('label_color') ?></label>
                <input type="text" name="color" value="<?= htmlspecialchars($old['color'] ?? '') ?>">
            </div>
        </div>
        <div class="field-row" style="display:flex; gap:0.75rem;">
            <div class="field" style="flex:1;">
                <label><?= t('th_seats') ?></label>
                <input type="number" name="seating_capacity" value="<?= htmlspecialchars($old['seating_capacity'] ?? '14') ?>" required min="1" max="30">
            </div>
            <div class="field" style="flex:1;">
                <label><?= t('label_luggage') ?></label>
                <input type="number" name="luggage_capacity" value="<?= htmlspecialchars($old['luggage_capacity'] ?? '0') ?>" min="0">
            </div>
        </div>
        <div class="field" style="display:flex; gap:1.5rem;">
            <label style="display:flex; align-items:center; gap:0.4rem;">
                <input type="checkbox" name="has_aircon" <?= isset($old['has_aircon']) ? 'checked' : '' ?>> <?= t('label_has_aircon') ?>
            </label>
            <label style="display:flex; align-items:center; gap:0.4rem;">
                <input type="checkbox" name="has_wifi" <?= isset($old['has_wifi']) ? 'checked' : '' ?>> <?= t('label_has_wifi') ?>
            </label>
        </div>
        <div class="field">
            <label><?= t('label_description') ?></label>
            <textarea name="description" rows="2"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
        </div>
        <div class="text-sm text-muted" style="margin-bottom:0.5rem;"><?= t('myvans_prices_note') ?></div>
        <div class="field-row" style="display:flex; gap:0.75rem;">
            <div class="field" style="flex:1;">
                <label><?= t('label_base_fare') ?></label>
                <input type="number" step="0.01" name="base_fare" value="<?= htmlspecialchars($old['base_fare'] ?? '0') ?>">
            </div>
            <div class="field" style="flex:1;">
                <label><?= t('label_fare_per_km') ?></label>
                <input type="number" step="0.01" name="fare_per_km" value="<?= htmlspecialchars($old['fare_per_km'] ?? '0') ?>">
            </div>
            <div class="field" style="flex:1;">
                <label><?= t('rent_price_label') ?></label>
                <input type="number" step="0.01" name="whole_van_day_rate" value="<?= htmlspecialchars($old['whole_van_day_rate'] ?? '0') ?>">
            </div>
        </div>
        <button type="submit" class="btn"><?= t('myvans_add_title') ?></button>
    </form>
</div>

<div class="section-heading">
    <h3 style="margin:0;"><?= t('nav_my_vans') ?></h3>
</div>

<?php if (empty($vans)): ?>
    <div class="empty-state"><?= t('myvans_none') ?></div>
<?php else: ?>
    <?php foreach ($vans as $van): ?>
        <?php $vid = (int)$van['van_id']; ?>
        <div class="card" style="display:flex; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
            <?php if (!empty($van['primary_image'])): ?>
                <img src="<?= htmlspecialchars($van['primary_image']) ?>" alt="" style="width:120px; height:90px; object-fit:cover; border-radius:8px;">
            <?php else: ?>
                <div style="width:120px; height:90px; background:var(--slate-bg); border-radius:8px; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--slate)" stroke-width="1.5" style="width:36px;height:36px;"><path d="M1 8h13v9H1z"/><path d="M14 11h4l3 3v3h-7z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="17.5" cy="18.5" r="2"/></svg>
                </div>
            <?php endif; ?>

            <div style="flex:1; min-width:220px;">
                <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                    <strong><?= htmlspecialchars($van['make'] . ' ' . $van['model']) ?></strong>
                    <span class="badge <?= $van['status'] === 'active' ? 'badge-active' : 'badge-pending' ?>"><?= t('status_' . $van['status']) ?></span>
                </div>
                <div class="text-sm text-muted" style="font-family:'SF Mono', monospace; margin:0.25rem 0;"><?= htmlspecialchars($van['plate_number']) ?><?= $van['color'] ? ' · ' . htmlspecialchars($van['color']) : '' ?><?= $van['year_model'] ? ' · ' . (int)$van['year_model'] : '' ?></div>
                <div class="text-sm text-muted"><?= (int)$van['seating_capacity'] ?> <?= t('unit_passengers') ?> · <?= t('label_has_aircon') ?>: <?= $van['has_aircon'] ? '✓' : '—' ?> · <?= t('label_has_wifi') ?>: <?= $van['has_wifi'] ? '✓' : '—' ?></div>
                <div class="text-sm" style="margin-top:0.35rem;">
                    <?= t('label_base_fare') ?>: ₱<?= number_format((float)$van['base_fare'], 2) ?> ·
                    <?= t('label_fare_per_km') ?>: ₱<?= number_format((float)$van['fare_per_km'], 2) ?> ·
                    <?= t('rent_price_label') ?>: ₱<?= number_format((float)$van['whole_van_day_rate'], 2) ?>
                </div>
            </div>

            <div style="flex:1; min-width:260px;">
                <div class="action-row" style="border:none; padding:0; margin-bottom:0.75rem;">
                    <button type="button" class="btn-sm btn-outline" onclick="document.getElementById('edit-van-<?= $vid ?>').style.display = (document.getElementById('edit-van-<?= $vid ?>').style.display === 'none' ? 'block' : 'none'); document.getElementById('upload-van-<?= $vid ?>').style.display = 'none';">✎ <?= t('myvans_edit_title') ?></button>
                    <button type="button" class="btn-sm btn-outline" onclick="document.getElementById('upload-van-<?= $vid ?>').style.display = (document.getElementById('upload-van-<?= $vid ?>').style.display === 'none' ? 'block' : 'none'); document.getElementById('edit-van-<?= $vid ?>').style.display = 'none';">📷 <?= t('myvans_upload_photo') ?></button>
                </div>

                <div id="edit-van-<?= $vid ?>" style="display:none; margin-top:0.6rem; padding-top:0.6rem; border-top:1px solid var(--border);">
                    <form method="POST" action="/sitrass/public/driver/updateMyVan">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="van_id" value="<?= $vid ?>">
                        <div class="field"><label><?= t('th_plate_number') ?></label><input type="text" name="plate_number" value="<?= htmlspecialchars($van['plate_number']) ?>" required></div>
                        <div class="field-row" style="display:flex; gap:0.5rem;">
                            <div class="field" style="flex:1;"><label><?= t('th_make') ?></label><input type="text" name="make" value="<?= htmlspecialchars($van['make']) ?>" required></div>
                            <div class="field" style="flex:1;"><label><?= t('th_model') ?></label><input type="text" name="model" value="<?= htmlspecialchars($van['model']) ?>" required></div>
                        </div>
                        <div class="field-row" style="display:flex; gap:0.5rem;">
                            <div class="field" style="flex:1;">
                                <label><?= t('th_type') ?></label>
                                <select name="van_type">
                                    <option value="standard" <?= $van['van_type'] === 'standard' ? 'selected' : '' ?>><?= t('vantype_standard') ?></option>
                                    <option value="premium" <?= $van['van_type'] === 'premium' ? 'selected' : '' ?>><?= t('vantype_premium') ?></option>
                                    <option value="tourist" <?= $van['van_type'] === 'tourist' ? 'selected' : '' ?>><?= t('vantype_tourist') ?></option>
                                </select>
                            </div>
                            <div class="field" style="flex:1;"><label><?= t('th_seats') ?></label><input type="number" name="seating_capacity" value="<?= (int)$van['seating_capacity'] ?>" min="1" max="30" required></div>
                        </div>
                        <div class="field-row" style="display:flex; gap:0.5rem;">
                            <div class="field" style="flex:1;"><label><?= t('label_base_fare') ?></label><input type="number" step="0.01" name="base_fare" value="<?= htmlspecialchars($van['base_fare']) ?>"></div>
                            <div class="field" style="flex:1;"><label><?= t('label_fare_per_km') ?></label><input type="number" step="0.01" name="fare_per_km" value="<?= htmlspecialchars($van['fare_per_km']) ?>"></div>
                            <div class="field" style="flex:1;"><label><?= t('rent_price_label') ?></label><input type="number" step="0.01" name="whole_van_day_rate" value="<?= htmlspecialchars($van['whole_van_day_rate']) ?>"></div>
                        </div>
                        <button type="submit" class="btn-sm btn-primary"><?= t('btn_save') ?></button>
                    </form>
                </div>

                <div id="upload-van-<?= $vid ?>" style="display:none; margin-top:0.6rem; padding-top:0.6rem; border-top:1px solid var(--border);">
                    <form method="POST" action="/sitrass/public/driver/uploadMyVanImage" enctype="multipart/form-data">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="van_id" value="<?= $vid ?>">
                        <div class="field"><label><?= t('myvans_upload_photo') ?></label><input type="file" name="image" accept="image/*" required></div>
                        <button type="submit" class="btn-sm btn-primary"><?= t('myvans_upload_photo') ?></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_driver_footer.php'; ?>