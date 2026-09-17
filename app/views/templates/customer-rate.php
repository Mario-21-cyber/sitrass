<?php require __DIR__ . '/_customer_header.php'; ?>

<?php $isEdit = !empty($existing); ?>
<?php if ($isEdit): ?>
    <div class="alert alert-success"><?= t('rate_edit_note') ?></div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/customer/submitRating" class="card" style="max-width:450px;">
    <?= Csrf::field() ?>
    <?php if (!empty($booking)): ?>
        <input type="hidden" name="booking_id" value="<?= (int)$booking['booking_id'] ?>">
    <?php else: ?>
        <input type="hidden" name="rental_id" value="<?= (int)$rental['rental_id'] ?>">
        <p class="text-sm text-muted" style="margin:0 0 0.75rem;">
            <?= htmlspecialchars($rental['reference_code'] ?? '—') ?> &middot;
            <?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?> (<?= htmlspecialchars($rental['plate_number']) ?>)
            <?php if (!empty($rental['driver_name'])): ?>
                &middot; <?= t('label_driver') ?>: <?= htmlspecialchars($rental['driver_name']) ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <div class="field">
        <label for="rate_overall"><?= t('label_overall_rating') ?></label>
        <select id="rate_overall" name="overall_rating" required>
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>" <?= ($isEdit && (int)$existing['overall_rating'] === $i) ? 'selected' : '' ?>><?= $i ?> - <?= t('rate_' . $i) ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="field">
        <label for="rate_punctuality"><?= t('label_punctuality') ?></label>
        <select id="rate_punctuality" name="punctuality_rating">
            <option value=""><?= t('option_skip') ?></option>
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>" <?= ($isEdit && (int)($existing['punctuality_rating'] ?? 0) === $i) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="field">
        <label for="rate_cleanliness"><?= t('label_van_cleanliness') ?></label>
        <select id="rate_cleanliness" name="cleanliness_rating">
            <option value=""><?= t('option_skip') ?></option>
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>" <?= ($isEdit && (int)($existing['cleanliness_rating'] ?? 0) === $i) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="field">
        <label for="rate_driving"><?= t('label_driving') ?></label>
        <select id="rate_driving" name="driving_rating">
            <option value=""><?= t('option_skip') ?></option>
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>" <?= ($isEdit && (int)($existing['driving_rating'] ?? 0) === $i) ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="field">
        <label for="rate_comment"><?= t('label_comment') ?></label>
        <input type="text" id="rate_comment" name="comment" value="<?= $isEdit ? htmlspecialchars($existing['comment'] ?? '') : '' ?>">
    </div>

    <button type="submit" class="btn"><?= $isEdit ? t('btn_update_rating') : t('btn_submit_rating') ?></button>
</form>

<?php require __DIR__ . '/_customer_footer.php'; ?>