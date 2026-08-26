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
        $allBookings = $bookingModel->getForDriver($this->driverRecord['driver_id']);
        // Itago ang mga booking hangga't hindi pa verified ng admin ang deposit -
        // hindi pa ito dapat lumitaw kung tatanggapin ng driver o hindi.
        $pendingBookings = array_values(array_filter($allBookings, function($b) {
            return $b['status'] === 'pending' && $b['reservation_status'] === 'confirmed';
        }));
        $activeBooking = $bookingModel->getActiveBookingForDriver($this->driverRecord['driver_id']);

        $message = $_SESSION['driver_message'] ?? null;
        $error = $_SESSION['driver_error'] ?? null;
        unset($_SESSION['driver_message'], $_SESSION['driver_error']);

        // Popup ng payment verification pagkatapos mag-end trip, kung may
        // pending F2F balance payment - ipapakita isang beses lang.
        $paymentToVerify = null;
        if (!empty($_SESSION['check_payment_reservation_id'])) {
            $paymentModel = new Payment();
            $paymentToVerify = $paymentModel->getPendingCashBalanceForReservation($_SESSION['check_payment_reservation_id']);
            unset($_SESSION['check_payment_reservation_id']);
        }

        View::render('driver-dashboard', [
            'pageTitle' => t('title_driver_dashboard'),
            'bookings' => $pendingBookings,
            'message' => $message,
            'error' => $error,
            'activeBooking' => $activeBooking,
            'driverIdForGps' => $this->driverRecord['driver_id'],
            'paymentToVerify' => $paymentToVerify,
        ]);
    }

    public function history() {
        $bookingModel = new Booking();
        $allBookings = $bookingModel->getForDriver($this->driverRecord['driver_id']);
        $completedBookings = array_values(array_filter($allBookings, function($b) {
            return in_array($b['status'], ['completed', 'cancelled', 'rejected']);
        }));

        View::render('driver-history', [
            'pageTitle' => t('history_page_title') . ' - SITRASS',
            'bookings' => $completedBookings,
        ]);
    }

        public function accept() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $bookingModel = new Booking();

        // Segurong-seguro: kahit direktang POST request, hindi puwedeng tanggapin
        // kung hindi pa verified ng admin ang deposit ng reservation na ito.
        $db = (new Model())->getConnection();
        $stmt = $db->prepare(
            "SELECT rs.status FROM bookings b
             JOIN reservations rs ON rs.reservation_id = b.reservation_id
             WHERE b.booking_id = ?"
        );
        $stmt->execute([$bookingId]);
        $reservationStatus = $stmt->fetchColumn();

        if ($reservationStatus !== 'confirmed') {
            $_SESSION['driver_error'] = 'Hindi pa verified ang deposit ng booking na ito.';
            header('Location: /sitrass/public/driver/dashboard');
            exit;
        }

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
        $booking = $bookingModel->getById($bookingId);

        if ($bookingModel->endTrip($bookingId, $this->driverRecord['driver_id'])) {
            $_SESSION['driver_message'] = 'Tapos na ang biyahe.';
            // Ipaalala natin sa driver na i-verify ang F2F balance payment, kung
            // meron - ipapakita bilang popup sa susunod na dashboard load.
            if ($booking) {
                $_SESSION['check_payment_reservation_id'] = $booking['reservation_id'];

                // Kapag tapos na ang lahat ng booking sa ilalim ng reservation na ito,
                // markahan din ang reservation bilang "completed" - dito na-aalis ang
                // buong reservation sa "Aking Mga Booking," lilipat na lang ito sa
                // "Kasaysayan ng Biyahe."
                $db = (new Model())->getConnection();
                $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE reservation_id = ? AND status != 'completed'");
                $stmt->execute([$booking['reservation_id']]);
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $db->prepare("UPDATE reservations SET status = 'completed' WHERE reservation_id = ?");
                    $stmt->execute([$booking['reservation_id']]);
                }
            }
        } else {
            $_SESSION['driver_error'] = 'Hindi na-process ang aksyon.';
        }

        header('Location: /sitrass/public/driver/dashboard');
        exit;
    }

    public function verifyBoarding() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $token = trim($_POST['token'] ?? '');

        $bookingModel = new Booking();
        $booking = $bookingModel->getById($bookingId);

        if (!$booking || $booking['driver_id'] != $this->driverRecord['driver_id'] || $booking['status'] !== 'accepted') {
            $_SESSION['driver_error'] = 'Hindi valid ang booking na ito.';
            header('Location: /sitrass/public/driver/dashboard');
            exit;
        }

        $qrModel = new QrBooking();
        $qr = $qrModel->getByBookingId($bookingId);

        if (!$qr || hash('sha256', $token) !== $qr['token_hash']) {
            $_SESSION['driver_error'] = 'Hindi tugma ang QR code sa bookingang ito.';
            header('Location: /sitrass/public/driver/dashboard');
            exit;
        }

        if ($qr['status'] === 'used') {
            $_SESSION['driver_error'] = 'Na-verify na ang pasaherong ito noon.';
            header('Location: /sitrass/public/driver/dashboard');
            exit;
        }

        $qrModel->markScanned($qr['qr_id'], $_SESSION['user_id']);
        $_SESSION['driver_message'] = 'Na-verify ang pasahero. Puwede nang simulan ang biyahe.';
        header('Location: /sitrass/public/driver/dashboard');
        exit;
    }
        public function scanQr() {
    $result = $_SESSION['scan_result'] ?? null;
    $pending = $_SESSION['scan_pending'] ?? null;
    unset($_SESSION['scan_result']);

    View::render('driver-scan', [
        'pageTitle' => t('nav_scan_qr') . ' - SITRASS',
        'result' => $result,
        'pending' => $pending,
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

        // Sa halip na agad markahan bilang "used," ipakita muna ang detalye ng
    // pasahero sa isang popup - markScanned() lang tatawagin kapag kinumpirma
    // na ng driver sa confirmBoarding().
    $_SESSION['scan_pending'] = [
        'qr_id' => $qr['qr_id'],
        'reference_code' => $qr['reference_code'],
        'customer_name' => $qr['customer_name'],
        'seats_booked' => (int)$qr['seats_booked'],
        'travel_date' => $qr['travel_date'],
        'pickup_time' => $qr['pickup_time'],
    ];
    header('Location: /sitrass/public/driver/scanQr');
    exit;
}

public function confirmBoarding() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $qrId = (int)($_POST['qr_id'] ?? 0);
    $pending = $_SESSION['scan_pending'] ?? null;
    unset($_SESSION['scan_pending']);

    if (!$pending || (int)$pending['qr_id'] !== $qrId) {
        $_SESSION['scan_result'] = ['success' => false, 'message' => 'Nag-expire na ang confirmation na ito. Subukan ulit i-scan.'];
        header('Location: /sitrass/public/driver/scanQr');
        exit;
    }

    $qrModel = new QrBooking();
    $qrModel->markScanned($qrId, $_SESSION['user_id']);

    $_SESSION['scan_result'] = [
        'success' => true,
        'message' => 'Verified! ' . $pending['customer_name'] . ' - ' . $pending['reference_code'] . ' (' . $pending['seats_booked'] . ' pasahero)',
    ];
    header('Location: /sitrass/public/driver/scanQr');
    exit;
}

public function cancelScan() {
    unset($_SESSION['scan_pending']);
    header('Location: /sitrass/public/driver/scanQr');
    exit;
}

public function payments() {
    $paymentModel = new Payment();
    $pending = $paymentModel->getPendingCashBalanceForDriver($this->driverRecord['driver_id']);

    View::render('driver-payments', [
        'pageTitle' => t('driver_payments_title') . ' - SITRASS',
        'payments' => $pending,
    ]);
}

public function verifyPayment() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $paymentModel = new Payment();
    $payment = $paymentModel->getById($paymentId);
    if (!$payment) {
        die('Payment not found.');
    }

    // I-verify na ang payment na ito ay talagang kabilang sa isang booking na naka-assign sa driver na ito
    $db = (new Model())->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE reservation_id = ? AND driver_id = ?");
    $stmt->execute([$payment['reservation_id'], $this->driverRecord['driver_id']]);
    if ($stmt->fetchColumn() == 0) {
        die('Wala kang access sa payment na ito.');
    }

    $paymentModel->verify($paymentId, $_SESSION['user_id']);

    $_SESSION['driver_message'] = 'Na-verify ang bayad.';
    header('Location: /sitrass/public/driver/payments');
    exit;
}

public function rejectPayment() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $paymentModel = new Payment();
    $payment = $paymentModel->getById($paymentId);
    if (!$payment) {
        die('Payment not found.');
    }

    $db = (new Model())->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE reservation_id = ? AND driver_id = ?");
    $stmt->execute([$payment['reservation_id'], $this->driverRecord['driver_id']]);
    if ($stmt->fetchColumn() == 0) {
        die('Wala kang access sa payment na ito.');
    }

    $paymentModel->reject($paymentId, 'Tinanggihan ng driver');

    $_SESSION['driver_message'] = 'Tinanggihan ang bayad.';
    header('Location: /sitrass/public/driver/payments');
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