<?php

class CustomerController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function search() {
        $locationModel = new Location();
        $scheduleModel = new TripSchedule();

        $origin = $_GET['origin'] ?? '';
        $destination = $_GET['destination'] ?? '';
        $date = $_GET['date'] ?? '';

        $results = null;
        if ($origin || $destination || $date) {
            $results = $scheduleModel->search(
                $origin ?: null,
                $destination ?: null,
                $date ?: null
            );
        }

        View::render('customer-search', [
                        'pageTitle' => t('nav_search') . ' - SITRASS',
            'locations' => $locationModel->getAll(),
            'results' => $results,
            'selectedOrigin' => $origin,
            'selectedDestination' => $destination,
            'selectedDate' => $date,
        ]);
    }
    public function book($scheduleId) {
    $scheduleId = (int)$scheduleId;

    $scheduleModel = new TripSchedule();
    $schedule = $scheduleModel->getById($scheduleId);

    if (!$schedule || $schedule['status'] !== 'scheduled' || $schedule['available_seats'] < 1) {
        die('Hindi na available ang biyaheng ito. <a href="/sitrass/public/customer/search">Bumalik sa search</a>');
    }

    $routeModel = new Route();
    $route = null;
    foreach ($routeModel->getAll() as $r) {
        if ($r['route_id'] == $schedule['route_id']) {
            $route = $r;
            break;
        }
    }

    $methodModel = new PaymentMethod();
    $settingModel = new SystemSetting();
    $depositPercentage = $settingModel->getValue('deposit_percentage', 30);
    $depositDisplay = rtrim(rtrim(number_format($depositPercentage, 2), '0'), '.');

    $errors = $_SESSION['book_errors'] ?? [];
    unset($_SESSION['book_errors']);

    View::render('customer-book', [
        'pageTitle' => 'Mag-book ng Biyahe - SITRASS',
        'schedule' => $schedule,
        'route' => $route,
        'methods' => $methodModel->getActive(),
        'depositPercentage' => $depositDisplay,
        'errors' => $errors,
    ]);
}

public function confirmBooking() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['book_errors'] = ['Invalid o expired na session. Subukan ulit.'];
        header('Location: /sitrass/public/customer/book/' . (int)($_POST['schedule_id'] ?? 0));
        exit;
    }

    $scheduleId = (int)($_POST['schedule_id'] ?? 0);
    $passengerCount = (int)($_POST['passenger_count'] ?? 1);
    $chosenMethodId = (int)($_POST['method_id'] ?? 0);

    $scheduleModel = new TripSchedule();
    $schedule = $scheduleModel->getById($scheduleId);

    if (!$schedule || $schedule['status'] !== 'scheduled') {
        die('Hindi na available ang biyaheng ito.');
    }

    if ($passengerCount < 1 || $passengerCount > $schedule['available_seats']) {
        $_SESSION['book_errors'] = ['Hindi valid ang bilang ng pasahero, o kulang na ang natitirang upuan.'];
        header('Location: /sitrass/public/customer/book/' . $scheduleId);
        exit;
    }

    // Kunin ang customer_id ng naka-login na user
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);

    if (!$customerId) {
        die('Customer record not found.');
    }

    $totalAmount = $schedule['fare_per_seat'] * $passengerCount;

    // --- Simulan ang transaction: dalawa o higit pang table ang babaguhin,
    // kailangang parehong magtagumpay o parehong mabigo. ---
    $db = (new Model())->getConnection();
    $db->beginTransaction();

    try {
        $scheduleModelTx = new TripSchedule();

        // Atomic na pagbawas - ito ang unang linya ng depensa laban sa overbooking
        $decremented = $scheduleModelTx->decrementSeats($scheduleId, $passengerCount);

        if (!$decremented) {
            $db->rollBack();
            $_SESSION['book_errors'] = ['Naubusan na ng upuan bago ka pa nakapag-book. Subukan ulit.'];
            header('Location: /sitrass/public/customer/book/' . $scheduleId);
            exit;
        }

        $reservationModel = new Reservation();
        $reservation = $reservationModel->create([
            'customer_id' => $customerId,
            'booking_type' => $schedule['booking_mode'] === 'exclusive' ? 'whole_van' : 'seat',
            'passenger_count' => $passengerCount,
            'total_amount' => $totalAmount,
        ]);

        $bookingModel = new Booking();
        $bookingModel->create([
            'reservation_id' => $reservation['reservation_id'],
            'schedule_id' => $scheduleId,
            'route_id' => $schedule['route_id'],
            'van_id' => $schedule['van_id'],
            'driver_id' => $schedule['driver_id'],
            'booking_mode' => $schedule['booking_mode'],
            'pickup_location_id' => $this->getRouteOriginId($schedule['route_id']),
            'dropoff_location_id' => $this->getRouteDestinationId($schedule['route_id']),
            'travel_date' => $schedule['departure_date'],
            'pickup_time' => $schedule['departure_time'],
            'seats_booked' => $passengerCount,
            'fare_amount' => $totalAmount,
        ]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        die('May naganap na error sa pag-book. Subukan ulit.');
    }

   // Itago ang napiling paraan ng bayad, para ma-preselect sa Magbayad page mamaya
    $_SESSION['preferred_method_' . $reservation['reference_code']] = $chosenMethodId;

    $userModel = new User();
    $customerUser = $userModel->getById($_SESSION['user_id']);

    Mailer::send(
        $customerUser['email'],
        $customerUser['first_name'],
        'Nakumpirma ang Booking - ' . $reservation['reference_code'],
        '<p>Kumusta, ' . htmlspecialchars($customerUser['first_name']) . '!</p>
         <p>Narito ang detalye ng iyong booking:</p>
         <p><strong>Reference Code:</strong> ' . htmlspecialchars($reservation['reference_code']) . '</p>
         <p><strong>Bilang ng Pasahero:</strong> ' . (int)$passengerCount . '</p>
         <p><strong>Kailangang Deposit:</strong> ₱' . number_format($totalAmount * 0.30, 2) . '</p>
         <p>Bayaran ang deposit sa loob ng 2 oras para hindi ma-cancel ang reservation.</p>'
    );

    header('Location: /sitrass/public/customer/booking-confirmed/' . $reservation['reference_code']);
    exit;
}

public function bookingConfirmed($referenceCode) {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $reservationModel = new Reservation();
    $reservation = $reservationModel->getByReferenceCode($referenceCode, $customerId);

    if (!$reservation) {
        die('Reservation not found.');
    }

    View::render('customer-booking-confirmed', [
                'pageTitle' => t('title_booking_confirmed'),
        'reservation' => $reservation,
    ]);
}

public function myBookings() {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $reservationModel = new Reservation();
    $reservations = $reservationModel->getByCustomerId($customerId);

    $message = $_SESSION['booking_message'] ?? null;
    $error = $_SESSION['booking_error'] ?? null;
    unset($_SESSION['booking_message'], $_SESSION['booking_error']);

    View::render('customer-my-bookings', [
                'pageTitle' => t('nav_my_bookings') . ' - SITRASS',
        'reservations' => $reservations,
        'message' => $message,
        'error' => $error,
    ]);
}

protected function getCustomerIdForUser($userId) {
    $db = (new Model())->getConnection();
    $stmt = $db->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: null;
}

protected function getRouteOriginId($routeId) {
    $db = (new Model())->getConnection();
    $stmt = $db->prepare("SELECT origin_location_id FROM routes WHERE route_id = ?");
    $stmt->execute([$routeId]);
    return $stmt->fetchColumn();
}

protected function getRouteDestinationId($routeId) {
    $db = (new Model())->getConnection();
    $stmt = $db->prepare("SELECT destination_location_id FROM routes WHERE route_id = ?");
    $stmt->execute([$routeId]);
    return $stmt->fetchColumn();
}
public function payReservation($referenceCode) {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $reservationModel = new Reservation();
    $reservation = $reservationModel->getByReferenceCode($referenceCode, $customerId);

    if (!$reservation) {
        die('Reservation not found.');
    }

    $methodModel = new PaymentMethod();
    $error = $_SESSION['payment_error'] ?? null;
    unset($_SESSION['payment_error']);

    $preferredMethodId = $_SESSION['preferred_method_' . $referenceCode] ?? null;

    View::render('customer-pay', [
        'pageTitle' => 'Magbayad - SITRASS',
        'reservation' => $reservation,
        'methods' => $methodModel->getActiveWithDynamicText(),
        'preferredMethodId' => $preferredMethodId,
        'error' => $error,
    ]);
}

public function submitPayment() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        $_SESSION['payment_error'] = 'Invalid o expired na session. Subukan ulit.';
        header('Location: /sitrass/public/customer/payReservation/' . urlencode($_POST['reference_code'] ?? ''));
        exit;
    }

    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $referenceCode = $_POST['reference_code'] ?? '';

    $reservationModel = new Reservation();
    $reservation = $reservationModel->getByReferenceCode($referenceCode, $customerId);

    if (!$reservation) {
        die('Reservation not found.');
    }

    $methodId = (int)($_POST['method_id'] ?? 0);
    $methodModel = new PaymentMethod();
    $method = $methodModel->getById($methodId);

    if (!$method) {
        $_SESSION['payment_error'] = 'Piliin ang paraan ng pagbabayad.';
        header('Location: /sitrass/public/customer/payReservation/' . urlencode($referenceCode));
        exit;
    }

    $paymentModel = new Payment();
    if ($paymentModel->referenceExists($methodId, $_POST['reference_number'] ?? '')) {
        $_SESSION['payment_error'] = 'Nagamit na ang reference number na ito sa nakaraang pagbabayad. I-check kung tama ang inilagay mo, o kung nasumite mo na dati.';
        header('Location: /sitrass/public/customer/payReservation/' . urlencode($referenceCode));
        exit;
    }

    $proofImagePath = null;

    if ($method['requires_proof']) {
        if (empty($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['payment_error'] = 'Kailangan ng proof ng pagbabayad (screenshot) para sa paraang ito.';
            header('Location: /sitrass/public/customer/payReservation/' . urlencode($referenceCode));
            exit;
        }

        $upload = ImageUpload::handle($_FILES['proof'], 'uploads/payments', 'pay' . $reservation['reservation_id']);
        if (!$upload['success']) {
            $_SESSION['payment_error'] = $upload['error'];
            header('Location: /sitrass/public/customer/payReservation/' . urlencode($referenceCode));
            exit;
        }
        $proofImagePath = $upload['path'];
    }

    $paymentModel->create([
        'reservation_id' => $reservation['reservation_id'],
        'method_id' => $methodId,
        'payment_type' => $reservation['payment_status'] === 'pending' ? 'deposit' : 'balance',
        'amount' => $_POST['amount'],
        'reference_number' => $_POST['reference_number'] ?? null,
        'proof_image' => $proofImagePath,
    ]);

    header('Location: /sitrass/public/customer/myBookings');
    exit;
}
public function cancelBooking() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $referenceCode = $_POST['reference_code'] ?? '';
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);

    $reservationModel = new Reservation();
    $reservation = $reservationModel->getByReferenceCode($referenceCode, $customerId);

    if (!$reservation) {
        die('Reservation not found.');
    }

    if (!in_array($reservation['status'], ['pending', 'confirmed'])) {
        $_SESSION['booking_error'] = 'Hindi na puwedeng kanselahin ang reservation na ito.';
        header('Location: /sitrass/public/customer/myBookings');
        exit;
    }

    $bookingModel = new Booking();
    $bookings = $bookingModel->getByReservationId($reservation['reservation_id']);

    // Cutoff: 12 oras bago ang unang biyahe (system_settings: cancellation_cutoff_hours)
    foreach ($bookings as $b) {
        $departureTimestamp = strtotime($b['travel_date'] . ' ' . $b['pickup_time']);
        $hoursUntilDeparture = ($departureTimestamp - time()) / 3600;

        if ($hoursUntilDeparture < 12) {
            $_SESSION['booking_error'] = 'Hindi na puwedeng kanselahin - kailangan ng hindi bababa sa 12 oras bago ang biyahe.';
            header('Location: /sitrass/public/customer/myBookings');
            exit;
        }
    }

    $db = (new Model())->getConnection();
    $db->beginTransaction();

    try {
        $scheduleModel = new TripSchedule();

        // Ibalik ang upuan sa bawat schedule na naka-link sa reservation na ito
        foreach ($bookings as $b) {
            if ($b['schedule_id']) {
                $scheduleModel->incrementSeats($b['schedule_id'], $b['seats_booked']);
            }
        }

        $bookingModel->cancelAllForReservation($reservation['reservation_id']);

        $reservationModel->cancel($reservation['reservation_id'], $_SESSION['user_id'], 'Kinansela ng customer');

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        die('May naganap na error sa pagkansela. Subukan ulit.');
    }

    $_SESSION['booking_message'] = 'Nakansela na ang reservation.';
    header('Location: /sitrass/public/customer/myBookings');
    exit;
}

public function rescheduleBooking($referenceCode) {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $reservationModel = new Reservation();
    $reservation = $reservationModel->getByReferenceCode($referenceCode, $customerId);

    if (!$reservation) {
        die('Reservation not found.');
    }

    $bookingModel = new Booking();
    $bookings = $bookingModel->getByReservationId($reservation['reservation_id']);
    $booking = $bookings[0] ?? null;

    if (!$booking || !$booking['schedule_id']) {
        die('Hindi na-reschedule ang uri ng booking na ito.');
    }

    $scheduleModel = new TripSchedule();
    $alternatives = $scheduleModel->getByRoute($booking['route_id'], $booking['schedule_id']);

    $error = $_SESSION['reschedule_error'] ?? null;
    unset($_SESSION['reschedule_error']);

    View::render('customer-reschedule', [
        'pageTitle' => 'I-reschedule ang Biyahe - SITRASS',
        'reservation' => $reservation,
        'booking' => $booking,
        'alternatives' => $alternatives,
        'error' => $error,
    ]);
}

public function confirmReschedule() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $referenceCode = $_POST['reference_code'] ?? '';
    $newScheduleId = (int)($_POST['new_schedule_id'] ?? 0);

    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $reservationModel = new Reservation();
    $reservation = $reservationModel->getByReferenceCode($referenceCode, $customerId);

    if (!$reservation) {
        die('Reservation not found.');
    }

    $bookingModel = new Booking();
    $bookings = $bookingModel->getByReservationId($reservation['reservation_id']);
    $booking = $bookings[0] ?? null;

    if (!$booking) {
        die('Booking not found.');
    }

    // Cutoff: 24 oras bago ang biyahe (system_settings: reschedule_cutoff_hours)
    $departureTimestamp = strtotime($booking['travel_date'] . ' ' . $booking['pickup_time']);
    $hoursUntilDeparture = ($departureTimestamp - time()) / 3600;

    if ($hoursUntilDeparture < 24) {
        $_SESSION['reschedule_error'] = 'Hindi na puwedeng mag-reschedule - kailangan ng hindi bababa sa 24 oras bago ang biyahe.';
        header('Location: /sitrass/public/customer/rescheduleBooking/' . urlencode($referenceCode));
        exit;
    }

    $scheduleModel = new TripSchedule();
    $newSchedule = $scheduleModel->getById($newScheduleId);

    if (!$newSchedule || $newSchedule['status'] !== 'scheduled' || $newSchedule['available_seats'] < $booking['seats_booked']) {
        $_SESSION['reschedule_error'] = 'Hindi na available ang napiling biyahe.';
        header('Location: /sitrass/public/customer/rescheduleBooking/' . urlencode($referenceCode));
        exit;
    }

    $db = (new Model())->getConnection();
    $db->beginTransaction();

    try {
        // Ibalik ang upuan sa lumang schedule, bawasan ang upuan sa bago
        $scheduleModel->incrementSeats($booking['schedule_id'], $booking['seats_booked']);
        $decremented = $scheduleModel->decrementSeats($newScheduleId, $booking['seats_booked']);

        if (!$decremented) {
            $db->rollBack();
            $_SESSION['reschedule_error'] = 'Naubusan na ng upuan ang napiling biyahe. Subukan ulit.';
            header('Location: /sitrass/public/customer/rescheduleBooking/' . urlencode($referenceCode));
            exit;
        }

        $bookingModel->moveToNewSchedule($booking['booking_id'], $newSchedule);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        die('May naganap na error sa pag-reschedule. Subukan ulit.');
    }

    $_SESSION['booking_message'] = 'Matagumpay na na-reschedule ang biyahe.';
    header('Location: /sitrass/public/customer/myBookings');
    exit;
}
public function viewQr($referenceCode) {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $reservationModel = new Reservation();
    $reservation = $reservationModel->getByReferenceCode($referenceCode, $customerId);

    if (!$reservation) {
        die('Reservation not found.');
    }

    if ($reservation['payment_status'] === 'pending') {
        die('Kailangan munang magbayad ng deposit bago makuha ang QR code. <a href="/sitrass/public/customer/myBookings">Bumalik</a>');
    }

    $bookingModel = new Booking();
    $bookings = $bookingModel->getByReservationId($reservation['reservation_id']);
    $booking = $bookings[0] ?? null;

    if (!$booking) {
        die('Booking not found.');
    }

    $qrModel = new QrBooking();
    $qr = $qrModel->getOrCreate($booking['booking_id']);

    // Kung bago lang nagawa, meron tayong raw_token. Kung dati na itong ginawa,
    // kailangan nating gumawa ng bagong token dahil hindi na natin ito na-retrieve.
    if (!isset($qr['raw_token'])) {
        // Ang QR code na dati na nating na-display ay gumagamit pa rin ng lumang token.
        // Dahil hash lang ang naka-store, hindi na natin ito ma-verify pabalik.
        // Kaya sa halip, gagamitin natin ang qr_id + booking_id bilang display fallback.
        $qr['raw_token'] = null;
    }

    View::render('customer-qr', [
        'pageTitle' => 'QR Code - SITRASS',
        'reservation' => $reservation,
        'booking' => $booking,
        'qr' => $qr,
    ]);
}
public function toRate() {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $bookingModel = new Booking();
    $unrated = $bookingModel->getCompletedUnratedForCustomer($customerId);

    View::render('customer-to-rate', [
        'pageTitle' => 'Mag-rate ng Biyahe - SITRASS',
        'trips' => $unrated,
    ]);
}

public function rate($bookingId) {
    $bookingId = (int)$bookingId;
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $booking = (new Booking())->getById($bookingId);

    if (!$booking) {
        die('Booking not found.');
    }

    $ratingModel = new Rating();
    if ($ratingModel->existsForBooking($bookingId)) {
        die('Na-rate mo na ang biyaheng ito. <a href="/sitrass/public/customer/myBookings">Bumalik</a>');
    }

    View::render('customer-rate', [
                'pageTitle' => t('rate_page_h2') . ' - SITRASS',
        'booking' => $booking,
    ]);
}

public function submitRating() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $booking = (new Booking())->getById($bookingId);
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);

    if (!$booking) {
        die('Booking not found.');
    }

    $ratingModel = new Rating();
    if ($ratingModel->existsForBooking($bookingId)) {
        die('Na-rate mo na ang biyaheng ito.');
    }

    $overall = (int)($_POST['overall_rating'] ?? 0);
    if ($overall < 1 || $overall > 5) {
        die('Hindi valid ang rating.');
    }

    $ratingModel->create([
        'booking_id' => $bookingId,
        'customer_id' => $customerId,
        'driver_id' => $booking['driver_id'],
        'van_id' => $booking['van_id'],
        'overall_rating' => $overall,
        'punctuality_rating' => $_POST['punctuality_rating'] ?? null,
        'cleanliness_rating' => $_POST['cleanliness_rating'] ?? null,
        'driving_rating' => $_POST['driving_rating'] ?? null,
        'comment' => $_POST['comment'] ?? null,
    ]);

    $_SESSION['booking_message'] = 'Salamat sa pag-rate!';
    header('Location: /sitrass/public/customer/myBookings');
    exit;
}
public function feedback() {
    $message = $_SESSION['feedback_message'] ?? null;
    unset($_SESSION['feedback_message']);

    View::render('customer-feedback', [
        'pageTitle' => 'Feedback - SITRASS',
        'message' => $message,
    ]);
}

public function submitFeedback() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $validator = new Validator($_POST);
    $validator->required('category', 'Kategorya')
        ->required('subject', 'Paksa')
        ->required('message', 'Mensahe');

    if (!$validator->passes()) {
        $_SESSION['feedback_message'] = 'Punuin ang lahat ng kinakailangang field.';
        header('Location: /sitrass/public/customer/feedback');
        exit;
    }

    $feedbackModel = new Feedback();
    $feedbackModel->create([
        'user_id' => $_SESSION['user_id'] ?? null,
        'category' => $_POST['category'],
        'subject' => $_POST['subject'],
        'message' => $_POST['message'],
        'contact_email' => $_POST['contact_email'] ?? null,
    ]);

    $_SESSION['feedback_message'] = 'Salamat sa iyong feedback! Titignan namin ito sa lalong madaling panahon.';
    header('Location: /sitrass/public/customer/feedback');
    exit;
}
public function trackTrip($bookingId) {
    $bookingId = (int)$bookingId;
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);

    $bookingModel = new Booking();
    $booking = $bookingModel->getWithDriverForTracking($bookingId, $customerId);

    if (!$booking) {
        die('Booking not found.');
    }

    if ($booking['status'] !== 'en_route') {
        die('Hindi pa nagsisimula ang biyaheng ito, o tapos na. <a href="/sitrass/public/customer/myBookings">Bumalik</a>');
    }

    View::render('customer-track', [
        'pageTitle' => 'I-track ang Biyahe - SITRASS',
        'booking' => $booking,
    ]);
}
public function history() {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);

    $reservationModel = new Reservation();
    $allReservations = $reservationModel->getByCustomerId($customerId);
    $pastReservations = array_values(array_filter($allReservations, function($r) {
        return in_array($r['status'], ['completed', 'cancelled']);
    }));

    View::render('customer-history', [
        'pageTitle' => t('history_page_title') . ' - SITRASS',
        'reservations' => $pastReservations,
    ]);
}
}