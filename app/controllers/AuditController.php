<?php

class AuditController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function index() {
        $auditModel = new AuditLog();
        $logs = $auditModel->getRecent(100);

        View::render('admin-audit-logs', [
            'pageTitle' => 'Audit Logs - SITRASS Admin',
            'pageHeading' => 'Audit Logs',
            'logs' => $logs,
        ]);
    }
}