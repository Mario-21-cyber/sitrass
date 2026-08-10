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
        <label>Route Code (hal. MAG-CAJ)</label>
        <input type="text" name="route_code" value="<?= htmlspecialchars($old['route_code'] ?? '') ?>" required>
    </div>

    <div class="field">
        <label>Pangalan ng Ruta</label>
        <input type="text" name="route_name" value="<?= htmlspecialchars($old['route_name'] ?? '') ?>" required>
    </div>

    <div class="field">
        <label>Mula (Origin)</label>
        <select name="origin_location_id" required>
            <?php foreach ($locations as $loc): ?>
                <option value="<?= (int)$loc['location_id'] ?>"><?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['municipality']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Papunta (Destination)</label>
        <select name="destination_location_id" required>
            <?php foreach ($locations as $loc): ?>
                <option value="<?= (int)$loc['location_id'] ?>"><?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['municipality']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Distansya (km)</label>
        <input type="number" step="0.01" name="distance_km" value="<?= htmlspecialchars($old['distance_km'] ?? '0') ?>">
    </div>

    <div class="field">
        <label>Tantiyang Oras (minuto)</label>
        <input type="number" name="estimated_duration_minutes" value="<?= htmlspecialchars($old['estimated_duration_minutes'] ?? '0') ?>">
    </div>

    <div class="field">
        <label>Base Fare (PHP)</label>
        <input type="number" step="0.01" name="base_fare" value="<?= htmlspecialchars($old['base_fare'] ?? '0') ?>">
    </div>

    <div class="field">
        <label>Fare per Passenger (PHP)</label>
        <input type="number" step="0.01" name="fare_per_passenger" value="<?= htmlspecialchars($old['fare_per_passenger'] ?? '0') ?>">
    </div>

    <div class="field">
        <label>Kalagayan ng Daan</label>
        <select name="road_condition" required>
            <option value="paved">Paved</option>
            <option value="partially_paved">Partially Paved</option>
            <option value="rough">Rough</option>
        </select>
    </div>

    <button type="submit" class="btn">I-save ang Ruta</button>
</form>

<?php require __DIR__ . '/_admin_footer.php'; ?>