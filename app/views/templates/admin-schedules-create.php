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
    <p><?= t('schedule_no_vans') ?> <a href="/sitrass/public/vans/create"><?= t('schedule_add_van_first') ?></a>.</p>
<?php elseif (empty($routes)): ?>
    <p><?= t('schedule_no_routes') ?> <a href="/sitrass/public/routes/create"><?= t('schedule_add_route_first') ?></a>.</p>
<?php else: ?>
    <form method="POST" action="/sitrass/public/schedules/store" style="max-width:500px;">
        <?= Csrf::field() ?>

        <div class="field">
            <label for="s_route"><?= t('th_route') ?></label>
            <select id="s_route" name="route_id" required>
                <?php foreach ($routes as $r): ?>
                    <option value="<?= (int)$r['route_id'] ?>"><?= htmlspecialchars($r['route_code'] . ' - ' . $r['route_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="s_van"><?= t('th_van') ?></label>
            <select id="s_van" name="van_id" required>
                <?php foreach ($vans as $v): ?>
                    <option value="<?= (int)$v['van_id'] ?>"><?= htmlspecialchars($v['plate_number'] . ' - ' . $v['make'] . ' ' . $v['model']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="s_driver"><?= t('label_driver_optional') ?></label>
            <select id="s_driver" name="driver_id">
                <option value=""><?= t('option_none_yet') ?></option>
                <?php foreach ($drivers as $d): ?>
                    <option value="<?= (int)$d['driver_id'] ?>"><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="s_date"><?= t('label_departure_date') ?></label>
            <input type="date" id="s_date" name="departure_date" value="<?= htmlspecialchars($old['departure_date'] ?? '') ?>" required min="<?= date('Y-m-d') ?>">
        </div>

        <div class="field">
            <label for="s_time"><?= t('label_departure_time') ?></label>
            <input type="time" id="s_time" name="departure_time" value="<?= htmlspecialchars($old['departure_time'] ?? '') ?>" required>
        </div>

        <div class="field">
            <label for="s_arrival"><?= t('label_est_arrival') ?></label>
            <input type="time" id="s_arrival" name="estimated_arrival" value="<?= htmlspecialchars($old['estimated_arrival'] ?? '') ?>">
        </div>

        <div class="field">
            <label for="s_seats"><?= t('th_seats') ?></label>
            <input type="number" id="s_seats" name="total_seats" value="<?= htmlspecialchars($old['total_seats'] ?? '15') ?>" required min="1" max="30">
        </div>

        <div class="field">
            <label for="s_fare"><?= t('label_fare_per_seat') ?></label>
            <input type="number" step="0.01" id="s_fare" name="fare_per_seat" value="<?= htmlspecialchars($old['fare_per_seat'] ?? '0') ?>" required>
        </div>

        <div class="field">
            <label for="s_mode"><?= t('label_booking_mode') ?></label>
            <select id="s_mode" name="booking_mode" required>
                <option value="seat"><?= t('mode_per_seat') ?></option>
                <option value="exclusive"><?= t('mode_exclusive') ?></option>
            </select>
        </div>

        <button type="submit" class="btn"><?= t('btn_save_schedule') ?></button>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>