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

<?php if (empty($vans)): ?>
    <p>Wala pang van. <a href="/sitrass/public/vans/create">Magdagdag muna ng van</a>.</p>
<?php elseif (empty($routes)): ?>
    <p>Wala pang ruta. <a href="/sitrass/public/routes/create">Magdagdag muna ng ruta</a>.</p>
<?php else: ?>
    <form method="POST" action="/sitrass/public/schedules/store" style="max-width:500px;">
        <?= Csrf::field() ?>

        <div class="field">
            <label>Ruta</label>
            <select name="route_id" required>
                <?php foreach ($routes as $r): ?>
                    <option value="<?= (int)$r['route_id'] ?>"><?= htmlspecialchars($r['route_code'] . ' - ' . $r['route_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Van</label>
            <select name="van_id" required>
                <?php foreach ($vans as $v): ?>
                    <option value="<?= (int)$v['van_id'] ?>"><?= htmlspecialchars($v['plate_number'] . ' - ' . $v['make'] . ' ' . $v['model']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Driver (opsyonal, puwedeng itakda mamaya)</label>
            <select name="driver_id">
                <option value="">-- Wala pa --</option>
                <?php foreach ($drivers as $d): ?>
                    <option value="<?= (int)$d['driver_id'] ?>"><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Petsa ng Biyahe</label>
            <input type="date" name="departure_date" value="<?= htmlspecialchars($old['departure_date'] ?? '') ?>" required min="<?= date('Y-m-d') ?>">
        </div>

        <div class="field">
            <label>Oras ng Alis</label>
            <input type="time" name="departure_time" value="<?= htmlspecialchars($old['departure_time'] ?? '') ?>" required>
        </div>

        <div class="field">
            <label>Tantiyang Oras ng Dating</label>
            <input type="time" name="estimated_arrival" value="<?= htmlspecialchars($old['estimated_arrival'] ?? '') ?>">
        </div>

        <div class="field">
            <label>Bilang ng Upuan</label>
            <input type="number" name="total_seats" value="<?= htmlspecialchars($old['total_seats'] ?? '15') ?>" required min="1" max="30">
        </div>

        <div class="field">
            <label>Pamasahe kada Upuan (PHP)</label>
            <input type="number" step="0.01" name="fare_per_seat" value="<?= htmlspecialchars($old['fare_per_seat'] ?? '0') ?>" required>
        </div>

        <div class="field">
            <label>Booking Mode</label>
            <select name="booking_mode" required>
                <option value="seat">Per Seat (shared)</option>
                <option value="exclusive">Exclusive (buong van)</option>
            </select>
        </div>

        <button type="submit" class="btn">I-save ang Schedule</button>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>