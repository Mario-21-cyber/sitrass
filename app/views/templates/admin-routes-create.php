<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="margin:0; padding-left: 1.2rem;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/routes/store" style="max-width:500px;">
    <?= Csrf::field() ?>

    <div class="field">
        <label for="r_code"><?= t('label_route_code_example') ?></label>
        <input type="text" id="r_code" name="route_code" value="<?= htmlspecialchars($old['route_code'] ?? '') ?>" required>
    </div>

    <div class="field">
        <label for="r_name"><?= t('label_route_name') ?></label>
        <input type="text" id="r_name" name="route_name" value="<?= htmlspecialchars($old['route_name'] ?? '') ?>" required>
    </div>

    <div class="field">
        <label for="r_origin"><?= t('label_origin') ?></label>
        <select id="r_origin" name="origin_location_id" required>
            <?php foreach ($locations as $loc): ?>
                <option value="<?= (int)$loc['location_id'] ?>"><?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['municipality']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label for="r_dest"><?= t('label_destination') ?></label>
        <select id="r_dest" name="destination_location_id" required>
            <?php foreach ($locations as $loc): ?>
                <option value="<?= (int)$loc['location_id'] ?>"><?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['municipality']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label for="r_distance"><?= t('label_distance_km') ?></label>
        <input type="number" step="0.01" id="r_distance" name="distance_km" value="<?= htmlspecialchars($old['distance_km'] ?? '0') ?>">
    </div>

    <div class="field">
        <label for="r_duration"><?= t('label_est_duration') ?></label>
        <input type="number" id="r_duration" name="estimated_duration_minutes" value="<?= htmlspecialchars($old['estimated_duration_minutes'] ?? '0') ?>">
    </div>

    <div class="field">
        <label for="r_base_fare"><?= t('label_base_fare') ?></label>
        <input type="number" step="0.01" id="r_base_fare" name="base_fare" value="<?= htmlspecialchars($old['base_fare'] ?? '0') ?>">
    </div>

    <div class="field">
        <label for="r_fare_pax"><?= t('label_fare_per_passenger') ?></label>
        <input type="number" step="0.01" id="r_fare_pax" name="fare_per_passenger" value="<?= htmlspecialchars($old['fare_per_passenger'] ?? '0') ?>">
    </div>

    <div class="field">
        <label for="r_road"><?= t('label_road_condition') ?></label>
        <select id="r_road" name="road_condition" required>
            <option value="paved"><?= t('road_paved') ?></option>
            <option value="partially_paved"><?= t('road_partially_paved') ?></option>
            <option value="rough"><?= t('road_rough') ?></option>
        </select>
    </div>

    <button type="submit" class="btn"><?= t('btn_save_route') ?></button>
</form>

<?php require __DIR__ . '/_admin_footer.php'; ?>