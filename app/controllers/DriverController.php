<?php

class DriverController extends Controller {

    protected $driverRecord;

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }

        $driverModel = new Driver();
        $this->driverRecord = $driverModel->getByUserId($_SESSION['user_id']);

        if (!$this->driverRecord || !$this->driverRecord['is_approved']) {
            die('Hindi pa aprubado ang driver account mo. <a href="/sitrass/public/auth/logout">Logout</a>');
        }
    }

    public function dashboard() {
        $bookingModel = new Booking();
        $bookings = $bookingModel->getForDriver($this->driverRecord['driver_id']);

        // Hanapin kung may aktibong "en_route" na booking - doon lang natin ipapadala ang GPS
        $activeEnRouteBookingId = null;
        foreach ($bookings as $b) {
            if ($b['status'] === 'en_route') {
                $activeEnRouteBookingId = $b['booking_id'];
                break;
            }
        }

        $message = $_SESSION['driver_message'] ?? null;
        $error = $_SESSION['driver_error'] ?? null;
        unset($_SESSION['driver_message'], $_SESSION['driver_error']);

        View::render('driver-dashboard', [
            'pageTitle' => 'Driver Dashboard - SITRASS',
            'bookings' => $bookings,
            'message' => $message,
            'error' => $error,
            'activeEnRouteBookingId' => $activeEnRouteBookingId,
            'driverIdForGps' => $this->driverRecord['driver_id'],
        ]);
    }

    public function accept() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $bookingModel = new Booking();

        if ($bookingModel->accept($bookingId, $this->driverRecord['driver_id'])) {
            $_SESSION['driver_message'] = 'Tinanggap ang booking.';
        } else {
            $_SESSION['driver_error'] = 'Hindi na-process ang aksyon. Baka nabago na ang status.';
        }

        header('Location: /sitrass/public/driver/dashboard');
        exit;
    }

    public function reject() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Hindi tinukoy');
        $bookingModel = new Booking();

        if ($bookingModel->reject($bookingId, $this->driverRecord['driver_id'], $reason)) {
            $_SESSION['driver_message'] = 'Tinanggihan ang booking.';
        } else {
            $_SESSION['driver_error'] = 'Hindi na-process ang aksyon. Baka nabago na ang status.';
        }

        header('Location: /sitrass/public/driver/dashboard');
        exit;
    }

    public function startTrip() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $bookingModel = new Booking();

        if ($bookingModel->startTrip($bookingId, $this->driverRecord['driver_id'])) {
            $_SESSION['driver_message'] = 'Nasimulan na ang biyahe.';
        } else {
            $_SESSION['driver_error'] = 'Hindi na-process ang aksyon.';
        }

        header('Location: /sitrass/public/driver/dashboard');
        exit;
    }

    public function endTrip() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $bookingModel = new Booking();

        if ($bookingModel->endTrip($bookingId, $this->driverRecord['driver_id'])) {
            $_SESSION['driver_message'] = 'Tapos na ang biyahe.';
        } else {
            $_SESSION['driver_error'] = 'Hindi na-process ang aksyon.';
        }

        header('Location: /sitrass/public/driver/dashboard');
        exit;
    }
    public function scanQr() {
    $result = $_SESSION['scan_result'] ?? null;
    unset($_SESSION['scan_result']);

    View::render('driver-scan', [
        'pageTitle' => 'I-verify ang QR - SITRASS',
        'result' => $result,
    ]);
}

public function verifyQr() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $token = trim($_POST['token'] ?? '');

    $qrModel = new QrBooking();
    $qr = $qrModel->getByToken($token);

    if (!$qr) {
        $_SESSION['scan_result'] = ['success' => false, 'message' => 'Hindi valid ang QR code na ito.'];
        header('Location: /sitrass/public/driver/scanQr');
        exit;
    }

    if ($qr['driver_id'] != $this->driverRecord['driver_id']) {
        $_SESSION['scan_result'] = ['success' => false, 'message' => 'Hindi sa iyo naka-assign ang bookingang ito.'];
        header('Location: /sitrass/public/driver/scanQr');
        exit;
    }

    if ($qr['status'] === 'used') {
        $_SESSION['scan_result'] = ['success' => false, 'message' => 'Nagamit na ang QR code na ito noon. (Scan count: ' . ((int)$qr['scan_count'] + 1) . ')'];
        header('Location: /sitrass/public/driver/scanQr');
        exit;
    }

    if (strtotime($qr['expires_at']) < time()) {
        $_SESSION['scan_result'] = ['success' => false, 'message' => 'Expired na ang QR code na ito.'];
        header('Location: /sitrass/public/driver/scanQr');
        exit;
    }

    $qrModel->markScanned($qr['qr_id'], $_SESSION['user_id']);

    $_SESSION['scan_result'] = [
        'success' => true,
        'message' => 'Verified! ' . $qr['customer_name'] . ' - ' . $qr['reference_code'] . ' (' . (int)$qr['seats_booked'] . ' pasahero)',
    ];
    header('Location: /sitrass/public/driver/scanQr');
    exit;
}
public function trackTrip($bookingId) {
    $bookingId = (int)$bookingId;
    $bookingModel = new Booking();
    $booking = $bookingModel->getForDriverTrackView($bookingId, $this->driverRecord['driver_id']);

    if (!$booking) {
        die('Booking not found.');
    }

    if ($booking['status'] !== 'en_route') {
        die('Hindi pa nagsisimula ang biyaheng ito, o tapos na. <a href="/sitrass/public/driver/dashboard">Bumalik</a>');
    }

    View::render('driver-track', [
        'pageTitle' => 'Subaybayan ang Customer - SITRASS',
        'booking' => $booking,
        'driverId' => $this->driverRecord['driver_id'],
    ]);
}
}