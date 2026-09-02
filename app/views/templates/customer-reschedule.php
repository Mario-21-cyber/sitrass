<?php require __DIR__ . '/_customer_header.php'; ?>

<h2><?= t('heading_reschedule_trip') ?></h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.5rem; margin-bottom:1.5rem;">
    <p><strong><?= t('label_current_date') ?>:</strong> <?= htmlspecialchars($booking['travel_date']) ?> @ <?= htmlspecialchars($booking['pickup_time']) ?></p>
    <p><strong><?= t('label_passenger_count') ?>:</strong> <?= (int)$booking['seats_booked'] ?></p>
</div>

<?php if (empty($alternatives)): ?>
    <p><?= t('empty_no_alternative_trip') ?></p>
<?php else: ?>
    <form method="POST" action="/sitrass/public/customer/confirmReschedule">
        <?= Csrf::field() ?>
        <input type="hidden" name="reference_code" value="<?= htmlspecialchars($reservation['reference_code']) ?>">

        <?php foreach ($alternatives as $alt): ?>
            <label style="display:block; background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1rem; margin-bottom:0.75rem; cursor:pointer;">
                <input type="radio" name="new_schedule_id" value="<?= (int)$alt['schedule_id'] ?>" required style="width:auto; margin-right:0.5rem;">
                <?= htmlspecialchars($alt['departure_date']) ?> @ <?= htmlspecialchars($alt['departure_time']) ?>
                &middot; <?= (int)$alt['available_seats'] ?> <?= t('label_available_seats') ?>
                &middot; <?= htmlspecialchars($alt['plate_number']) ?>
            </label>
        <?php endforeach; ?>

        <button type="submit" class="btn" style="width:auto; padding:0.7rem 2rem; margin-top:1rem;"><?= t('btn_confirm_new_date') ?></button>
    </form>
<?php endif; ?>

<a href="/sitrass/public/customer/myBookings" class="btn-link"><?= t('btn_back') ?></a>

<?php require __DIR__ . '/_customer_footer.php'; ?>
