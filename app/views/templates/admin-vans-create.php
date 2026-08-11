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
        <label for="v_plate">Plate Number</label>
        <input type="text" id="v_plate" name="plate_number" value="<?= htmlspecialchars($old['plate_number'] ?? '') ?>" required>
    </div>

    <div class="field">
        <label for="v_make">Make (hal. Toyota)</label>
        <input type="text" id="v_make" name="make" value="<?= htmlspecialchars($old['make'] ?? '') ?>" required>
    </div>

    <div class="field">
        <label for="v_model">Model (hal. HiAce Commuter)</label>
        <input type="text" id="v_model" name="model" value="<?= htmlspecialchars($old['model'] ?? '') ?>" required>
    </div>

    <div class="field">
        <label for="v_year">Year Model</label>
        <input type="number" id="v_year" name="year_model" value="<?= htmlspecialchars($old['year_model'] ?? '') ?>">
    </div>

    <div class="field">
        <label for="v_color">Kulay</label>
        <input type="text" id="v_color" name="color" value="<?= htmlspecialchars($old['color'] ?? '') ?>">
    </div>

    <div class="field">
        <label for="v_type">Tipo ng Van</label>
        <select id="v_type" name="van_type" required>
            <option value="standard">Standard</option>
            <option value="premium">Premium</option>
            <option value="tourist">Tourist</option>
        </select>
    </div>

    <div class="field">
        <label for="v_seats">Bilang ng Upuan</label>
        <input type="number" id="v_seats" name="seating_capacity" value="<?= htmlspecialchars($old['seating_capacity'] ?? '') ?>" required min="1" max="30">
    </div>

    <div class="field">
        <label for="v_luggage">Luggage Capacity</label>
        <input type="number" id="v_luggage" name="luggage_capacity" value="<?= htmlspecialchars($old['luggage_capacity'] ?? '0') ?>">
    </div>
    <div class="field">
        <label><input type="checkbox" name="has_aircon" style="width:auto;" checked> May Aircon</label>
    </div>

    <div class="field">
        <label><input type="checkbox" name="has_wifi" style="width:auto;"> May WiFi</label>
    </div>

    <div class="field">
        <label for="v_base_fare">Base Fare (PHP)</label>
        <input type="number" step="0.01" id="v_base_fare" name="base_fare" value="<?= htmlspecialchars($old['base_fare'] ?? '0') ?>">
    </div>

    <div class="field">
        <label for="v_fare_km">Fare per KM (PHP)</label>
        <input type="number" step="0.01" id="v_fare_km" name="fare_per_km" value="<?= htmlspecialchars($old['fare_per_km'] ?? '0') ?>">
    </div>

    <div class="field">
        <label for="v_day_rate">Whole Van Day Rate (PHP)</label>
        <input type="number" step="0.01" id="v_day_rate" name="whole_van_day_rate" value="<?= htmlspecialchars($old['whole_van_day_rate'] ?? '0') ?>">
    </div>

    <div class="field">
        <label for="v_desc">Deskripsyon</label>
        <input type="text" id="v_desc" name="description" value="<?= htmlspecialchars($old['description'] ?? '') ?>">
    </div>

    <button type="submit" class="btn">I-save ang Van</button>
</form>

<?php require __DIR__ . '/_admin_footer.php'; ?>