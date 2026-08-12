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
            <th>Reference</th>
            <th>Customer</th>
            <th>Paraan</th>
            <th>Halaga</th>
            <th>Proof</th>
            <th>Aksyon</th>
        </tr>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td style="font-family:'SF Mono', monospace; font-weight:600;"><?= htmlspecialchars($p['reference_code']) ?></td>
                <td><?= htmlspecialchars($p['customer_name']) ?></td>
                <td><span class="badge badge-neutral"><?= htmlspecialchars($p['method_name']) ?></span></td>
                <td style="font-weight:600;">₱<?= number_format($p['amount'], 2) ?></td>
                <td>
                    <?php if ($p['proof_image']): ?>
                        <a href="<?= htmlspecialchars($p['proof_image']) ?>" target="_blank" class="btn-ghost">Tingnan</a>
                    <?php else: ?>
                        <span class="text-muted">&mdash;</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="/sitrass/public/payments/verify" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.35rem 0.85rem; font-size:0.82rem;">I-verify</button>
                    </form>
                    <form method="POST" action="/sitrass/public/payments/reject" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>">
                        <button type="submit" class="btn-danger" style="width:auto; padding:0.35rem 0.85rem; font-size:0.82rem; border:none; border-radius:6px; cursor:pointer;">Tanggihan</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>