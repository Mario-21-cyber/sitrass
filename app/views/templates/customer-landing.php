<?php require __DIR__ . '/_customer_header.php'; ?>

<p class="text-muted" style="margin-bottom:var(--space-6);"><?= sprintf(t('welcome_message'), htmlspecialchars($_SESSION['full_name'])) ?></p>

<div class="stat-grid">

    <a href="/sitrass/public/customer/search" class="stat-card stat-primary stat-featured" style="text-decoration:none; display:flex;">
        <div class="stat-top-row">
            <div class="stat-label"><?= t('nav_search') ?></div>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
        </div>
        <div class="text-sm" style="color:var(--ink);"><?= t('card_search_desc') ?></div>
    </a>

    <a href="/sitrass/public/customer/myBookings" class="stat-card stat-info" style="text-decoration:none; display:flex;">
        <div class="stat-top-row">
            <div class="stat-label"><?= t('nav_my_bookings') ?></div>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
        </div>
        <div class="text-sm" style="color:var(--ink);"><?= t('card_bookings_desc') ?></div>
    </a>

    <a href="/sitrass/public/customer/toRate" class="stat-card stat-warning" style="text-decoration:none; display:flex;">
        <div class="stat-top-row">
            <div class="stat-label"><?= t('nav_rate_trip') ?></div>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
        </div>
        <div class="text-sm" style="color:var(--ink);"><?= t('card_rate_desc') ?></div>
    </a>

    <a href="/sitrass/public/customer/feedback" class="stat-card stat-success" style="text-decoration:none; display:flex;">
        <div class="stat-top-row">
            <div class="stat-label"><?= t('nav_give_feedback') ?></div>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
        </div>
        <div class="text-sm" style="color:var(--ink);"><?= t('card_feedback_desc') ?></div>
    </a>

</div>

<?php require __DIR__ . '/_customer_footer.php'; ?>