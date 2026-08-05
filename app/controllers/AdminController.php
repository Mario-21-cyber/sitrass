<?php

class AdminController extends Controller {

    public function __construct() {
        // Protektahan ang lahat ng admin pages - kailangan naka-login bilang admin
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function dashboard() {
    View::render('admin-dashboard', [
        'pageTitle' => 'Dashboard - SITRASS Admin',
        'pageHeading' => 'Dashboard',
    ]);
}
    public function pendingCustomers() {
    $userModel = new User();
    $pending = $userModel->getPending();

    View::render('admin-pending-customers', [
        'pageTitle' => 'Pending Accounts - SITRASS Admin',
        'pageHeading' => 'Mga Naghihintay na Account',
        'pending' => $pending,
    ]);
}

public function approve() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session. <a href="/sitrass/public/admin/pending-customers">Bumalik</a>');
    }

    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        $userModel = new User();
        $userModel->approve($userId);
    }

    header('Location: /sitrass/public/admin/pending-customers');
    exit;
}
}