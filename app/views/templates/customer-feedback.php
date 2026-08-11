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
            <option value="bug">Bug / Problema sa System</option>
            <option value="suggestion">Mungkahi</option>
            <option value="complaint">Reklamo</option>
            <option value="compliment">Papuri</option>
            <option value="other">Iba pa</option>
        </select>
    </div>

    <div class="field">
        <label for="fb_subject">Paksa</label>
        <input type="text" id="fb_subject" name="subject" required>
    </div>

    <div class="field">
        <label for="fb_message">Mensahe</label>
        <input type="text" id="fb_message" name="message" required>
    </div>

    <div class="field">
        <label for="fb_email">Email para sa Sagot (opsyonal)</label>
        <input type="email" id="fb_email" name="contact_email">
    </div>

    <button type="submit" class="btn">Ipadala</button>
</form>

<?php require __DIR__ . '/_customer_footer.php'; ?>