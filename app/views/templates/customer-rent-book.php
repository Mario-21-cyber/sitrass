<?php require __DIR__ . '/_customer_header.php'; ?>

<a href="/sitrass/public/customer/rentVan" class="btn-ghost" style="display:inline-block; text-decoration:none; margin-bottom:1rem;">&larr; <?= t('rent_back') ?></a>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error ?? '') ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; display:flex; gap:1rem; align-items:center;">
    <?php if (!empty($van['primary_image'])): ?>
        <img src="<?= htmlspecialchars($van['primary_image']) ?>" alt="" style="width:110px; height:80px; object-fit:cover; border-radius:8px;">
    <?php else: ?>
        <div style="width:110px; height:80px; background:var(--slate-bg); border-radius:8px; display:flex; align-items:center; justify-content:center;">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--slate)" stroke-width="1.5" style="width:36px;height:36px;"><path d="M1 8h13v9H1z"/><path d="M14 11h4l3 3v3h-7z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="17.5" cy="18.5" r="2"/></svg>
        </div>
    <?php endif; ?>
    <div>
        <strong style="font-size:1.05rem;"><?= htmlspecialchars($van['make'] . ' ' . $van['model']) ?></strong>
        <div class="text-sm text-muted" style="font-family:'SF Mono', monospace;"><?= htmlspecialchars($van['plate_number']) ?> &middot; <?= (int)$van['seating_capacity'] ?> <?= t('unit_passengers') ?></div>
        <div style="font-weight:700; margin-top:0.25rem;"><?= t('currency_symbol') ?><?= number_format((float)$van['whole_van_day_rate'], 2) ?><span class="text-sm text-muted"> <?= t('rent_per_day') ?></span></div>
    </div>
</div>

<form method="POST" action="/sitrass/public/customer/rentVanStore" style="max-width:500px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="van_id" value="<?= (int)$van['van_id'] ?>">

    <div class="field">
        <label for="r_start"><?= t('rent_start_date') ?></label>
        <input type="date" id="r_start" name="start_date" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($old['start_date'] ?? '') ?>">
    </div>

    <div class="field">
        <label for="r_end"><?= t('rent_end_date') ?></label>
        <input type="date" id="r_end" name="end_date" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($old['end_date'] ?? '') ?>">
    </div>

    <div class="field">
        <label><?= t('rent_route') ?></label>
        <select name="route_id" required>
            <option value="">— <?= t('option_none_yet') ?> —</option>
            <?php foreach ($routes as $r): ?>
                <option value="<?= (int)$r['route_id'] ?>"><?= htmlspecialchars($r['route_code'] . ' — ' . $r['origin_name'] . ' → ' . $r['destination_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <small class="text-muted" style="display:block; margin-top:0.25rem;"><?= t('rent_route_note') ?></small>
    </div>

    <div class="field">
        <label for="r_pickup"><?= t('rent_pickup_location') ?></label>
        <select id="r_pickup" name="pickup_location_id">
            <option value="">— <?= t('option_none_yet') ?> —</option>
            <?php foreach ($locations as $loc): ?>
                <option value="<?= (int)$loc['location_id'] ?>"><?= htmlspecialchars($loc['name'] . ' (' . $loc['municipality'] . ')') ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label for="r_notes"><?= t('rent_notes') ?></label>
        <textarea id="r_notes" name="notes" rows="2"><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
    </div>

    <div class="card" style="background:var(--slate-bg); margin-bottom:1rem;">
        <div style="display:flex; justify-content:space-between; margin-bottom:0.35rem;">
            <span class="text-sm text-muted"><?= t('rent_days_label') ?></span>
            <strong id="r_days">1</strong>
        </div>
        <div style="display:flex; justify-content:space-between;">
            <span class="text-sm text-muted"><?= t('rent_total_label') ?></span>
            <strong id="r_total" style="color:var(--teal-dark);"><?= t('currency_symbol') ?><?= number_format((float)$van['whole_van_day_rate'], 2) ?></strong>
        </div>
    </div>

    <button type="submit" class="btn"><?= t('rent_btn_confirm') ?></button>
</form>

<script>
// Live na computation: araw x rent price kada araw
const RATE = <?= json_encode((float)$van['whole_van_day_rate']) ?>;

function recalcRental() {
    const s = document.getElementById('r_start').value;
    const e = document.getElementById('r_end').value;
    const daysEl = document.getElementById('r_days');
    const totalEl = document.getElementById('r_total');
    if (!s || !e) return;

    const days = Math.floor((new Date(e) - new Date(s)) / 86400000) + 1;
    if (days >= 1) {
        daysEl.textContent = days;
        totalEl.textContent = '₱' + (days * RATE).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

document.getElementById('r_start').addEventListener('change', function() {
    // Hindi pwedeng mas maaga ang wakas kaysa simula
    document.getElementById('r_end').min = this.value;
    recalcRental();
});
document.getElementById('r_end').addEventListener('change', recalcRental);
recalcRental();
</script>

<?php require __DIR__ . '/_customer_footer.php'; ?>
