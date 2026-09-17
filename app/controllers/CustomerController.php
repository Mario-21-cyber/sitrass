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
                $newBookingId = $bookingModel->create([
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

    // Gumawa ng QR code (base sa Reference Code) at ikabit sa email, kung
    // matagumpay na nakuha ang booking ID - kung hindi, ipadala pa rin ang
    // email nang walang QR sa halip na biguin ang buong booking.
    $qrImageHtml = '';
    if (!empty($newBookingId)) {
        $qrModel = new QrBooking();
        $qr = $qrModel->getOrCreate($newBookingId);
        if ($qr && !empty($qr['raw_token'])) {
            $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qr['raw_token']);
            $qrImageHtml = '<p><img src="' . $qrImageUrl . '" alt="QR Code"></p>
                             <p style="font-size:0.85rem;color:#666;">Ipakita ang QR code na ito sa driver bago sumakay.</p>';
        }
    }

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
        . $qrImageHtml
    );

    // SMS backup - lalabas lang kung naka-on ang setting at may naka-store na
    // phone number ang customer. Hindi nagpapabigo ang buong booking kung
    // sakaling mabigo ang SMS - text lang ito, hindi kritikal.
    $settingModel = new SystemSetting();
    if ($settingModel->getValue('sms_enabled', 0) && !empty($customerUser['phone'])) {
        Sms::send(
            $customerUser['phone'],
            'SITRASS: Nakumpirma ang booking mo (' . $reservation['reference_code'] . '). Bayaran ang deposit sa loob ng 2 oras.'
        );
    }

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
    $allReservations = $reservationModel->getByCustomerId($customerId);

    // Dito na lang natin ipapakita ang mga aktibong reservation - ang mga
    // "completed" at "cancelled" ay napupunta na sa Kasaysayan ng Biyahe.
    $reservations = array_values(array_filter($allReservations, function($r) {
        return !in_array($r['status'], ['completed', 'cancelled']);
    }));

    // Ikabit ang payment history sa bawat reservation - listahan ng bawat
    // verified na bayad (deposit, tapos balance), hindi nagpapalit, dumadagdag
    // lang bawat pagkakataon na may bagong verified payment.
    $paymentModel = new Payment();
    foreach ($reservations as &$r) {
        $r['payment_history'] = $paymentModel->getVerifiedForReservation($r['reservation_id']);
    }
    unset($r);
    // Kunin din ang LAHAT ng van rentals ng customer para sa iisang My Bookings
    // view - KASAMA ang completed at cancelled, para makita ang buong transaction
    // history (katulad ng Shared Trips na ipinapakita ang payment history).
    $rentalModel = new VanRental();
    $myRentals = $rentalModel->getForCustomer($customerId);
    foreach ($myRentals as &$rent) {
        $rent['payment_history'] = $paymentModel->getVerifiedForRental($rent['rental_id']);
    }
    unset($rent);


    $message = $_SESSION['booking_message'] ?? null;
    $error = $_SESSION['booking_error'] ?? null;
    unset($_SESSION['booking_message'], $_SESSION['booking_error']);

    View::render('customer-my-bookings', [
        'pageTitle' => t('nav_my_bookings') . ' - SITRASS',
        'reservations' => $reservations,
        'myRentals' => $myRentals,
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

    // Kung wala pang deposit, iyon ang dapat unahing halaga - kung bayad na
    // ang deposit, ang natitirang balance na ang dapat lumitaw bilang default.
    $amountToPay = $reservation['payment_status'] === 'pending'
        ? $reservation['deposit_required']
        : $reservation['balance_due'];

    View::render('customer-pay', [
        'pageTitle' => t('pay_page_title') . ' - SITRASS',
        'reservation' => $reservation,
        'methods' => $methodModel->getActiveWithDynamicText(),
        'preferredMethodId' => $preferredMethodId,
        'amountToPay' => $amountToPay,
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
                                $scheduleModel->restoreWholeVan($b['schedule_id']);
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
                $scheduleModel->restoreWholeVan($booking['schedule_id']);
        $decremented = $scheduleModel->bookWholeVan($newScheduleId);

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

    // Kasama rin ang mga tapos nang VAN RENTAL na hindi pa na-rate -
    // iisang pahina, iisang listahan (katulad ng shared trips).
    $unratedRentals = (new VanRental())->getCompletedUnratedForCustomer($customerId);

    // Para sa "Pinakabagong Rating" sa itaas at "Rate History" sa pahina
    $ratingModel = new Rating();
    $latestRating = $ratingModel->getLatestForCustomer($customerId);
    $ratingHistory = $ratingModel->getHistoryForCustomer($customerId);

    View::render('customer-to-rate', [
        'pageTitle' => t('nav_rate_trip') . ' - SITRASS',
        'trips' => $unrated,
        'rentals' => $unratedRentals,
        'latestRating' => $latestRating,
        'ratingHistory' => $ratingHistory,
    ]);
}

// Rate page para sa isang van rental (katulad ng rate() ng shared booking)
public function rateRental($rentalId) {
    $rentalId = (int)$rentalId;
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $rental = (new VanRental())->getById($rentalId);

    if (!$rental || $rental['customer_id'] != $customerId) {
        die('Rental not found.');
    }

    if ($rental['status'] !== 'completed') {
        die('Tapusin muna ang rental bago mag-rate. <a href="/sitrass/public/customer/myBookings?tab=rentals">Bumalik</a>');
    }

    $ratingModel = new Rating();
    $existing = $ratingModel->getByRental($rentalId);
    if ($existing && $existing['customer_id'] != $customerId) {
        die('Wala kang access sa rating na ito.');
    }

    View::render('customer-rate', [
        'pageTitle' => ($existing ? t('rate_edit_title') : t('rate_page_h2')) . ' - SITRASS',
        'booking' => null,
        'rental' => $rental,
        'existing' => $existing,
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

    // Kung may rating na ang booking, EDIT mode na ito (hindi na bawal) -
    // basta sa kanya mismo ang rating na iyon.
    $existing = $ratingModel->getByBooking($bookingId);
    if ($existing && $existing['customer_id'] != $customerId) {
        die('Wala kang access sa rating na ito.');
    }

    View::render('customer-rate', [
        'pageTitle' => ($existing ? t('rate_edit_title') : t('rate_page_h2')) . ' - SITRASS',
        'booking' => $booking,
        'existing' => $existing,
    ]);
}

public function submitRating() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $overall = (int)($_POST['overall_rating'] ?? 0);
    if ($overall < 1 || $overall > 5) {
        die('Hindi valid ang rating.');
    }

    $payload = [
        'overall_rating' => $overall,
        'punctuality_rating' => $_POST['punctuality_rating'] ?? null,
        'cleanliness_rating' => $_POST['cleanliness_rating'] ?? null,
        'driving_rating' => $_POST['driving_rating'] ?? null,
        'comment' => $_POST['comment'] ?? null,
    ];

    $ratingModel = new Rating();

    // ---------------------------------------------------------------
    // RENTAL rating (katulad ng booking rating)
    // ---------------------------------------------------------------
    $rentalId = (int)($_POST['rental_id'] ?? 0);
    if ($rentalId > 0) {
        $rental = (new VanRental())->getById($rentalId);
        if (!$rental || $rental['customer_id'] != $customerId) {
            die('Wala kang access sa rental na ito.');
        }
        if ($rental['status'] !== 'completed') {
            die('Tapusin muna ang rental bago mag-rate.');
        }

        $existing = $ratingModel->getByRental($rentalId);
        if ($existing) {
            // EDIT MODE - i-update ang dating rating (sa may-ari lang nito)
            if ($existing['customer_id'] != $customerId) {
                die('Wala kang access sa rating na ito.');
            }
            $payload['driver_id'] = $existing['driver_id'];
            $ratingModel->update($existing['rating_id'], $payload);
            $_SESSION['booking_message'] = 'Na-update na ang iyong rating!';
        } else {
            $ratingModel->create(array_merge($payload, [
                'rental_id' => $rentalId,
                'customer_id' => $customerId,
                'driver_id' => $rental['driver_id'],
                'van_id' => $rental['van_id'],
            ]));
            $_SESSION['booking_message'] = 'Salamat sa pag-rate!';
        }

        header('Location: /sitrass/public/customer/toRate');
        exit;
    }

    // ---------------------------------------------------------------
    // BOOKING rating (orihinal na daloy)
    // ---------------------------------------------------------------
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $booking = (new Booking())->getById($bookingId);

    if (!$booking) {
        die('Booking not found.');
    }

    $existing = $ratingModel->getByBooking($bookingId);

    if ($existing) {
        // EDIT MODE - i-update ang dating rating (sa may-ari lang nito)
        if ($existing['customer_id'] != $customerId) {
            die('Wala kang access sa rating na ito.');
        }
        $payload['driver_id'] = $existing['driver_id'];
        $ratingModel->update($existing['rating_id'], $payload);
        $_SESSION['booking_message'] = 'Na-update na ang iyong rating!';
    } else {
        // Bagong rating - siguraduhing sa kanya ang booking
        $reservation = (new Reservation())->getById($booking['reservation_id']);
        if (!$reservation || $reservation['customer_id'] != $customerId) {
            die('Wala kang access sa biyaheng ito.');
        }
        $payload = array_merge($payload, [
            'booking_id' => $bookingId,
            'customer_id' => $customerId,
            'driver_id' => $booking['driver_id'],
            'van_id' => $booking['van_id'],
        ]);
        $ratingModel->create($payload);
        $_SESSION['booking_message'] = 'Salamat sa pag-rate!';
    }

    header('Location: /sitrass/public/customer/toRate');
    exit;
}
public function feedback() {
    $message = $_SESSION['feedback_message'] ?? null;
    unset($_SESSION['feedback_message']);

    View::render('customer-feedback', [
        'pageTitle' => t('nav_give_feedback') . ' - SITRASS',
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

// Live tracking ng VAN RENTAL - katulad ng trackTrip ng shared booking:
// mapa na may ruta papunta sa destinasyon (OSRM), "Pupuntahan" na pin,
// at live na lokasyon ng van mula sa GPS ng driver.
public function trackRental($rentalId) {
    $rentalId = (int)$rentalId;
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);

    $rentalModel = new VanRental();
    $rental = $rentalModel->getById($rentalId);

    if (!$rental || $rental['customer_id'] != $customerId) {
        die('Rental not found.');
    }

    if ($rental['status'] !== 'active') {
        die('Hindi pa nasisimulan ang rental na ito, o tapos na. <a href="/sitrass/public/customer/myBookings?tab=rentals">Bumalik</a>');
    }

    View::render('customer-rent-track', [
        'pageTitle' => 'I-track ang Rental - SITRASS',
        'rental' => $rental,
    ]);
}

// JSON status ng rental para sa live polling ng track page
public function rentalStatus($rentalId) {
    header('Content-Type: application/json');
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);

    $rental = (new VanRental())->getById((int)$rentalId);
    if (!$rental || $rental['customer_id'] != $customerId) {
        echo json_encode(['status' => null]);
        exit;
    }

    echo json_encode(['status' => $rental['status']]);
    exit;
}
public function history() {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);

    // Base sa BOOKINGS ang history (hindi lang reservation status) para
    // lumabas agad ang mga biyahe na: completed ng driver, na-cancel, o
    // lumipas na ang travel date - kahit hindi pa na-update ang status
    // ng buong reservation. Ang mga upcoming/pending na biyahe ay nananatili
    // sa My Bookings.
    $bookingModel = new Booking();
    $pastReservations = $bookingModel->getHistoryForCustomer($customerId);

    // Kasama rin sa kasaysayan ang mga van rental - completed, cancelled,
    // o lumipas na ang rental period (katulad ng shared bookings).
    $rentalModel = new VanRental();
    $rentals = $rentalModel->getHistoryForCustomer($customerId);

    // Ikabit ang payment history sa bawat rental - katulad ng shared booking
    // na ipinapakita ang payment history (deposit + balance) sa history card.
    $paymentModel = new Payment();
    foreach ($rentals as &$r) {
        $r['payment_history'] = $paymentModel->getVerifiedForRental($r['rental_id']);
        $verifiedTotal = (float)array_sum(array_column($r['payment_history'], 'amount'));
        $r['balance_due'] = max(0, round((float)$r['total_price'] - $verifiedTotal, 2));
    }
    unset($r);

    View::render('customer-history', [
        'pageTitle' => t('history_page_title') . ' - SITRASS',
        'reservations' => $pastReservations,
        'rentals' => $rentals,
    ]);
}

// Sinusuri ng customer tracking page kada ~20s kung tapos na ang biyahe.
public function tripStatus($bookingId) {
    $bookingModel = new Booking();
    $booking = $bookingModel->getById((int)$bookingId);

    header('Content-Type: application/json');
    $owned = false;
    if ($booking) {
        $customerId = $this->getCustomerIdForUser($_SESSION['user_id'] ?? 0);
        $reservation = (new Reservation())->getById($booking['reservation_id']);
        $owned = $reservation && $reservation['customer_id'] == $customerId;
    }

    if (!$owned) {
        echo json_encode(['status' => null]);
        exit;
    }
    echo json_encode(['status' => $booking['status']]);
    exit;
}

// =====================================================================
// RENT A VAN - buong van na inuupahan ng customer
// Status bawat van: available / may biyahe / maintenance / naka-rent.
// Presyo: whole_van_day_rate na itinatakda ng admin, x bilang ng araw.
// =====================================================================
public function rentVan() {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $vanModel = new Van();
    $vans = $vanModel->getForRent();

    foreach ($vans as &$v) {
        if ($v['status'] === 'maintenance') {
            $v['rent_status'] = 'maintenance';
        } elseif ($v['status'] !== 'active') {
            $v['rent_status'] = 'inactive';
        } elseif ($v['has_active_booking']) {
            // May accepted/en_route booking — nasa biyahe ang van
            $v['rent_status'] = 'on_trip';
        } elseif ($v['has_active_rental']) {
            // May active rental (na-pick up na) — nasa biyahe ang van
            $v['rent_status'] = 'on_trip';
        } elseif ($v['has_future_rental']) {
            // May confirmed/pending rental (nabayaran na ng customer) — naka-book na
            $v['rent_status'] = 'rented';
        } elseif ($v['has_upcoming_trip']) {
            // May upcoming schedule kahit walang booking pa — naka-schedule
            $v['rent_status'] = 'on_trip';
        } else {
            $v['rent_status'] = 'available';
        }
    }
    unset($v);

    $success = $_SESSION['rent_success'] ?? null;
    $error = $_SESSION['rent_error'] ?? null;
    unset($_SESSION['rent_success'], $_SESSION['rent_error']);

    View::render('customer-rent-van', [
        'pageTitle' => t('nav_rent_van') . ' - SITRASS',
        'vans' => $vans,
        'success' => $success,
        'error' => $error,
    ]);
}

public function rentVanBook($vanId) {
    $vanModel = new Van();
    $van = $vanModel->getForRentById((int)$vanId);
    if (!$van) {
        die('Van not found.');
    }

    // Server-side availability check - hindi pwedang i-book kung hindi active
    // ang van, may maintenance, may biyahe, o may sabayang rental.
    $rentStatus = 'available';
    if ($van['status'] === 'maintenance') {
        $rentStatus = 'maintenance';
    } elseif ($van['status'] !== 'active') {
        $rentStatus = 'inactive';
    } elseif ($van['has_active_booking']) {
        $rentStatus = 'on_trip';
    } elseif ($van['has_active_rental']) {
        $rentStatus = 'on_trip';
    } elseif ($van['has_future_rental']) {
        $rentStatus = 'rented';
    } elseif ($van['has_upcoming_trip']) {
        $rentStatus = 'on_trip';
    }

    if ($rentStatus !== 'available') {
        $msgMap = [
            'maintenance' => t('rent_error_maintenance'),
            'inactive' => t('rent_error_maintenance'),
            'on_trip' => t('rent_error_on_trip'),
            'rented' => t('rent_error_rented'),
        ];
        $_SESSION['rent_error'] = $msgMap[$rentStatus];
        header('Location: /sitrass/public/customer/rentVan');
        exit;
    }

    $locationModel = new Location();
    $routeModel = new Route();
    $old = $_SESSION['rent_old'] ?? [];
    unset($_SESSION['rent_old']);

    View::render('customer-rent-book', [
        'pageTitle' => t('nav_rent_van') . ' - SITRASS',
        'van' => $van,
        'locations' => $locationModel->getAll(),
        'routes' => $routeModel->getAll(),
        'old' => $old,
        'error' => null,
    ]);
}

public function rentVanStore() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    if (!$customerId) {
        die('Customer record not found.');
    }

    $fail = function($msg) {
        $_SESSION['rent_error'] = $msg;
        header('Location: /sitrass/public/customer/rentVan');
        exit;
    };

    $vanModel = new Van();
    $vanId = (int)($_POST['van_id'] ?? 0);
    $van = $vanModel->getById($vanId);
    if (!$van) {
        die('Van not found.');
    }

    // Muling availability check (baka nagbago habang nasa form)
    $info = $vanModel->getForRentById($vanId);
    if (!$info || $van['status'] === 'maintenance') {
        $fail(t('rent_error_maintenance'));
    }
    if ($info['has_active_booking'] || $info['has_upcoming_trip']) {
        $fail(t('rent_error_on_trip'));
    }
    if ($info['has_future_rental']) {
        $fail(t('rent_error_rented'));
    }

    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? '';
    if (!$start || !$end || $start < date('Y-m-d') || $end < $start) {
        $fail(t('rent_error_dates'));
    }

    $days = (int)floor((strtotime($end) - strtotime($start)) / 86400) + 1;
    if ($days < 1 || $days > 30) {
        $fail(t('rent_error_dates'));
    }

    $rate = (float)$van['whole_van_day_rate'];
    if ($rate <= 0) {
        $fail(t('rent_error_rate'));
    }

    // Route ng biyahe - required (katulad ng shared booking na may ruta)
    $routeId = (int)($_POST['route_id'] ?? 0);
    $routeModel = new Route();
    $route = $routeModel->getById($routeId);
    if (!$route) {
        $fail(t('rent_error_route'));
    }

    $rentalModel = new VanRental();
    if ($rentalModel->hasConflict($vanId, $start, $end)) {
        $fail(t('rent_error_overlap'));
    }

    // Deposit base sa admin setting - katulad ng shared booking
    $settingModel = new SystemSetting();
    $depositPct = (float)$settingModel->getValue('deposit_percentage', 30);
    $total = round($days * $rate, 2);
    $deposit = round($total * $depositPct / 100, 2);

    // Unique reference code (RNT-...) - ito rin ang laman ng QR
    do {
        $ref = 'RNT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
    } while ($rentalModel->refExists($ref));

    $rentalId = $rentalModel->create([
        'van_id' => $vanId,
        'customer_id' => $customerId,
        'route_id' => $routeId,
        'start_date' => $start,
        'end_date' => $end,
        'pickup_location_id' => (int)($_POST['pickup_location_id'] ?? 0) ?: null,
        'days' => $days,
        'price_per_day' => $rate,
        'total_price' => $total,
        'deposit_required' => $deposit,
        'reference_code' => $ref,
        'notes' => trim($_POST['notes'] ?? ''),
    ]);

    // Tuloy sa payment page - deposit muna bago ma-confirm,
    // katulad ng daloy ng per-shared na booking.
    header('Location: /sitrass/public/customer/rentPay/' . (int)$rentalId);
    exit;
}

// Payment page para sa rental - deposit muna, tapos balance kung may
// natitira (katulad ng payReservation ng shared booking)
public function rentPay($rentalId) {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $rentalModel = new VanRental();
    $rental = $rentalModel->getById((int)$rentalId);

    if (!$rental || $rental['customer_id'] != $customerId) {
        die('Rental not found.');
    }

    // Kapag nasa biyahe na, o kanselado - wala nang babayan dito.
    // (Ang 'completed' ay KASAMA para ma-babayad pa rin ang natitirang
    // balance pagkatapos ng biyahe - katulad ng shared booking flow.)
    if (!in_array($rental['status'], ['pending', 'confirmed', 'completed'])) {
        header('Location: /sitrass/public/customer/myBookings?tab=rentals');
        exit;
    }

    // Magkano na ang na-verify na bayad at magkano ang natitira
    $paymentModel = new Payment();
    $verifiedTotal = (float)array_sum(array_column($paymentModel->getVerifiedForRental($rental['rental_id']), 'amount'));
    $balanceDue = max(0, round((float)$rental['total_price'] - $verifiedTotal, 2));

    // Buo na ang bayad - diretso na sa My Bookings
    if ($balanceDue <= 0.005) {
        header('Location: /sitrass/public/customer/myBookings?tab=rentals');
        exit;
    }

    // Kung wala pang naibabayad, ang deposit ang unahin; kung bayad na ito,
    // ang natitirang balance na ang default na halaga (katulad ng shared booking).
    $amountToPay = ($verifiedTotal <= 0.005 && (float)$rental['deposit_required'] > 0)
        ? (float)$rental['deposit_required']
        : $balanceDue;

    $methodModel = new PaymentMethod();
    $error = $_SESSION['rent_pay_error'] ?? null;
    unset($_SESSION['rent_pay_error']);

    View::render('customer-rent-pay', [
        'pageTitle' => t('rent_pay_title') . ' - SITRASS',
        'rental' => $rental,
        'methods' => $methodModel->getActiveWithDynamicText(),
        'amountToPay' => $amountToPay,
        'balanceDue' => $balanceDue,
        'verifiedTotal' => $verifiedTotal,
        'error' => $error,
    ]);
}

// Submit ng rental payment (katulad ng submitPayment) - itinatala bilang
// 'deposit' kapag wala pang na-verify na bayad, at 'balance' kapag mayroon na
public function rentPaySubmit() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $rentalId = (int)($_POST['rental_id'] ?? 0);
    $rentalModel = new VanRental();
    $rental = $rentalModel->getById($rentalId);

    if (!$rental || $rental['customer_id'] != $customerId) {
        die('Rental not found.');
    }

    $backTo = function($msg) use ($rentalId) {
        $_SESSION['rent_pay_error'] = $msg;
        header('Location: /sitrass/public/customer/rentPay/' . $rentalId);
        exit;
    };

    if (!in_array($rental['status'], ['pending', 'confirmed', 'completed'])) {
        header('Location: /sitrass/public/customer/myBookings?tab=rentals');
        exit;
    }

    // Deposit o balance? Base sa kabuuang na-verify na bayad - kapag wala pa,
    // deposit; kapag mayroon na, balance na (katulad ng shared booking).
    $paymentModel = new Payment();
    $verifiedTotal = (float)array_sum(array_column($paymentModel->getVerifiedForRental($rentalId), 'amount'));
    $balanceDue = max(0, round((float)$rental['total_price'] - $verifiedTotal, 2));

    if ($balanceDue <= 0.005) {
        header('Location: /sitrass/public/customer/myBookings?tab=rentals');
        exit;
    }

    $paymentType = $verifiedTotal <= 0.005 ? 'deposit' : 'balance';

    $methodId = (int)($_POST['method_id'] ?? 0);
    $method = (new PaymentMethod())->getById($methodId);
    if (!$method) {
        $backTo('Piliin ang paraan ng pagbabayad.');
    }

    if ($paymentModel->referenceExists($methodId, $_POST['reference_number'] ?? '')) {
        $backTo('Nagamit na ang reference number na ito sa nakaraang pagbabayad.');
    }

    $proofImagePath = null;
    if ($method['requires_proof']) {
        if (empty($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
            $backTo('Kailangan ng proof ng pagbabayad (screenshot) para sa paraang ito.');
        }
        $upload = ImageUpload::handle($_FILES['proof'], 'uploads/payments', 'rent' . $rentalId);
        if (!$upload['success']) {
            $backTo($upload['error']);
        }
        $proofImagePath = $upload['path'];
    }

    $paymentModel->create([
        'reservation_id' => null,
        'rental_id' => $rentalId,
        'method_id' => $methodId,
        'payment_type' => $paymentType,
        'amount' => $_POST['amount'],
        'reference_number' => $_POST['reference_number'] ?? null,
        'proof_image' => $proofImagePath,
    ]);

    // Iba ang mensahe depende sa paraan ng pagbabayad:
    // - F2F (cash) + may driver ang van: ang DRIVER ang mag-verify
    // - F2F (cash) + WALANG driver ang van: ang ADMIN ang mag-verify (fallback)
    // - GCash (online): ang ADMIN ang mag-verify
    if ((int)$method['is_online'] === 0) {
        // Suriin kung may driver ang van ng rental na ito
        $vanModel = new Van();
        $van = $vanModel->getById($rental['van_id']);
        if (!empty($van['driver_id'])) {
            $_SESSION['booking_message'] = t('rent_pay_submitted_f2f');
        } else {
            // Walang driver ang van - admin ang mag-verify bilang fallback
            $_SESSION['booking_message'] = t('rent_pay_submitted_gcash');
        }
    } else {
        $_SESSION['booking_message'] = t('rent_pay_submitted_gcash');
    }
    header('Location: /sitrass/public/customer/myBookings?tab=rentals');
    exit;
}

// Kanselasyon ng van rental - katulad ng cancelBooking: may 12-oras na
// cutoff bago magsimula ang rental, at pending o confirmed pa lang ito.
public function cancelRental() {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        die('Invalid na session.');
    }

    $rentalId = (int)($_POST['rental_id'] ?? 0);
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $rentalModel = new VanRental();
    $rental = $rentalModel->getById($rentalId);

    if (!$rental || $rental['customer_id'] != $customerId) {
        die('Rental not found.');
    }

    if (!in_array($rental['status'], ['pending', 'confirmed'])) {
        $_SESSION['booking_error'] = 'Hindi na puwedeng kanselahin ang rental na ito.';
        header('Location: /sitrass/public/customer/myBookings?tab=rentals');
        exit;
    }

    // Cutoff: 12 oras bago magsimula ang rental (katulad ng shared booking)
    $startTimestamp = strtotime($rental['start_date'] . ' 00:00:00');
    $hoursUntilStart = ($startTimestamp - time()) / 3600;

    if ($hoursUntilStart < 12) {
        $_SESSION['booking_error'] = 'Hindi na puwedeng kanselahin - kailangan ng hindi bababa sa 12 oras bago ang simula ng rental.';
        header('Location: /sitrass/public/customer/myBookings?tab=rentals');
        exit;
    }

    if ($rentalModel->cancelForCustomer($rentalId, $customerId)) {
        $_SESSION['booking_message'] = t('rent_cancel_success');
    } else {
        $_SESSION['booking_error'] = 'Hindi na-process ang aksyon.';
    }

    header('Location: /sitrass/public/customer/myBookings?tab=rentals');
    exit;
}

// QR code ng rental para sa pickup (available lang kapag bayad na)
public function viewRentalQr($rentalId) {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $rentalModel = new VanRental();
    $rental = $rentalModel->getById((int)$rentalId);

    if (!$rental || $rental['customer_id'] != $customerId) {
        die('Rental not found.');
    }

    if ($rental['payment_status'] === 'pending') {
        die('Kailangan munang magbayad ng deposit bago makuha ang QR code. <a href="/sitrass/public/customer/rentVan">Bumalik</a>');
    }

    View::render('customer-rent-qr', [
        'pageTitle' => 'Rental QR - SITRASS',
        'rental' => $rental,
    ]);
}
}