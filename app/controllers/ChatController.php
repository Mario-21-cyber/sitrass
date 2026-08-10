<?php

class ChatController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /sitrass/public/auth/login');
            exit;
        }
    }

    public function open($bookingId) {
        $bookingId = (int)$bookingId;
        $bookingModel = new Booking();
        $booking = $bookingModel->getById($bookingId);

        if (!$booking) {
            die('Booking not found.');
        }

        // I-verify na kabilang ang naka-login na user sa booking na ito -
        // customer ng booking, o ang naka-assign na driver.
        $isAuthorized = false;
        $otherPartyName = '';

        if ($_SESSION['role'] === 'customer') {
            $customerId = $this->getCustomerIdForUser($_SESSION['user_id']);
            $reservationModel = new Reservation();
            $reservation = $reservationModel->getById($booking['reservation_id']);
            if ($reservation && $reservation['customer_id'] == $customerId) {
                $isAuthorized = true;
                if ($booking['driver_id']) {
                    $driverModel = new Driver();
                    $stmt = (new Model())->getConnection()->prepare(
                        "SELECT CONCAT(u.first_name,' ',u.last_name) FROM drivers d JOIN users u ON u.user_id=d.user_id WHERE d.driver_id=?"
                    );
                    $stmt->execute([$booking['driver_id']]);
                    $otherPartyName = $stmt->fetchColumn() ?: 'Driver';
                }
            }
        } elseif ($_SESSION['role'] === 'driver') {
            $driverModel = new Driver();
            $driver = $driverModel->getByUserId($_SESSION['user_id']);
            if ($driver && $booking['driver_id'] == $driver['driver_id']) {
                $isAuthorized = true;
                $stmt = (new Model())->getConnection()->prepare(
                    "SELECT CONCAT(u.first_name,' ',u.last_name) FROM reservations rs
                     JOIN customers c ON c.customer_id = rs.customer_id
                     JOIN users u ON u.user_id = c.user_id
                     WHERE rs.reservation_id = ?"
                );
                $stmt->execute([$booking['reservation_id']]);
                $otherPartyName = $stmt->fetchColumn() ?: 'Customer';
            }
        }

        if (!$isAuthorized) {
            die('Wala kang access sa chat na ito.');
        }

        $headerFile = $_SESSION['role'] === 'driver' ? '_driver_header.php' : '_customer_header.php';
        $footerFile = $_SESSION['role'] === 'driver' ? '_driver_footer.php' : '_customer_footer.php';

        View::render('chat', [
            'pageTitle' => 'Chat - SITRASS',
            'bookingId' => $bookingId,
            'otherPartyName' => $otherPartyName,
            'myName' => $_SESSION['full_name'],
            'myRole' => $_SESSION['role'],
            'headerFile' => $headerFile,
            'footerFile' => $footerFile,
        ]);
    }

    protected function getCustomerIdForUser($userId) {
        $db = (new Model())->getConnection();
        $stmt = $db->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: null;
    }
}