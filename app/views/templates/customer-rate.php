<?php require __DIR__ . '/_customer_header.php'; ?>

<h2><?= t('nav_rate_trip') ?></h2>

<form method="POST" action="/sitrass/public/customer/submitRating" class="card" style="max-width:450px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="booking_id" value="<?= (int)$booking['booking_id'] ?>">

    <div class="field">
        <label for="rate_overall"><?= t('label_overall_rating') ?></label>
        <select id="rate_overall" name="overall_rating" required>
            <option value="5"><?= t('rate_5') ?></option>
<option value="4"><?= t('rate_4') ?></option>
<option value="3"><?= t('rate_3') ?></option>
<option value="2"><?= t('rate_2') ?></option>
<option value="1"><?= t('rate_1') ?></option>
        </select>
    </div>

    <div class="field">
        <label for="rate_punctuality"><?= t('label_punctuality') ?></label>
    <select id="rate_punctuality" name="punctuality_rating">
        <option value=""><?= t('option_skip') ?></option>
            <option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option>
        </select>
    </div>

    <div class="field">
        <label for="rate_cleanliness"><?= t('label_van_cleanliness') ?></label>
    <select id="rate_cleanliness" name="cleanliness_rating">
        <option value=""><?= t('option_skip') ?></option>
            <option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option>
        </select>
    </div>

    <div class="field">
        <label for="rate_driving"><?= t('label_driving') ?></label>
    <select id="rate_driving" name="driving_rating">
        <option value=""><?= t('option_skip') ?></option>
            <option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option>
        </select>
    </div>

    <div class="field">
        <label for="rate_comment"><?= t('label_comment') ?></label>
        <input type="text" id="rate_comment" name="comment">
    </div>

    <button type="submit" class="btn"><?= t('btn_submit_rating') ?></button>
</form>

<?php require __DIR__ . '/_customer_footer.php'; ?>