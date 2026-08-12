<?php require __DIR__ . '/_admin_header.php'; ?>

<?php if (empty($items)): ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--border);margin-bottom:0.75rem;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <div>Wala pang feedback na naitala.</div>
    </div>
<?php else: ?>
    <?php foreach ($items as $f): ?>
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:start; flex-wrap:wrap; gap:0.5rem;">
                <div>
                    <span class="badge badge-neutral" style="text-transform:capitalize;"><?= htmlspecialchars($f['category']) ?></span>
                    <strong style="margin-left:0.5rem;"><?= htmlspecialchars($f['subject']) ?></strong><br>
                    <span class="text-sm text-muted">Galing kay: <?= htmlspecialchars($f['user_name'] ?? 'Anonymous') ?> &middot; <?= htmlspecialchars($f['created_at']) ?></span>
                </div>
                <span class="badge <?= $f['status'] === 'resolved' ? 'badge-active' : 'badge-pending' ?>"><?= htmlspecialchars($f['status']) ?></span>
            </div>

            <p style="margin:0.75rem 0;"><?= nl2br(htmlspecialchars($f['message'])) ?></p>

            <?php if ($f['response']): ?>
                <div style="background:var(--slate-bg); border-radius:8px; padding:0.75rem; font-size:0.9rem;">
                    <strong>Sagot ng Admin:</strong> <?= htmlspecialchars($f['response']) ?>
                </div>
            <?php else: ?>
                <div class="actions">
                    <?php if ($f['status'] === 'new'): ?>
                        <form method="POST" action="/sitrass/public/feedback/markInReview" style="display:inline; margin-right:0.5rem;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="feedback_id" value="<?= (int)$f['feedback_id'] ?>">
                            <button type="submit" class="btn-ghost">I-mark bilang In Review</button>
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