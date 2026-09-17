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

        // Katulad nito para sa RENTAL: pagkatapos i-click ang End Rental,
        // may popup kung may pending na F2F balance payment ang rental.
        $rentalPaymentToVerify = null;
        if (!empty($_SESSION['check_payment_rental_id'])) {
            $paymentModel = new Payment();
            $rentalPaymentToVerify = $paymentModel->getPendingCashBalanceForRental($_SESSION['check_payment_rental_id']);
            unset($_SESSION['check_payment_rental_id']);
        }

        // Persistent list ng lahat ng pending F2F balance payments para sa
        // mga rental ng driver na ito - para makita ng driver kahit hindi
        // agad pagkatapos ng endRental (katulad ng shared booking payments page).
        $paymentModel2 = new Payment();
        $rentalPaymentsToVerify = $paymentModel2->getPendingCashBalanceForRentalForDriver($this->driverRecord['driver_id']);

                View::render('driver-dashboard', [
            'pageTitle' => t('nav_dashboard'),
            'bookings' => $pendingBookings,
            'message' => $message,
            'error' => $error,
            'activeBooking' => $activeBooking,
            'driverIdForGps' => $this->driverRecord['driver_id'],
            'paymentToVerify' => $paymentToVerify,
            'boardingPending' => $_SESSION['boarding_pending'] ?? null,
            'rentalPickupPending' => $_SESSION['rental_pickup_pending'] ?? null,
            'rentalPickups' => (new VanRental())->getPickupsForDriver($this->driverRecord['driver_id']),
            'activeRental' => (new VanRental())->getActiveForDriver($this->driverRecord['driver_id']),
            'rentalPaymentToVerify' => $rentalPaymentToVerify,
            'rentalPaymentsToVerify' => $rentalPaymentsToVerify,
        ]);
    }

    public function history() {
        $bookingModel = new Booking();
        $allBookings = $bookingModel->getForDriver($this->driverRecord['driver_id']);
        $completedBookings = array_values(array_filter($allBookings, function($b) {
            return in_array($b['status'], ['completed', 'cancelled', 'rejected']);
        }));

        // Kasama rin sa kasaysayan ang mga rental ng mga van mo - completed,
        // cancelled, o lumipas na ang rental period.
        $rentals = (new VanRental())->getHistoryForDriver($this->driverRecord['driver_id']);

        View::render('driver-history', [
            'pageTitle' => t('history_page_title') . ' - SITRASS',
            'bookings' => $completedBookings,
            'rentals' => $rentals,
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

                // Linisin ang live tracking sa Firebase - dapat mawala na ang
                // mga GPS marker sa mapa ng customer at driver kapag tapos na.
                $this->clearFirebaseLocation('driver_locations/' . $this->driverRecord['driver_id']);
                $this->clearFirebaseLocation('customer_locations/' . $bookingId);
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

        // Hindi pa natin agad ma-mamarkahan ang QR bilang "used" - kailangan
        // munang makita ng driver ang detalye ng pasahero (ilan sila, bayad na
        // ba) at kumpirmahin muna, bago talaga tuluyang i-verify.
        $db = (new Model())->getConnection();
        $stmt = $db->prepare(
            "SELECT rs.reference_code, rs.payment_status, rs.total_amount, rs.amount_paid,
                    (rs.total_amount - rs.amount_paid) AS balance_due,
                    CONCAT(u.first_name,' ',u.last_name) AS customer_name
             FROM reservations rs
             JOIN customers c ON c.customer_id = rs.customer_id
             JOIN users u ON u.user_id = c.user_id
             WHERE rs.reservation_id = ?"
        );
        $stmt->execute([$booking['reservation_id']]);
        $resInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        $_SESSION['boarding_pending'] = [
            'qr_id' => $qr['qr_id'],
            'booking_id' => $bookingId,
            'reference_code' => $resInfo['reference_code'] ?? '',
            'customer_name' => $resInfo['customer_name'] ?? '',
            'seats_booked' => (int)$booking['seats_booked'],
            'payment_status' => $resInfo['payment_status'] ?? 'pending',
            'balance_due' => $resInfo['balance_due'] ?? 0,
        ];

        header('Location: /sitrass/public/driver/dashboard');
        exit;
    }

    public function verifyBoardingConfirm() {
        if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
            die('Invalid na session.');
        }

        $pending = $_SESSION['boarding_pending'] ?? null;
        $qrId = (int)($_POST['qr_id'] ?? 0);
        unset($_SESSION['boarding_pending']);

        if (!$pending || (int)$pending['qr_id'] !== $qrId) {
            $_SESSION['driver_error'] = 'Nag-expire na ang confirmation na ito. Subukan ulit i-verify.';
            header('Location: /sitrass/public/driver/dashboard');
            exit;
        }

        $qrModel = new QrBooking();
        $qrModel->markScanned($qrId, $_SESSION['user_id']);

        $_SESSION['driver_message'] = 'Na-verify ang pasahero. Puwede nang simulan ang biyahe.';
        header('Location: /sitrass/public/driver/dashboard');
        exit;
    }

    public function verifyBoardingCancel() {
        unset($_SESSION['boarding_pending']);
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

    // RENTAL payment? I-verify na ang van ng rental ay sa driver na ito,
    // tapos i-verify ang bayad (rental-safe ang Payment::verify) at
    // i-update ang payment_status ng rental.
    if (!empty($payment['rental_id'])) {
        $rentalModel = new VanRental();
        $rental = $rentalModel->getById((int)$payment['rental_id']);
        if (!$rental || $rental['driver_id'] != $this->driverRecord['driver_id']) {
            die('Wala kang access sa payment na ito.');
        }

        $paymentModel->verify($paymentId, $_SESSION['user_id']);
        $rentalModel->markPaid((int)$payment['rental_id']);

        $_SESSION['driver_message'] = 'Na-verify ang bayad.';
        header('Location: /sitrass/public/driver/dashboard');
        exit;
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

    // RENTAL payment? I-verify na ang van ng rental ay sa driver na ito.
    if (!empty($payment['rental_id'])) {
        $rentalModel = new VanRental();
        $rental = $rentalModel->getById((int)$payment['rental_id']);
        if (!$rental || $rental['driver_id'] != $this->driverRecord['driver_id']) {
            die('Wala kang access sa payment na ito.');
        }

        $paymentModel->reject($paymentId, 'Tinanggihan ng driver');
        $_SESSION['driver_message'] = 'Tinanggihan ang bayad.';
        header('Location: /sitrass/public/driver/dashboard');
        exit;
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

// Sinusuri ng tracking pages kada ~20s kung tapos na ang biyahe - kapag
// hindi na en_route, maglilinis ang pahina at mawawala ang mga marker.
public function tripStatus($bookingId) {
    $bookingModel = new Booking();
    $booking = $bookingModel->getById((int)$bookingId);

    header('Content-Type: application/json');
    if (!$booking || $booking['driver_id'] != $this->driverRecord['driver_id']) {
        echo json_encode(['status' => null]);
        exit;
    }
    echo json_encode(['status' => $booking['status']]);
    exit;
}

// =====================================================================
// MGA VAN KO (Driver) - pag-register at pag-manage ng sariling vans.
// Bagong van = pending muna hanggang i-approve ng admin (Pending Vans).
// =====================================================================
public function myVans() {
    $vanModel = new Van();
    $vans = $vanModel->getByDriver($this->driverRecord['driver_id']);

    $errors = $_SESSION['myvan_errors'] ?? [];
    $success = $_SESSION['myvan_success'] ?? null;
    $old = $_SESSION['myvan_old'] ?? [];
    unset($_SESSION['myvan_errors'], $_SESSION['myvan_success'], $_SESSION['myvan_old']);

    View::render('driver-my-vans', [
        'pageTitle' => t('nav_my_vans') . ' - SITRASS Driver',
        'vans' => $vans,
        'errors' => $errors,
        'success' => $success,
        'old' => $old,
    ]);
}

public function storeMyVan() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $validator = new Validator($_POST);
    $validator->required('plate_number', 'Plate number')
        ->required('make', 'Make')
        ->required('model', 'Model')
        ->required('van_type', 'Van type')
        ->required('seating_capacity', 'Seating capacity');

    $vanModel = new Van();
    if ($validator->passes() && $vanModel->plateExists(trim($_POST['plate_number']))) {
        $_SESSION['myvan_errors'] = [t('myvans_plate_taken')];
        $_SESSION['myvan_old'] = $_POST;
        header('Location: /sitrass/public/driver/myVans');
        exit;
    }

    if (!$validator->passes()) {
        $_SESSION['myvan_errors'] = $validator->getErrors();
        $_SESSION['myvan_old'] = $_POST;
        header('Location: /sitrass/public/driver/myVans');
        exit;
    }

    $vanModel->createForDriver($_POST, $this->driverRecord['driver_id']);

    $_SESSION['myvan_success'] = t('myvans_added');
    header('Location: /sitrass/public/driver/myVans');
    exit;
}

public function updateMyVan() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $vanId = (int)($_POST['van_id'] ?? 0);
    $vanModel = new Van();
    $van = $vanModel->getById($vanId);

    // Ownership guard: sariling van lang ng driver ang pwedeng i-edit
    if (!$van || $van['driver_id'] != $this->driverRecord['driver_id']) {
        die('Hindi mo ito van.');
    }

    $validator = new Validator($_POST);
    $validator->required('plate_number', 'Plate number')
        ->required('make', 'Make')
        ->required('model', 'Model')
        ->required('van_type', 'Van type')
        ->required('seating_capacity', 'Seating capacity');

    if ($validator->passes() && !$vanModel->plateExists(trim($_POST['plate_number']), $vanId)) {
        $vanModel->updateByOwner($vanId, $this->driverRecord['driver_id'], $_POST);
        $_SESSION['myvan_success'] = t('myvans_updated');
    } else {
        $_SESSION['myvan_errors'] = $validator->passes() ? [t('myvans_plate_taken')] : $validator->getErrors();
    }

    header('Location: /sitrass/public/driver/myVans');
    exit;
}

public function uploadMyVanImage() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $vanId = (int)($_POST['van_id'] ?? 0);
    $vanModel = new Van();
    $van = $vanModel->getById($vanId);

    // Ownership guard
    if (!$van || $van['driver_id'] != $this->driverRecord['driver_id']) {
        die('Hindi mo ito van.');
    }

    $result = ImageUpload::handle($_FILES['image'] ?? null, 'uploads/vans', 'van' . $vanId);

    if (!$result['success']) {
        $_SESSION['myvan_errors'] = [$result['error']];
        header('Location: /sitrass/public/driver/myVans');
        exit;
    }

    $imageModel = new VanImage();
    $isPrimary = $imageModel->countByVanId($vanId) === 0;
    if ($isPrimary) {
        $imageModel->clearPrimary($vanId);
    }
    $imageModel->create($vanId, $result['path'], $result['thumbnail'], $isPrimary, $_SESSION['user_id']);

    $_SESSION['myvan_success'] = t('myvans_photos') . ' ✓';
    header('Location: /sitrass/public/driver/myVans');
    exit;
}

// Pickup verification ng rental - manual reference code o QR scan content
// (RNT-...) mula sa customer. KATULAD NG SHARED BOOKING: hindi agad
// ina-activate - ipapakita muna sa driver ang detalye ng customer at
// kailangang kumpirmahin bago tuluyang maging 'active' ang rental.
public function verifyRentalPickup() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $ref = strtoupper(trim($_POST['reference_code'] ?? ''));
    $rentalModel = new VanRental();
    $rental = $rentalModel->getByReference($ref);

    $failBack = function($msg) {
        $_SESSION['driver_error'] = $msg;
        header('Location: /sitrass/public/driver/dashboard');
        exit;
    };

    if (!$rental) {
        $failBack('Hindi valid ang reference code na ito.');
    }

    // Guard: ang van ng rental ay sa iyo
    if ($rental['driver_id'] != $this->driverRecord['driver_id']) {
        $failBack('Ang van ng rental na ito ay hindi naka-assign sa iyo.');
    }

    // Guard: bayad na at naka-confirm pa lang (hindi pa na-pick up)
    if ($rental['status'] !== 'confirmed') {
        $failBack('Hindi na puwedeng i-verify ang rental na ito (malamang na-verify na noon).');
    }

    // Magkano na ang na-verify na bayad (para sa balance sa confirmation)
    $paymentModel = new Payment();
    $verifiedTotal = (float)array_sum(array_column($paymentModel->getVerifiedForRental($rental['rental_id']), 'amount'));

    // Hindi pa natin ina-activate - kailangan munang makita ng driver ang
    // detalye ng customer at kumpirmahin, katulad ng boarding confirmation.
    $_SESSION['rental_pickup_pending'] = [
        'rental_id' => (int)$rental['rental_id'],
        'reference_code' => $rental['reference_code'] ?? $ref,
        'customer_name' => $rental['customer_name'],
        'customer_phone' => $rental['customer_phone'],
        'dates' => $rental['start_date'] . ' → ' . $rental['end_date'],
        'days' => (int)$rental['days'],
        'payment_status' => $rental['payment_status'],
        'balance_due' => max(0, round((float)$rental['total_price'] - $verifiedTotal, 2)),
    ];

    header('Location: /sitrass/public/driver/dashboard');
    exit;
}

// Kumpirmasyon ng pickup (katulad ng verifyBoardingConfirm)
public function verifyRentalPickupConfirm() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $pending = $_SESSION['rental_pickup_pending'] ?? null;
    $rentalId = (int)($_POST['rental_id'] ?? 0);
    unset($_SESSION['rental_pickup_pending']);

    if (!$pending || (int)$pending['rental_id'] !== $rentalId) {
        $_SESSION['driver_error'] = 'Nag-expire na ang confirmation na ito. Subukan ulit i-verify.';
        header('Location: /sitrass/public/driver/dashboard');
        exit;
    }

    $rentalModel = new VanRental();
    $rentalModel->markActive($rentalId);

    $_SESSION['driver_message'] = t('rent_pickup_confirmed') . ' (' . $pending['reference_code'] . ')';
    header('Location: /sitrass/public/driver/dashboard');
    exit;
}

// Kanselasyon ng pickup confirmation (katulad ng verifyBoardingCancel)
public function verifyRentalPickupCancel() {
    unset($_SESSION['rental_pickup_pending']);
    header('Location: /sitrass/public/driver/dashboard');
    exit;
}

// Pagtatapos ng van rental - katulad ng endTrip ng shared booking:
// maging 'completed' ang rental at linisin ang live tracking sa Firebase.
public function endRental() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $rentalId = (int)($_POST['rental_id'] ?? 0);
    $rentalModel = new VanRental();
    $rental = $rentalModel->getById($rentalId);

    // Guard: ang van ng rental ay sa iyo at aktibo pa ang rental
    if (!$rental || $rental['driver_id'] != $this->driverRecord['driver_id'] || $rental['status'] !== 'active') {
        $_SESSION['driver_error'] = 'Hindi valid ang rental na ito.';
        header('Location: /sitrass/public/driver/dashboard');
        exit;
    }

    if ($rentalModel->markCompleted($rentalId)) {
        $_SESSION['driver_message'] = t('rent_ended');

        // Katulad ng endTrip: kapag may pending na F2F balance payment ang
        // rental, may popup sa susunod na dashboard load para i-verify ito.
        $_SESSION['check_payment_rental_id'] = $rentalId;

        // Linisin ang live tracking sa Firebase - dapat mawala na ang GPS
        // marker sa mapa kapag tapos na ang rental (katulad ng endTrip).
        $this->clearFirebaseLocation('driver_locations/' . $this->driverRecord['driver_id']);
    } else {
        $_SESSION['driver_error'] = 'Hindi na-process ang aksyon.';
    }

    header('Location: /sitrass/public/driver/dashboard');
    exit;
}

// Best-effort na pag-alis ng location node sa Firebase Realtime Database
// (pinapayagan ng security rules ang pagsulat sa mga path na ito).
private function clearFirebaseLocation($path) {
    $url = 'https://sitrass-default-rtdb.firebaseio.com/' . $path . '.json';
    $ch = curl_init($url);
    if (!$ch) return;
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_TIMEOUT => 5,
        CURLOPT_RETURNTRANSFER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}
}