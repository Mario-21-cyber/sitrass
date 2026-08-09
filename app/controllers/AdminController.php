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
    $reservationModel = new Reservation();
    $paymentModel = new Payment();
    $vanModel = new Van();
    $userModel = new User();

    View::render('admin-dashboard', [
        'pageTitle' => 'Dashboard - SITRASS Admin',
        'pageHeading' => 'Dashboard',
        'reservationStats' => $reservationModel->getStats(),
        'revenueStats' => $paymentModel->getRevenueStats(),
        'dailyRevenue' => $paymentModel->getDailyRevenue(7),
        'vanStats' => $vanModel->getStats(),
        'userStats' => $userModel->getStats(),
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
        $user = $userModel->getById($userId);
        $userModel->approve($userId);

        // Kung driver ang inaprubahan, i-update din ang drivers.is_approved
        if ($user && $user['role'] === 'driver') {
            $driverModel = new Driver();
            $driver = $driverModel->getByUserId($userId);
            if ($driver) {
                $driverModel->approve($driver['driver_id'], $_SESSION['user_id']);
            }
        }

        $auditModel = new AuditLog();
        $auditModel->log($_SESSION['user_id'], 'user.approved', 'user', $userId);
    }

    header('Location: /sitrass/public/admin/pending-customers');
    exit;
}
}