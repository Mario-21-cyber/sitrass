<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (empty($logs)): ?>
    <p>Wala pang audit log na naitala.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Petsa/Oras</th>
            <th>User</th>
            <th>Aksyon</th>
            <th>Entity</th>
        </tr>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['created_at']) ?></td>
                <td><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                <td><?= htmlspecialchars($log['action']) ?></td>
                <td><?= htmlspecialchars($log['entity_type'] ?? '—') ?> #<?= (int)($log['entity_id'] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>