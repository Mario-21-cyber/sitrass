<?php require __DIR__ . '/_admin_header.php'; ?>

<p><a href="/sitrass/public/vans/create" class="btn" style="display:inline-block; width:auto; padding:0.6rem 1.2rem; text-decoration:none;">+ Magdagdag ng Van</a></p>

<?php if (empty($vans)): ?>
    <p>Wala pang van na naitala.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Plate Number</th>
            <th>Van</th>
            <th>Tipo</th>
            <th>Upuan</th>
            <th>Status</th>
            <th>Aksyon</th>
        </tr>
        <?php foreach ($vans as $van): ?>
            <tr>
                <td><?= htmlspecialchars($van['plate_number']) ?></td>
                <td><?= htmlspecialchars($van['make'] . ' ' . $van['model']) ?></td>
                <td><?= htmlspecialchars($van['van_type']) ?></td>
                <td><?= (int)$van['seating_capacity'] ?></td>
                <td>
                    <span class="badge <?= $van['status'] === 'active' ? 'badge-active' : 'badge-pending' ?>">
                        <?= htmlspecialchars($van['status']) ?>
                    </span>
                </td>
                <td>
    <a href="/sitrass/public/vans/images/<?= (int)$van['van_id'] ?>" style="margin-right:0.5rem; font-size:0.85rem;">Mga Larawan</a>
    <?php if ($van['status'] === 'active'): ?>
                        <form method="POST" action="/sitrass/public/vans/toggleStatus" style="display:inline;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="van_id" value="<?= (int)$van['van_id'] ?>">
                            <input type="hidden" name="status" value="maintenance">
                            <button type="submit" class="btn" style="width:auto; padding:0.3rem 0.7rem; font-size:0.8rem;">I-maintenance</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/sitrass/public/vans/toggleStatus" style="display:inline;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="van_id" value="<?= (int)$van['van_id'] ?>">
                            <input type="hidden" name="status" value="active">
                            <button type="submit" class="btn" style="width:auto; padding:0.3rem 0.7rem; font-size:0.8rem;">I-activate</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>