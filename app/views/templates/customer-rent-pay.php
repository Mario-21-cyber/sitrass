<?php require __DIR__ . '/_customer_header.php'; ?>

<h2><?= t('rent_pay_title') ?></h2>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error ?? '') ?></div>
<?php endif; ?>

<div class="card">
    <p><strong><?= t('rent_th_ref') ?>:</strong> <?= htmlspecialchars($rental['reference_code'] ?? '—') ?></p>
    <p><strong><?= t('rent_total_label') ?>:</strong> ₱<?= number_format((float)$rental['total_price'], 2) ?></p>
    <p><strong><?= t('rent_pay_deposit') ?>:</strong> ₱<?= number_format((float)$rental['deposit_required'], 2) ?></p>
    <p style="margin:0;"><strong><?= t('label_balance') ?>:</strong> ₱<?= number_format($balanceDue, 2) ?></p>
</div>

<?php foreach ($methods as $m): ?>
    <div class="method-details" id="details_<?= (int)$m['method_id'] ?>" style="display:none; background:var(--teal-dark); color:var(--white); border-radius:var(--radius); padding:1.25rem; margin-bottom:1.5rem;">
        <?php if ($m['is_online'] && $m['account_number']): ?>
            <div class="stub-label" style="color:var(--amber-light); font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.4rem;">
                <?= t('send_to_method') ?> <?= htmlspecialchars($m['method_name']) ?>
            </div>
            <div style="font-size:1.1rem; font-weight:700;"><?= htmlspecialchars($m['account_name']) ?></div>
            <div style="font-family:'SF Mono', monospace; font-size:1.3rem; letter-spacing:0.05em;"><?= htmlspecialchars($m['account_number']) ?></div>
        <?php endif; ?>
        <?php if ($m['instructions']): ?>
            <p style="margin-top:0.75rem; margin-bottom:0; font-size:0.85rem; opacity:0.9;"><?= htmlspecialchars($m['instructions']) ?></p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<form method="POST" action="/sitrass/public/customer/rentPaySubmit" enctype="multipart/form-data" style="max-width:450px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="rental_id" value="<?= (int)$rental['rental_id'] ?>">

    <div class="field">
        <label for="method_id"><?= t('label_payment_method') ?></label>
        <select name="method_id" id="method_id" required onchange="updateMethodDisplay()">
            <?php foreach ($methods as $m): ?>
                <option value="<?= (int)$m['method_id'] ?>"
                    data-requires-proof="<?= $m['requires_proof'] ?>"
                    data-is-online="<?= (int)$m['is_online'] ?>">
                    <?= htmlspecialchars($m['method_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label for="pay_amount"><?= t('label_amount_to_pay') ?></label>
        <input type="number" step="0.01" id="pay_amount" name="amount" value="<?= htmlspecialchars(number_format($amountToPay, 2, '.', '')) ?>" required>
    </div>

    <div class="field" id="ref_field">
        <label for="pay_ref"><?= t('label_ref_number_gcash') ?></label>
        <input type="text" id="pay_ref" name="reference_number">
    </div>

    <div class="field" id="proof_field">
        <label for="pay_proof"><?= t('label_payment_proof') ?></label>
        <input type="file" id="pay_proof" name="proof" accept="image/jpeg,image/png">
    </div>

    <button type="submit" class="btn"><?= t('btn_submit_payment') ?></button>
</form>

<script>
function updateMethodDisplay() {
    const select = document.getElementById('method_id');
    const selectedOption = select.options[select.selectedIndex];
    const requiresProof = selectedOption.getAttribute('data-requires-proof') === '1';
    const isOnline = selectedOption.getAttribute('data-is-online') === '1';

    document.getElementById('proof_field').style.display = requiresProof ? 'block' : 'none';

    // Ang reference number ay para lang sa ONLINE na paraan (hal. GCash)
    const refField = document.getElementById('ref_field');
    refField.style.display = isOnline ? 'block' : 'none';
    document.getElementById('pay_ref').disabled = !isOnline;

    document.querySelectorAll('.method-details').forEach(el => el.style.display = 'none');
    const activeDetails = document.getElementById('details_' + select.value);
    if (activeDetails) {
        activeDetails.style.display = 'block';
    }
}
updateMethodDisplay();
</script>

<a href="/sitrass/public/customer/myBookings?tab=rentals" class="btn-link" style="margin-top:1.5rem; display:inline-block;"><?= t('btn_back') ?></a>

<?php require __DIR__ . '/_customer_footer.php'; ?>