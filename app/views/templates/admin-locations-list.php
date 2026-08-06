<?php require __DIR__ . '/_admin_header.php'; ?>

<p><a href="/sitrass/public/locations/create" class="btn" style="display:inline-block; width:auto; padding:0.6rem 1.2rem; text-decoration:none;">+ Magdagdag ng Lokasyon</a></p>

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
            <td><?= htmlspecialchars($loc['category']) ?></td>
            <td><?= htmlspecialchars($loc['latitude']) ?>, <?= htmlspecialchars($loc['longitude']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<?php require __DIR__ . '/_admin_footer.php'; ?>