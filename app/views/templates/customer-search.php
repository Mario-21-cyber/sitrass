<?php require __DIR__ . '/_customer_header.php'; ?>

<h2>Maghanap ng Biyahe</h2>

<form method="GET" action="/sitrass/public/customer/search" style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.5rem; margin-bottom:1.5rem;">
    <div style="display:flex; gap:1rem; flex-wrap:wrap;">
        <div class="field" style="flex:1; min-width:200px;">
            <label>Mula</label>
            <select name="origin">
                <option value="">-- Kahit saan --</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= (int)$loc['location_id'] ?>" <?= $selectedOrigin == $loc['location_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['municipality']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field" style="flex:1; min-width:200px;">
            <label>Papunta</label>
            <select name="destination">
                <option value="">-- Kahit saan --</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= (int)$loc['location_id'] ?>" <?= $selectedDestination == $loc['location_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc['name']) ?> (<?= htmlspecialchars($loc['municipality']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field" style="flex:1; min-width:150px;">
            <label>Petsa</label>
            <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" min="<?= date('Y-m-d') ?>">
        </div>
    </div>

    <button type="submit" class="btn" style="width:auto; padding:0.7rem 2rem; margin-top:1rem;">Maghanap</button>
</form>

<?php if ($results !== null): ?>
    <?php if (empty($results)): ?>
        <p>Walang nahanap na biyahe na tugma sa iyong hinanap.</p>
    <?php else: ?>
        <?php foreach ($results as $trip): ?>
            <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem; margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong><?= htmlspecialchars($trip['origin_name']) ?> &rarr; <?= htmlspecialchars($trip['destination_name']) ?></strong><br>
                    <span style="color:var(--ocean); font-size:0.9rem;">
                        <?= htmlspecialchars($trip['departure_date']) ?> @ <?= htmlspecialchars($trip['departure_time']) ?>
                    </span><br>
                    <span style="font-size:0.85rem;">
                        <?= htmlspecialchars($trip['plate_number']) ?> - <?= htmlspecialchars($trip['make'] . ' ' . $trip['model']) ?>
                        &middot; <?= (int)$trip['available_seats'] ?> upuang bakante
                        <?php if ($trip['driver_name']): ?>
                            &middot; Driver: <?= htmlspecialchars($trip['driver_name']) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div style="text-align:right;">
                    <div style="font-family:'SF Mono', monospace; font-size:1.2rem; font-weight:700; color:var(--teal-dark);">
                        ₱<?= htmlspecialchars($trip['fare_per_seat']) ?>
                    </div>
                    <span style="font-size:0.8rem; color:var(--ocean);">per upuan</span><br>
                    <a href="/sitrass/public/customer/book/<?= (int)$trip['schedule_id'] ?>" class="btn" style="display:inline-block; width:auto; padding:0.4rem 1rem; margin-top:0.5rem; text-decoration:none; font-size:0.85rem;">Mag-book</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/_customer_footer.php'; ?>