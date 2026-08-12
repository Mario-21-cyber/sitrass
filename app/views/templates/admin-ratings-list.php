<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (empty($ratings)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--border);margin-bottom:0.75rem;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        <div>Wala pang rating na naitala.</div>
    </div>
<?php else: ?>
    <table>
        <tr>
            <th>Customer</th>
            <th>Driver</th>
            <th>Van</th>
            <th>Rating</th>
            <th>Komento</th>
            <th>Status</th>
            <th>Aksyon</th>
        </tr>
        <?php foreach ($ratings as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['customer_name']) ?></td>
                <td><?= htmlspecialchars($r['driver_name'] ?? '—') ?></td>
                <td class="text-muted"><?= htmlspecialchars($r['plate_number'] ?? '—') ?></td>
                <td style="color:#A6650C; letter-spacing:1px;"><?= str_repeat('★', (int)$r['overall_rating']) . str_repeat('☆', 5 - (int)$r['overall_rating']) ?></td>
                <td class="td-truncate"><?= htmlspecialchars($r['comment'] ?? '') ?></td>
                <td>
                    <span class="badge <?= $r['is_visible'] ? 'badge-active' : 'badge-pending' ?>">
                        <?= $r['is_visible'] ? 'Nakikita' : 'Nakatago' ?>
                    </span>
                </td>
                <td>
                    <?php if ($r['is_visible']): ?>
                        <form method="POST" action="/sitrass/public/ratings/hide" style="display:inline;" onsubmit="return confirm('Itago ang rating na ito?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="rating_id" value="<?= (int)$r['rating_id'] ?>">
                            <button type="submit" class="btn-danger" style="width:auto; padding:0.35rem 0.85rem; font-size:0.82rem; border:none; border-radius:6px; cursor:pointer;">Itago</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/sitrass/public/ratings/unhide" style="display:inline;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="rating_id" value="<?= (int)$r['rating_id'] ?>">
                            <button type="submit" class="btn" style="width:auto; padding:0.35rem 0.85rem; font-size:0.82rem;">Ipakita Ulit</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>