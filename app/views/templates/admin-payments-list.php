<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (empty($payments)): ?>
    <p>Walang pending na payment sa ngayon.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Reference</th>
            <th>Customer</th>
            <th>Paraan</th>
            <th>Halaga</th>
            <th>Proof</th>
            <th>Aksyon</th>
        </tr>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['reference_code']) ?></td>
                <td><?= htmlspecialchars($p['customer_name']) ?></td>
                <td><?= htmlspecialchars($p['method_name']) ?></td>
                <td>₱<?= htmlspecialchars($p['amount']) ?></td>
                <td>
                    <?php if ($p['proof_image']): ?>
                        <a href="<?= htmlspecialchars($p['proof_image']) ?>" target="_blank">Tingnan</a>
                    <?php else: ?>
                        &mdash;
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="/sitrass/public/payments/verify" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.3rem 0.7rem; font-size:0.8rem;">I-verify</button>
                    </form>
                    <form method="POST" action="/sitrass/public/payments/reject" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>">
                        <button type="submit" style="width:auto; padding:0.3rem 0.7rem; font-size:0.8rem; background:var(--danger); color:#fff; border:none; border-radius:4px; cursor:pointer;">Tanggihan</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>