<?php require __DIR__ . '/_admin_header.php'; ?>

<div class="section-heading">
    <h3 style="margin:0;">Mga Schedule ng Biyahe</h3>
    <a href="/sitrass/public/schedules/create" class="btn" style="width:auto; padding:0.6rem 1.2rem;">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Magdagdag ng Schedule
    </a>
</div>

<?php if (empty($schedules)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--border);margin-bottom:0.75rem;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        <div>Wala pang schedule na naitala.</div>
        <div class="text-sm">Magdagdag ng schedule para makapag-book na ang customer.</div>
    </div>
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
                <td><span class="badge badge-neutral"><?= htmlspecialchars($s['route_code']) ?></span></td>
                <td style="font-family:'SF Mono', monospace;"><?= htmlspecialchars($s['plate_number']) ?></td>
                <td><?= htmlspecialchars($s['driver_name'] ?? 'Wala pang driver') ?></td>
                <td><?= htmlspecialchars($s['departure_date']) ?></td>
                <td><?= htmlspecialchars($s['departure_time']) ?></td>
                <td><?= (int)$s['available_seats'] ?> / <?= (int)$s['total_seats'] ?></td>
                <td style="font-weight:600;">₱<?= htmlspecialchars($s['fare_per_seat']) ?></td>
                                <td>
                    <span class="badge <?= $s['status'] === 'scheduled' ? 'badge-active' : 'badge-pending' ?>">
                        <?= t('status_' . $s['status']) ?>
                    </span>
                </td>
                <td>
                    <?php if ($s['status'] === 'scheduled'): ?>
                        <form method="POST" action="/sitrass/public/schedules/cancel" style="display:inline;" onsubmit="return confirm('Sigurado kang kanselahin ang schedule na ito?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="schedule_id" value="<?= (int)$s['schedule_id'] ?>">
                            <button type="submit" class="btn-danger" style="width:auto; padding:0.35rem 0.85rem; font-size:0.82rem; border:none; border-radius:6px; cursor:pointer;">Kanselahin</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>