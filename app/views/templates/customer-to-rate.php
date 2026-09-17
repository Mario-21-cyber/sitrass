<?php require __DIR__ . '/_customer_header.php'; ?>

<?php
// Isang tuloy-tuloy na listahan - WALANG mga heading. Ang pinakabagong
// nangyari (bagong rating o bagong tapos na biyahe) ay laging nasa taas.
// - rating: ayos ayon sa created_at (petsa ng pag-rate)
// - trip na naghihintay ng rating: ayos ayon sa trip_ended_at / travel_date
$rateItems = [];
foreach (($ratingHistory ?? []) as $r) {
    $rateItems[] = ['type' => 'rating', 'sort' => strtotime($r['created_at']), 'data' => $r];
}
foreach (($trips ?? []) as $t) {
    $ts = !empty($t['trip_ended_at']) ? strtotime($t['trip_ended_at'])
        : (!empty($t['travel_date']) ? strtotime($t['travel_date']) : 0);
    $rateItems[] = ['type' => 'trip', 'sort' => $ts, 'data' => $t];
}
foreach (($rentals ?? []) as $rn) {
    $ts = !empty($rn['end_date']) ? strtotime($rn['end_date']) : 0;
    $rateItems[] = ['type' => 'rental', 'sort' => $ts, 'data' => $rn];
}
usort($rateItems, function ($a, $b) {
    return $b['sort'] <=> $a['sort'];
});
?>

<?php if (empty($rateItems)): ?>
    <div class="empty-state"><?= t('empty_no_trip_to_rate') ?></div>
<?php else: ?>
    <?php foreach ($rateItems as $item): ?>
        <?php if ($item['type'] === 'rating'): ?>
            <?php $r = $item['data']; ?>
            <div class="card list-card">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($r['reference_code'] ?? '—') ?></span><br>
                    <?php if (!empty($r['rental_id'])): ?>
                        <?php // Rental rating - ipakita ang van info imbes na ruta ?>
                        <strong><?= htmlspecialchars($r['plate_number'] ?? '—') ?></strong><br>
                    <?php else: ?>
                        <strong><?= htmlspecialchars($r['pickup_name'] ?? '—') ?> &rarr; <?= htmlspecialchars($r['dropoff_name'] ?? '—') ?></strong><br>
                    <?php endif; ?>
                    <span style="font-size:0.85rem;">
                        <?= (int)$r['overall_rating'] ?>/5 &#11088;
                        <?php if (!empty($r['comment'])): ?>
                            &middot; &ldquo;<?= htmlspecialchars($r['comment']) ?>&rdquo;
                        <?php else: ?>
                            &middot; <?= t('rate_no_comment') ?>
                        <?php endif; ?>
                    </span><br>
                    <span style="font-size:0.8rem; color:var(--ocean);"><?= htmlspecialchars(date('F j, Y', strtotime($r['created_at']))) ?></span>
                </div>
                <?php if (!empty($r['rental_id'])): ?>
                    <a href="/sitrass/public/customer/rateRental/<?= (int)$r['rental_id'] ?>" class="btn" style="display:inline-block; width:auto; padding:0.5rem 1.2rem;"><?= t('btn_edit_rating') ?></a>
                <?php else: ?>
                    <a href="/sitrass/public/customer/rate/<?= (int)$r['booking_id'] ?>" class="btn" style="display:inline-block; width:auto; padding:0.5rem 1.2rem;"><?= t('btn_edit_rating') ?></a>
                <?php endif; ?>
            </div>
        <?php elseif ($item['type'] === 'rental'): ?>
            <?php $rn = $item['data']; ?>
            <div class="card list-card">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($rn['reference_code'] ?? '—') ?></span><br>
                    <strong><?= htmlspecialchars($rn['make'] . ' ' . $rn['model']) ?> (<?= htmlspecialchars($rn['plate_number']) ?>)</strong><br>
                    <span style="font-size:0.85rem;">
                        <?php if (!empty($rn['route_label'])): ?><?= htmlspecialchars($rn['route_label']) ?> &middot; <?php endif; ?>
                        <?= !empty($rn['end_date']) ? htmlspecialchars(date('F j, Y', strtotime($rn['end_date']))) : '—' ?>
                        <?php if (!empty($rn['driver_name'])): ?> &middot; <?= t('label_driver') ?>: <?= htmlspecialchars($rn['driver_name']) ?><?php endif; ?>
                    </span>
                </div>
                <a href="/sitrass/public/customer/rateRental/<?= (int)$rn['rental_id'] ?>" class="btn" style="display:inline-block; width:auto; padding:0.5rem 1.2rem;"><?= t('btn_rate') ?></a>
            </div>
        <?php else: ?>
            <?php $t = $item['data']; ?>
            <div class="card list-card">
                <div>
                    <span style="font-family:monospace; font-weight:700; color:var(--teal-dark);"><?= htmlspecialchars($t['reference_code']) ?></span><br>
                    <strong><?= htmlspecialchars($t['pickup_name']) ?> &rarr; <?= htmlspecialchars($t['dropoff_name']) ?></strong><br>
                    <span style="font-size:0.85rem;"><?= htmlspecialchars(date('F j, Y', strtotime($t['travel_date']))) ?> &middot; <?= htmlspecialchars($t['plate_number']) ?>
                        <?php if ($t['driver_name']): ?> &middot; <?= t('label_driver') ?>: <?= htmlspecialchars($t['driver_name']) ?><?php endif; ?>
                    </span>
                </div>
                <a href="/sitrass/public/customer/rate/<?= (int)$t['booking_id'] ?>" class="btn" style="display:inline-block; width:auto; padding:0.5rem 1.2rem;"><?= t('btn_rate') ?></a>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_customer_footer.php'; ?>
