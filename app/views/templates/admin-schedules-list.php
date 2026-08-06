<?php require __DIR__ . '/_admin_header.php'; ?>

<p><a href="/sitrass/public/schedules/create" class="btn" style="display:inline-block; width:auto; padding:0.6rem 1.2rem; text-decoration:none;">+ Magdagdag ng Schedule</a></p>

<?php if (empty($schedules)): ?>
    <p>Wala pang schedule na naitala.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Ruta</th>
            <th>Van</th>
            <th>Driver</th>
            <th>Petsa</th>
            <th>Oras</th>
            <th>Upuan</th>
            <th>Pamasahe</th>
            <th>Status</th>
            <th>Aksyon</th>
        </tr>
        <?php foreach ($schedules as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['route_code']) ?></td>
                <td><?= htmlspecialchars($s['plate_number']) ?></td>
                <td><?= htmlspecialchars($s['driver_name'] ?? 'Wala pang driver') ?></td>
                <td><?= htmlspecialchars($s['departure_date']) ?></td>
                <td><?= htmlspecialchars($s['departure_time']) ?></td>
                <td><?= (int)$s['available_seats'] ?> / <?= (int)$s['total_seats'] ?></td>
                <td>₱<?= htmlspecialchars($s['fare_per_seat']) ?></td>
                <td>
                    <span class="badge <?= $s['status'] === 'scheduled' ? 'badge-active' : 'badge-pending' ?>">
                        <?= htmlspecialchars($s['status']) ?>
                    </span>
                </td>
                <td>
                    <?php if ($s['status'] === 'scheduled'): ?>
                        <form method="POST" action="/sitrass/public/schedules/cancel" style="display:inline;" onsubmit="return confirm('Sigurado kang kanselahin ang schedule na ito?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="schedule_id" value="<?= (int)$s['schedule_id'] ?>">
                            <button type="submit" style="width:auto; padding:0.3rem 0.7rem; font-size:0.8rem; background:var(--danger); color:#fff; border:none; border-radius:4px; cursor:pointer;">Kanselahin</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>