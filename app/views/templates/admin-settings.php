<?php require __DIR__ . '/_admin_header.php'; ?>

<?php
// Bilingual override para sa 35 settings descriptions (naka-seed sa English sa database).
// Kung walang tumugma dito, babalik sa description na galing sa DB (fallback).
$settingDescOverrides = [
    'tl' => [
        'site_name' => 'Pangalan ng Application',
        'site_tagline' => 'Slogan na makikita sa landing page',
        'contact_email' => 'Publikong email para sa suporta',
        'contact_phone' => 'Publikong hotline para sa suporta',
        'timezone' => 'Timezone ng application',
        'currency_code' => 'ISO currency code',
        'deposit_percentage' => 'Kinakailangang deposit sa reservation, porsyento ng kabuuan',
        'deposit_hold_minutes' => 'Bilang ng minuto bago mag-expire ang hindi pa bayad na reservation',
        'balance_due_hours_before' => 'Bilang ng oras bago ang biyahe kung kailan dapat bayaran ang natitirang balance',
        'refund_cutoff_hours' => 'Kanselahin ng hindi bababa sa ganitong bilang ng oras bago ang biyahe para maka-refund ng deposit',
        'refund_percentage' => 'Porsyento ng deposit na maibabalik sa loob ng cutoff window',
        'booking_min_lead_hours' => 'Minimum na oras sa pagitan ng oras ng booking at oras ng biyahe',
        'booking_max_advance_days' => 'Gaano kalayo ang puwedeng i-book ng customer',
        'max_passengers_per_booking' => 'Pinakamataas na bilang ng pasahero sa isang reservation',
        'cancellation_cutoff_hours' => 'Huling puwedeng kanselahin ng customer nang mag-isa',
        'reschedule_cutoff_hours' => 'Huling puwedeng mag-reschedule ang customer nang mag-isa',
        'no_show_grace_minutes' => 'Oras ng paghihintay bago markahan ang pasahero na "no-show"',
        'qr_validity_hours' => 'Bilang ng oras na balido ang QR code ng booking',
        'gps_ping_interval_seconds' => 'Gaano kadalas nagpapadala ng GPS location ang driver app',
        'gps_stale_after_seconds' => 'Markahan ang marker na "stale" pagkatapos ng ganitong bilang ng segundo',
        'gps_history_retention_days' => 'Bilang ng araw ng GPS history na itinatago bago burahin',
        'map_default_lat' => 'Default na latitude ng sentro ng mapa ng Sibuyan Island',
        'map_default_lng' => 'Default na longitude ng sentro ng mapa ng Sibuyan Island',
        'map_default_zoom' => 'Paunang zoom level ng Leaflet map',
        'map_tile_provider' => 'Pinagmumulan ng map tiles: openstreetmap o google',
        'max_upload_size_mb' => 'Pinakamataas na sukat ng upload bago i-compress',
        'image_compression_quality' => 'JPEG quality na ginagamit kapag ni-compress ang mga upload',
        'allowed_image_types' => 'Mga pahintulutang image extension',
        'login_max_attempts' => 'Bilang ng maling login bago ma-lock ang account',
        'login_lockout_minutes' => 'Tagal ng lockout pagkatapos lumampas sa max attempts',
        'session_lifetime_minutes' => 'Tagal bago mag-expire ang idle session',
        'password_min_length' => 'Pinakamaikling haba ng password',
        'sms_enabled' => 'I-on/off ang SMS notifications (Semaphore)',
        'email_enabled' => 'I-on/off ang email notifications (PHPMailer)',
        'maintenance_mode' => 'Ilagay offline ang site para sa mga hindi admin',
    ],
    'en' => [
        // Ito ang mga orihinal na English description mula sa database seed.
    ],
];

$groupLabelKeys = [
    'general' => 'settings_group_general',
    'payment' => 'settings_group_payment',
    'booking' => 'settings_group_booking',
    'security' => 'settings_group_security',
    'notifications' => 'settings_group_notifications',
    'map' => 'settings_group_map',
    'uploads' => 'settings_group_uploads',
    'qr' => 'settings_group_qr',
    'tracking' => 'settings_group_tracking',
];

function settingLabel($setting, $overrides) {
    $lang = Lang::current();
    if ($lang === 'tl' && isset($overrides['tl'][$setting['setting_key']])) {
        return $overrides['tl'][$setting['setting_key']];
    }
    // Sa English mode, o kung walang override, gamitin ang orihinal na DB description.
    return $setting['description'] ?: $setting['setting_key'];
}
?>

<?php if (!empty($saved)): ?>
    <div class="alert alert-success">Na-save na ang mga setting.</div>
<?php endif; ?>

<form method="POST" action="/sitrass/public/settings/update">
    <?= Csrf::field() ?>

    <?php foreach ($grouped as $groupName => $settings): ?>
        <div class="form-section">
            <div class="form-section-title"><?= isset($groupLabelKeys[$groupName]) ? t($groupLabelKeys[$groupName]) : htmlspecialchars($groupName) ?></div>
            <div class="card">
                <?php foreach ($settings as $s): ?>
                    <div class="field">
                        <label for="setting_<?= (int)$s['setting_id'] ?>"><?= htmlspecialchars(settingLabel($s, $settingDescOverrides)) ?></label>
                        <?php if ($s['data_type'] === 'boolean'): ?>
                            <select id="setting_<?= (int)$s['setting_id'] ?>" name="setting_<?= (int)$s['setting_id'] ?>">
                                <option value="1" <?= $s['setting_value'] == '1' ? 'selected' : '' ?>>Oo</option>
                                <option value="0" <?= $s['setting_value'] == '0' ? 'selected' : '' ?>>Hindi</option>
                            </select>
                        <?php else: ?>
                            <input type="text" id="setting_<?= (int)$s['setting_id'] ?>" name="setting_<?= (int)$s['setting_id'] ?>" value="<?= htmlspecialchars($s['setting_value']) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn" style="width:auto; padding:0.75rem 2rem;">I-save Lahat ng Setting</button>
</form>

<?php require __DIR__ . '/_admin_footer.php'; ?>