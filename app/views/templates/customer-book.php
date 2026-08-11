<?php require __DIR__ . '/_customer_header.php'; ?>

<h2>Kumpirmahin ang Booking</h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul style="margin:0; padding-left: 1.2rem;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.5rem; margin-bottom:1.5rem;">
    <strong><?= htmlspecialchars($route['origin_name']) ?> &rarr; <?= htmlspecialchars($route['destination_name']) ?></strong><br>
    <span style="color:var(--ocean);"><?= htmlspecialchars($schedule['departure_date']) ?> @ <?= htmlspecialchars($schedule['departure_time']) ?></span><br>
    <span style="font-size:0.9rem;"><?= (int)$schedule['available_seats'] ?> upuang bakante &middot; ₱<?= htmlspecialchars($schedule['fare_per_seat']) ?> per upuan</span>
</div>

<form method="POST" action="/sitrass/public/customer/confirmBooking" style="max-width:400px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="schedule_id" value="<?= (int)$schedule['schedule_id'] ?>">

    <div class="field">
        <label for="bk_pax">Bilang ng Pasahero</label>
        <input type="number" id="bk_pax" name="passenger_count" value="1" min="1" max="<?= (int)$schedule['available_seats'] ?>" required>
    </div>

    <div class="field">
        <label for="bk_method">Paraan ng Pagbabayad</label>
        <select id="bk_method" name="method_id" required>
            <?php foreach ($methods as $m): ?>
                <option value="<?= (int)$m['method_id'] ?>"><?= htmlspecialchars($m['method_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <p style="font-size:0.85rem; color:var(--ocean);">
        Kailangan ng <?= htmlspecialchars($depositPercentage) ?>% deposit sa loob ng 2 oras para makumpirma ang reservation.
    </p>

    <button type="submit" class="btn">Kumpirmahin ang Booking</button>
</form>

<?php require __DIR__ . '/_customer_footer.php'; ?>