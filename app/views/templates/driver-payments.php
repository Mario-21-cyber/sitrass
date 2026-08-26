<?php require __DIR__ . '/_driver_header.php'; ?>



<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (empty($payments)): ?>
    <div class="empty-state"><?= t('driver_payments_empty') ?></div>
<?php else: ?>
    <table>
        <tr>
            <th><?= t('th_reference') ?></th>
            <th><?= t('th_customer') ?></th>
            <th><?= t('th_amount') ?></th>
            <th><?= t('th_action') ?></th>
        </tr>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td style="font-family:'SF Mono', monospace; font-weight:600;"><?= htmlspecialchars($p['reference_code']) ?></td>
                <td><?= htmlspecialchars($p['customer_name']) ?></td>
                <td style="font-weight:600;">₱<?= number_format($p['amount'], 2) ?></td>
                <td>
                    <form method="POST" action="/sitrass/public/driver/verifyPayment" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.35rem 0.85rem; font-size:0.82rem;"><?= t('btn_verify') ?></button>
                    </form>
                    <form method="POST" action="/sitrass/public/driver/rejectPayment" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>">
                        <button type="submit" class="btn-danger" style="width:auto; padding:0.35rem 0.85rem; font-size:0.82rem; border:none; border-radius:6px; cursor:pointer;"><?= t('btn_reject') ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_driver_footer.php'; ?>