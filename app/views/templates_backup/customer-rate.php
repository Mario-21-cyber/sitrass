<?php require __DIR__ . '/_customer_header.php'; ?>

<h2>I-rate ang Biyahe</h2>

<form method="POST" action="/sitrass/public/customer/submitRating" class="card" style="max-width:450px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="booking_id" value="<?= (int)$booking['booking_id'] ?>">

    <div class="field">
        <label>Pangkalahatang Rating (1-5)</label>
        <select name="overall_rating" required>
            <option value="5">5 - Napakahusay</option>
            <option value="4">4 - Mahusay</option>
            <option value="3">3 - Okay lang</option>
            <option value="2">2 - Hindi Maganda</option>
            <option value="1">1 - Napakasama</option>
        </select>
    </div>

    <div class="field">
        <label>Punctuality (opsyonal)</label>
        <select name="punctuality_rating">
            <option value="">-- Laktawan --</option>
            <option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option>
        </select>
    </div>

    <div class="field">
        <label>Kalinisan ng Van (opsyonal)</label>
        <select name="cleanliness_rating">
            <option value="">-- Laktawan --</option>
            <option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option>
        </select>
    </div>

    <div class="field">
        <label>Pagmamaneho (opsyonal)</label>
        <select name="driving_rating">
            <option value="">-- Laktawan --</option>
            <option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option>
        </select>
    </div>

    <div class="field">
        <label>Komento (opsyonal)</label>
        <input type="text" name="comment">
    </div>

    <button type="submit" class="btn">Isumite ang Rating</button>
</form>

<?php require __DIR__ . '/_customer_footer.php'; ?>