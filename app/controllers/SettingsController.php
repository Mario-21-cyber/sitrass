<?php

class SettingsController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function index() {
        $settingModel = new SystemSetting();
        $grouped = $settingModel->getAllGrouped();

        $saved = $_SESSION['settings_saved'] ?? false;
        unset($_SESSION['settings_saved']);

        View::render('admin-settings', [
            'pageTitle' => 'Mga Setting - SITRASS Admin',
                        'pageHeading' => t('settings_page_heading'),
            'grouped' => $grouped,
            'saved' => $saved,
        ]);
    }

    public function update() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $settingModel = new SystemSetting();
        $auditModel = new AuditLog();

        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $settingId = (int)str_replace('setting_', '', $key);
                $settingModel->update($settingId, $value, $_SESSION['user_id']);
            }
        }

        $auditModel->log($_SESSION['user_id'], 'settings.updated', 'system_settings', null);

        $_SESSION['settings_saved'] = true;
        header('Location: /sitrass/public/settings');
        exit;
    }
}