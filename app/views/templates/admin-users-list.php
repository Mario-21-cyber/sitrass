<?php require __DIR__ . '/_admin_header.php'; ?>

<div class="filter-toolbar">
    <a href="/sitrass/public/admin/users" class="<?= !$roleFilter ? 'btn' : 'btn-ghost' ?>" style="width:auto; text-decoration:none; padding:0.5rem 1rem;"><?= t('filter_all_roles') ?></a>
    <a href="/sitrass/public/admin/users?role=customer" class="<?= $roleFilter === 'customer' ? 'btn' : 'btn-ghost' ?>" style="width:auto; text-decoration:none; padding:0.5rem 1rem;"><?= t('filter_customers') ?></a>
    <a href="/sitrass/public/admin/users?role=driver" class="<?= $roleFilter === 'driver' ? 'btn' : 'btn-ghost' ?>" style="width:auto; text-decoration:none; padding:0.5rem 1rem;"><?= t('filter_drivers') ?></a>
    <a href="/sitrass/public/admin/users?role=admin" class="<?= $roleFilter === 'admin' ? 'btn' : 'btn-ghost' ?>" style="width:auto; text-decoration:none; padding:0.5rem 1rem;"><?= t('filter_admins') ?></a>
</div>

<?php if (empty($users)): ?>
    <div class="empty-state"><?= t('pending_empty') ?></div>
<?php else: ?>
    <table>
        <tr>
            <th><?= t('th_name') ?></th>
            <th><?= t('th_email') ?></th>
            <th><?= t('th_phone') ?></th>
            <th><?= t('th_role') ?></th>
            <th><?= t('th_status') ?></th>
            <th><?= t('th_action') ?></th>
        </tr>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($u['phone']) ?></td>
                <td><span class="badge badge-neutral"><?= htmlspecialchars($u['role']) ?></span></td>
                <td>
                    <?php if (!empty($u['deleted_at'])): ?>
                        <span class="badge badge-danger"><?= t('badge_deactivated') ?></span>
                    <?php else: ?>
                        <span class="badge <?= $u['status'] === 'active' ? 'badge-active' : 'badge-pending' ?>"><?= t('status_' . $u['status']) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($u['user_id'] == $_SESSION['user_id']): ?>
                        <span class="text-muted text-sm">—</span>
                    <?php elseif (!empty($u['deleted_at'])): ?>
                        <form method="POST" action="/sitrass/public/admin/reactivateUser" style="display:inline;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                            <button type="submit" class="btn" style="width:auto; padding:0.35rem 0.85rem; font-size:0.82rem;"><?= t('btn_reactivate') ?></button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/sitrass/public/admin/deactivateUser" style="display:inline;" onsubmit="return confirm(<?= json_encode(t('confirm_deactivate')) ?>);">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                            <button type="submit" class="btn-danger" style="width:auto; padding:0.35rem 0.85rem; font-size:0.82rem; border:none; border-radius:6px; cursor:pointer;"><?= t('btn_deactivate') ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>