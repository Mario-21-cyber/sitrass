<?php require __DIR__ . '/_admin_header.php'; ?>

<p><a href="/sitrass/public/routes/create" class="btn" style="display:inline-block; width:auto; padding:0.6rem 1.2rem; text-decoration:none;">+ Magdagdag ng Ruta</a></p>

<table>
    <tr>
        <th>Code</th>
        <th>Pangalan</th>
        <th>Mula</th>
        <th>Papunta</th>
        <th>Distansya</th>
        <th>Tantiyang Oras</th>
    </tr>
    <?php foreach ($routes as $route): ?>
        <tr>
            <td><?= htmlspecialchars($route['route_code']) ?></td>
            <td><?= htmlspecialchars($route['route_name']) ?></td>
            <td><?= htmlspecialchars($route['origin_name']) ?></td>
            <td><?= htmlspecialchars($route['destination_name']) ?></td>
            <td><?= htmlspecialchars($route['distance_km']) ?> km</td>
            <td><?= htmlspecialchars($route['estimated_duration_minutes']) ?> min</td>
        </tr>
    <?php endforeach; ?>
</table>

<?php require __DIR__ . '/_admin_footer.php'; ?>