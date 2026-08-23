<?php require __DIR__ . '/_customer_header.php'; ?>

<h2>Ipadala ang Iyong Feedback</h2>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/customer/submitFeedback" class="card" style="max-width:500px;">
    <?= Csrf::field() ?>

    <div class="field">
        <label for="fb_category">Kategorya</label>
        <select id="fb_category" name="category" required>
            <option value="bug"><?= t('feedback_option_bug') ?></option>
            <option value="suggestion"><?= t('feedback_option_suggestion') ?></option>
            <option value="complaint"><?= t('feedback_option_complaint') ?></option>
            <option value="compliment"><?= t('feedback_option_compliment') ?></option>
            <option value="other"><?= t('feedback_option_other') ?></option>
        </select>
    </div>

    <div class="field">
        <label for="fb_subject"><?= t('label_subject') ?></label>
        <input type="text" id="fb_subject" name="subject" required>
    </div>

    <div class="field">
        <label for="fb_message"><?= t('label_message') ?></label>
        <input type="text" id="fb_message" name="message" required>
    </div>

    <div class="field">
        <label for="fb_email"><?= t('label_contact_email') ?></label>
        <input type="email" id="fb_email" name="contact_email">
    </div>

    <button type="submit" class="btn"><?= t('btn_submit') ?></button>
</form>

<?php require __DIR__ . '/_customer_footer.php'; ?>