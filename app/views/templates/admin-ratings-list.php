<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (empty($ratings)): ?>
    <p>Wala pang rating na naitala.</p>
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
                <td><?= htmlspecialchars($r['plate_number'] ?? '—') ?></td>
                <td><?= str_repeat('★', (int)$r['overall_rating']) . str_repeat('☆', 5 - (int)$r['overall_rating']) ?></td>
                <td><?= htmlspecialchars($r['comment'] ?? '') ?></td>
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
                            <button type="submit" style="width:auto; padding:0.3rem 0.7rem; font-size:0.8rem; background:var(--danger); color:#fff; border:none; border-radius:4px; cursor:pointer;">Itago</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/sitrass/public/ratings/unhide" style="display:inline;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="rating_id" value="<?= (int)$r['rating_id'] ?>">
                            <button type="submit" class="btn" style="width:auto; padding:0.3rem 0.7rem; font-size:0.8rem;">Ipakita Ulit</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>