<?php

class Booking extends Model {
    protected $table = 'bookings';

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO bookings
                (reservation_id, schedule_id, route_id, van_id, driver_id, booking_mode,
                 pickup_location_id, dropoff_location_id, travel_date, pickup_time,
                 seats_booked, fare_amount)
             VALUES
                (:reservation_id, :schedule_id, :route_id, :van_id, :driver_id, :booking_mode,
                 :pickup_location_id, :dropoff_location_id, :travel_date, :pickup_time,
                 :seats_booked, :fare_amount)"
        );
        $stmt->execute([
            'reservation_id' => $data['reservation_id'],
            'schedule_id' => $data['schedule_id'],
            'route_id' => $data['route_id'],
            'van_id' => $data['van_id'],
            'driver_id' => $data['driver_id'],
            'booking_mode' => $data['booking_mode'],
            'pickup_location_id' => $data['pickup_location_id'],
            'dropoff_location_id' => $data['dropoff_location_id'],
            'travel_date' => $data['travel_date'],
            'pickup_time' => $data['pickup_time'],
            'seats_booked' => $data['seats_booked'],
            'fare_amount' => $data['fare_amount'],
        ]);
        return $this->db->lastInsertId();
    }
    public function getByReservationId($reservationId) {
    $stmt = $this->db->prepare("SELECT * FROM bookings WHERE reservation_id = ?");
    $stmt->execute([$reservationId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function cancelAllForReservation($reservationId) {
    $stmt = $this->db->prepare(
        "UPDATE bookings SET status = 'cancelled' WHERE reservation_id = ?"
    );
    $stmt->execute([$reservationId]);
}

public function moveToNewSchedule($bookingId, $newSchedule) {
    $stmt = $this->db->prepare(
        "UPDATE bookings
         SET schedule_id = ?, van_id = ?, driver_id = ?, travel_date = ?, pickup_time = ?
         WHERE booking_id = ?"
    );
    $stmt->execute([
        $newSchedule['schedule_id'],
        $newSchedule['van_id'],
        $newSchedule['driver_id'],
        $newSchedule['departure_date'],
        $newSchedule['departure_time'],
        $bookingId,
    ]);
}
public function getForDriver($driverId) {
    $stmt = $this->db->prepare(
        "SELECT b.*, rs.reference_code,
                o.name AS pickup_name, d.name AS dropoff_name,
                CONCAT(cu.first_name, ' ', cu.last_name) AS customer_name,
                cu.phone AS customer_phone
         FROM bookings b
         JOIN reservations rs ON rs.reservation_id = b.reservation_id
         JOIN customers c ON c.customer_id = rs.customer_id
         JOIN users cu ON cu.user_id = c.user_id
         JOIN locations o ON o.location_id = b.pickup_location_id
         JOIN locations d ON d.location_id = b.dropoff_location_id
         WHERE b.driver_id = ?
         ORDER BY b.travel_date ASC, b.pickup_time ASC"
    );
    $stmt->execute([$driverId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getById($id) {
    $stmt = $this->db->prepare("SELECT * FROM bookings WHERE booking_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function accept($bookingId, $driverId) {
    $stmt = $this->db->prepare(
        "UPDATE bookings SET status = 'accepted', accepted_at = NOW()
         WHERE booking_id = ? AND driver_id = ? AND status = 'pending'"
    );
    $stmt->execute([$bookingId, $driverId]);
    return $stmt->rowCount() > 0;
}

public function reject($bookingId, $driverId, $reason) {
    $stmt = $this->db->prepare(
        "UPDATE bookings SET status = 'rejected', rejected_at = NOW(), rejection_reason = ?
         WHERE booking_id = ? AND driver_id = ? AND status = 'pending'"
    );
    $stmt->execute([$reason, $bookingId, $driverId]);
    return $stmt->rowCount() > 0;
}

public function startTrip($bookingId, $driverId) {
    $stmt = $this->db->prepare(
        "UPDATE bookings SET status = 'en_route', trip_started_at = NOW()
         WHERE booking_id = ? AND driver_id = ? AND status = 'accepted'"
    );
    $stmt->execute([$bookingId, $driverId]);
    return $stmt->rowCount() > 0;
}

public function endTrip($bookingId, $driverId) {
    $stmt = $this->db->prepare(
        "UPDATE bookings SET status = 'completed', trip_ended_at = NOW()
         WHERE booking_id = ? AND driver_id = ? AND status = 'en_route'"
    );
    $stmt->execute([$bookingId, $driverId]);
    return $stmt->rowCount() > 0;
}
public function getCompletedUnratedForCustomer($customerId) {
    $stmt = $this->db->prepare(
        "SELECT b.*, rs.reference_code, v.plate_number,
                CONCAT(du.first_name, ' ', du.last_name) AS driver_name,
                o.name AS pickup_name, dl.name AS dropoff_name
         FROM bookings b
         JOIN reservations rs ON rs.reservation_id = b.reservation_id
         JOIN vans v ON v.van_id = b.van_id
         LEFT JOIN drivers d ON d.driver_id = b.driver_id
         LEFT JOIN users du ON du.user_id = d.user_id
         JOIN locations o ON o.location_id = b.pickup_location_id
         JOIN locations dl ON dl.location_id = b.dropoff_location_id
         LEFT JOIN ratings r ON r.booking_id = b.booking_id
         WHERE rs.customer_id = ? AND b.status = 'completed' AND r.rating_id IS NULL
         ORDER BY b.trip_ended_at DESC"
    );
    $stmt->execute([$customerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getWithDriverForTracking($bookingId, $customerId) {
    $stmt = $this->db->prepare(
        "SELECT b.*, rs.reference_code, v.plate_number,
                d.driver_id, CONCAT(du.first_name, ' ', du.last_name) AS driver_name,
                o.name AS pickup_name, o.latitude AS pickup_lat, o.longitude AS pickup_lng,
                dl.name AS dropoff_name, dl.latitude AS dropoff_lat, dl.longitude AS dropoff_lng
         FROM bookings b
         JOIN reservations rs ON rs.reservation_id = b.reservation_id
         JOIN vans v ON v.van_id = b.van_id
         LEFT JOIN drivers d ON d.driver_id = b.driver_id
         LEFT JOIN users du ON du.user_id = d.user_id
         JOIN locations o ON o.location_id = b.pickup_location_id
         JOIN locations dl ON dl.location_id = b.dropoff_location_id
         WHERE b.booking_id = ? AND rs.customer_id = ?"
    );
    $stmt->execute([$bookingId, $customerId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
}