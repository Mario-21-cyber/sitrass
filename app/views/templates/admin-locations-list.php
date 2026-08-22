<?php require __DIR__ . '/_admin_header.php'; ?>

<div class="section-heading">
    <h3 style="margin:0;">Mga Lokasyon</h3>
    <a href="/sitrass/public/locations/create" class="btn" style="width:auto; padding:0.6rem 1.2rem;">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Magdagdag ng Lokasyon
    </a>
</div>

<?php if (empty($locations)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--border);margin-bottom:0.75rem;"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
        <div>Wala pang lokasyon na naitala.</div>
    </div>
<?php else: ?>
    <table>
        <tr>
            <th>Pangalan</th>
            <th>Munisipyo</th>
            <th>Kategorya</th>
            <th>Koordinada</th>
        </tr>
        <?php foreach ($locations as $loc): ?>
            <tr>
                <td><?= htmlspecialchars($loc['name']) ?></td>
                <td><?= htmlspecialchars($loc['municipality']) ?></td>
                                <td><span class="badge badge-neutral"><?= t('category_' . $loc['category']) ?></span></td>
                <td class="text-muted" style="font-family:'SF Mono', monospace; font-size:0.8rem;"><?= htmlspecialchars($loc['latitude']) ?>, <?= htmlspecialchars($loc['longitude']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>