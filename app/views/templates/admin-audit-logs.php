<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (empty($logs)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--border);margin-bottom:0.75rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 15h6M9 11h2"/></svg>
        <div>Wala pang audit log na naitala.</div>
    </div>
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
                <td class="text-muted" style="font-size:0.82rem;"><?= htmlspecialchars($log['created_at']) ?></td>
                <td><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                <td><span class="badge badge-info"><?= htmlspecialchars($log['action']) ?></span></td>
                <td class="text-muted"><?= htmlspecialchars($log['entity_type'] ?? '—') ?> #<?= (int)($log['entity_id'] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>