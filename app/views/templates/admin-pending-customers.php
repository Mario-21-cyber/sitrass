<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (empty($pending)): ?>
    <p>Walang naghihintay na account sa ngayon.</p>
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
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
                <td><span class="badge badge-pending"><?= htmlspecialchars($user['role']) ?></span></td>
                <td><?= htmlspecialchars($user['created_at']) ?></td>
                <td>
                    <form method="POST" action="/sitrass/public/admin/approve" style="display:inline;">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
                        <button type="submit" class="btn" style="width:auto; padding:0.4rem 0.9rem; font-size:0.85rem;">Aprubahan</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>