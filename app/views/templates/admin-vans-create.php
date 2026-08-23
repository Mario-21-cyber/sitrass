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

<form method="POST" action="/sitrass/public/vans/store" style="max-width:500px;">
    <?= Csrf::field() ?>

    <div class="field">
        <label for="v_plate"><?= t('label_plate_number') ?></label>
        <input type="text" id="v_plate" name="plate_number" value="<?= htmlspecialchars($old['plate_number'] ?? '') ?>" required>
    </div>

    <div class="field">
        <label for="v_make"><?= t('label_make_example') ?></label>
        <input type="text" id="v_make" name="make" value="<?= htmlspecialchars($old['make'] ?? '') ?>" required>
    </div>

    <div class="field">
        <label for="v_model"><?= t('label_model_example') ?></label>
        <input type="text" id="v_model" name="model" value="<?= htmlspecialchars($old['model'] ?? '') ?>" required>
    </div>

    <div class="field">
        <label for="v_year"><?= t('label_year_model') ?></label>
        <input type="number" id="v_year" name="year_model" value="<?= htmlspecialchars($old['year_model'] ?? '') ?>">
    </div>

    <div class="field">
        <label for="v_color"><?= t('label_color') ?></label>
        <input type="text" id="v_color" name="color" value="<?= htmlspecialchars($old['color'] ?? '') ?>">
    </div>

    <div class="field">
        <label for="v_type"><?= t('label_van_type') ?></label>
        <select id="v_type" name="van_type" required>
            <option value="standard"><?= t('vantype_standard') ?></option>
            <option value="premium"><?= t('vantype_premium') ?></option>
            <option value="tourist"><?= t('vantype_tourist') ?></option>
        </select>
    </div>

    <div class="field">
        <label for="v_seats"><?= t('th_seats') ?></label>
        <input type="number" id="v_seats" name="seating_capacity" value="<?= htmlspecialchars($old['seating_capacity'] ?? '') ?>" required min="1" max="30">
    </div>

    <div class="field">
        <label for="v_luggage"><?= t('label_luggage_capacity') ?></label>
        <input type="number" id="v_luggage" name="luggage_capacity" value="<?= htmlspecialchars($old['luggage_capacity'] ?? '0') ?>">
    </div>
    <div class="field">
        <label><input type="checkbox" name="has_aircon" style="width:auto;" checked> <?= t('label_has_aircon') ?></label>
    </div>

    <div class="field">
        <label><input type="checkbox" name="has_wifi" style="width:auto;"> <?= t('label_has_wifi') ?></label>
    </div>

    <div class="field">
        <label for="v_base_fare"><?= t('label_base_fare') ?></label>
        <input type="number" step="0.01" id="v_base_fare" name="base_fare" value="<?= htmlspecialchars($old['base_fare'] ?? '0') ?>">
    </div>

    <div class="field">
        <label for="v_fare_km"><?= t('label_fare_per_km') ?></label>
        <input type="number" step="0.01" id="v_fare_km" name="fare_per_km" value="<?= htmlspecialchars($old['fare_per_km'] ?? '0') ?>">
    </div>

    <div class="field">
        <label for="v_day_rate"><?= t('label_day_rate') ?></label>
        <input type="number" step="0.01" id="v_day_rate" name="whole_van_day_rate" value="<?= htmlspecialchars($old['whole_van_day_rate'] ?? '0') ?>">
    </div>

    <div class="field">
        <label for="v_desc"><?= t('label_description') ?></label>
        <input type="text" id="v_desc" name="description" value="<?= htmlspecialchars($old['description'] ?? '') ?>">
    </div>

    <button type="submit" class="btn"><?= t('btn_save_van') ?></button>
</form>

<?php require __DIR__ . '/_admin_footer.php'; ?>