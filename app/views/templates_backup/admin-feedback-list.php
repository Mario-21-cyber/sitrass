<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (empty($items)): ?>
    <p>Wala pang feedback na naitala.</p>
<?php else: ?>
    <?php foreach ($items as $f): ?>
        <div style="background:var(--white); border:1px solid var(--border); border-radius:8px; padding:1.25rem; margin-bottom:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:start;">
                <div>
                    <span class="badge badge-pending" style="text-transform:capitalize;"><?= htmlspecialchars($f['category']) ?></span>
                    <strong style="margin-left:0.5rem;"><?= htmlspecialchars($f['subject']) ?></strong><br>
                    <span style="font-size:0.85rem;">Galing kay: <?= htmlspecialchars($f['user_name'] ?? 'Anonymous') ?> &middot; <?= htmlspecialchars($f['created_at']) ?></span>
                </div>
                <span class="badge <?= $f['status'] === 'resolved' ? 'badge-active' : 'badge-pending' ?>"><?= htmlspecialchars($f['status']) ?></span>
            </div>

            <p style="margin:0.75rem 0;"><?= nl2br(htmlspecialchars($f['message'])) ?></p>

            <?php if ($f['response']): ?>
                <div style="background:var(--sand); border-radius:6px; padding:0.75rem; font-size:0.9rem;">
                    <strong>Sagot ng Admin:</strong> <?= htmlspecialchars($f['response']) ?>
                </div>
            <?php else: ?>
                <div style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid var(--border);">
                    <?php if ($f['status'] === 'new'): ?>
                        <form method="POST" action="/sitrass/public/feedback/markInReview" style="display:inline; margin-right:0.5rem;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="feedback_id" value="<?= (int)$f['feedback_id'] ?>">
                            <button type="submit" style="width:auto; padding:0.3rem 0.7rem; font-size:0.8rem;">I-mark bilang In Review</button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" action="/sitrass/public/feedback/respond">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="feedback_id" value="<?= (int)$f['feedback_id'] ?>">
                        <div class="field" style="margin-top:0.5rem;">
                            <input type="text" name="response" placeholder="Isulat ang sagot..." required style="max-width:400px;">
                        </div>
                        <button type="submit" class="btn" style="width:auto; padding:0.4rem 1rem; font-size:0.85rem;">Isumite ang Sagot</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/_admin_footer.php'; ?>