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
            'pageTitle' => 'Maghanap ng Biyahe - SITRASS',
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

    $errors = $_SESSION['book_errors'] ?? [];
    unset($_SESSION['book_errors']);

    View::render('customer-book', [
        'pageTitle' => 'Mag-book ng Biyahe - SITRASS',
        'schedule' => $schedule,
        'route' => $route,
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
        'pageTitle' => 'Booking Confirmed - SITRASS',
        'reservation' => $reservation,
    ]);
}

public function myBookings() {
    $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
    $reservationModel = new Reservation();
    $reservations = $reservationModel->getByCustomerId($customerId);

    View::render('customer-my-bookings', [
        'pageTitle' => 'Aking Mga Booking - SITRASS',
        'reservations' => $reservations,
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
}