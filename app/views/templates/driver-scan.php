<?php require __DIR__ . '/_driver_header.php'; ?>

<h2>I-verify ang QR Code</h2>

<?php if (!empty($result)): ?>
    <div class="alert <?= $result['success'] ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars($result['message']) ?>
    </div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/driver/verifyQr" style="max-width:400px;">
    <?= Csrf::field() ?>
    <div class="field">
        <label>I-type o i-paste ang QR code</label>
        <input type="text" name="token" required autofocus>
    </div>
    <button type="submit" class="btn">I-verify</button>
</form>

<p style="font-size:0.85rem; color:var(--ocean); margin-top:1rem;">
    Kung camera-based na scanning ang gusto, ang code na naka-encode sa QR ay eksaktong parehong text na ito - puwedeng i-type nang manu-mano bilang alternatibo.
</p>

<a href="/sitrass/public/driver/dashboard" class="btn-link">Bumalik sa Dashboard</a>

<?php require __DIR__ . '/_driver_footer.php'; ?>