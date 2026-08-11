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

<form method="POST" action="/sitrass/public/locations/store" style="max-width:500px;">
    <?= Csrf::field() ?>

    <div class="field">
        <label for="loc_name">Pangalan</label>
        <input type="text" id="loc_name" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
    </div>

    <div class="field">
        <label for="loc_type">Tipo</label>
        <select id="loc_type" name="location_type" required>
            <option value="both">Pickup at Destination</option>
            <option value="pickup">Pickup lang</option>
            <option value="destination">Destination lang</option>
        </select>
    </div>

    <div class="field">
        <label for="loc_category">Kategorya</label>
        <select id="loc_category" name="category" required>
            <option value="port">Port</option>
            <option value="terminal">Terminal</option>
            <option value="town_proper">Town Proper</option>
            <option value="barangay">Barangay</option>
            <option value="resort">Resort</option>
            <option value="landmark">Landmark</option>
            <option value="airport">Airport</option>
            <option value="other">Iba pa</option>
        </select>
    </div>

    <div class="field">
        <label for="loc_barangay">Barangay</label>
        <input type="text" id="loc_barangay" name="barangay" value="<?= htmlspecialchars($old['barangay'] ?? '') ?>">
    </div>

    <div class="field">
        <label for="loc_municipality">Munisipyo</label>
        <select id="loc_municipality" name="municipality" required>
            <option value="Magdiwang">Magdiwang</option>
            <option value="San Fernando">San Fernando</option>
            <option value="Cajidiocan">Cajidiocan</option>
            <option value="Other">Iba pa</option>
        </select>
    </div>

    <div class="field">
        <label for="loc_lat">Latitude</label>
        <input type="text" id="loc_lat" name="latitude" value="<?= htmlspecialchars($old['latitude'] ?? '') ?>" required placeholder="hal. 12.48861">
    </div>

    <div class="field">
        <label for="loc_lng">Longitude</label>
        <input type="text" id="loc_lng" name="longitude" value="<?= htmlspecialchars($old['longitude'] ?? '') ?>" required placeholder="hal. 122.52306">
    </div>

    <div class="field">
        <label for="loc_landmark">Landmark</label>
        <input type="text" id="loc_landmark" name="landmark" value="<?= htmlspecialchars($old['landmark'] ?? '') ?>">
    </div>

    <button type="submit" class="btn">I-save ang Lokasyon</button>
</form>

<?php require __DIR__ . '/_admin_footer.php'; ?>