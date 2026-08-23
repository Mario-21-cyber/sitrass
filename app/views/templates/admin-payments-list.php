<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (empty($payments)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--border);margin-bottom:0.75rem;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <div>Walang pending na payment sa ngayon.</div>
        <div class="text-sm">Lahat ng payment ay na-verify na.</div>
    </div>
<?php else: ?>
    <table>
        <tr>
            <th><?= t('th_reference') ?></th>
            <th><?= t('th_customer') ?></th>
            <th><?= t('th_method') ?></th>
            <th><?= t('th_amount') ?></th>
            <th><?= t('th_proof') ?></th>
            <th><?= t('th_action') ?></th>
        </tr>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td style="font-family:'SF Mono', monospace; font-weight:600;"><?= htmlspecialchars($p['reference_code']) ?></td>
                <td><?= htmlspecialchars($p['customer_name']) ?></td>
                <td><span class="badge badge-neutral"><?= htmlspecialchars($p['method_name']) ?></span></td>
                <td style="font-weight:600;">₱<?= number_format($p['amount'], 2) ?></td>
                <td>
                    <?php if ($p['proof_image']): ?>
                        <a href="<?= htmlspecialchars($p['proof_image']) ?>" target="_blank" class="btn-ghost"><?= t('link_view') ?></a>
                    <?php else: ?>
                        <span class="text-muted">&mdash;</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="/sitrass/public/payments/verify" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.35rem 0.85rem; font-size:0.82rem;"><?= t('btn_verify') ?></button>
                    </form>
                    <form method="POST" action="/sitrass/public/payments/reject" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>">
                        <button type="submit" class="btn-danger" style="width:auto; padding:0.35rem 0.85rem; font-size:0.82rem; border:none; border-radius:6px; cursor:pointer;"><?= t('btn_reject') ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>