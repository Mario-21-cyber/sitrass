<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (empty($pending)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--border);margin-bottom:0.75rem;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <div>Walang naghihintay na account sa ngayon.</div>
        <div class="text-sm">Ang lahat ng bagong registration ay na-approve na.</div>
    </div>
<?php else: ?>
    <table>
        <tr>
            <th>Pangalan</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Ginawa noong</th>
            <th>Aksyon</th>
        </tr>
        <?php foreach ($pending as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($user['email']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($user['phone']) ?></td>
                <td>
                    <span class="badge <?= $user['role'] === 'driver' ? 'badge-info' : 'badge-neutral' ?>">
                        <?= htmlspecialchars($user['role']) ?>
                    </span>
                </td>
                <td class="text-muted" style="font-size:0.82rem;"><?= htmlspecialchars($user['created_at']) ?></td>
                <td>
                    <form method="POST" action="/sitrass/public/admin/approve" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.4rem 1rem; font-size:0.85rem;">
                            <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-right:2px;"><polyline points="20 6 9 17 4 12"/></svg>
                            Aprubahan
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>