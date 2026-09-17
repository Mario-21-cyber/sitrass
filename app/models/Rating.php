<?php

class Rating extends Model {
    protected $table = 'ratings';

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO ratings
                (booking_id, rental_id, customer_id, driver_id, van_id, overall_rating,
                 punctuality_rating, cleanliness_rating, driving_rating, comment)
             VALUES
                (:booking_id, :rental_id, :customer_id, :driver_id, :van_id, :overall_rating,
                 :punctuality_rating, :cleanliness_rating, :driving_rating, :comment)"
        );
        $stmt->execute([
            'booking_id' => $data['booking_id'] ?? null,
            'rental_id' => $data['rental_id'] ?? null,
            'customer_id' => $data['customer_id'],
            'driver_id' => $data['driver_id'],
            'van_id' => $data['van_id'],
            'overall_rating' => $data['overall_rating'],
            'punctuality_rating' => $data['punctuality_rating'] ?: null,
            'cleanliness_rating' => $data['cleanliness_rating'] ?: null,
            'driving_rating' => $data['driving_rating'] ?: null,
            'comment' => $data['comment'] ?: null,
        ]);

        // I-update ang running average ng driver batay sa lahat ng visible na rating
        $this->recalculateDriverRating($data['driver_id']);

        return $this->db->lastInsertId();
    }

    protected function recalculateDriverRating($driverId) {
        $stmt = $this->db->prepare(
            "SELECT AVG(overall_rating) AS avg_rating, COUNT(*) AS total
             FROM ratings WHERE driver_id = ? AND is_visible = 1"
        );
        $stmt->execute([$driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare(
            "UPDATE drivers SET rating_average = ?, rating_count = ? WHERE driver_id = ?"
        );
        $stmt->execute([
            round($result['avg_rating'] ?? 0, 2),
            $result['total'] ?? 0,
            $driverId,
        ]);
    }

    public function existsForBooking($bookingId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ratings WHERE booking_id = ?");
        $stmt->execute([$bookingId]);
        return $stmt->fetchColumn() > 0;
    }

    public function getAll() {
        $stmt = $this->db->query(
            "SELECT r.*, CONCAT(cu.first_name, ' ', cu.last_name) AS customer_name,
                    CONCAT(du.first_name, ' ', du.last_name) AS driver_name,
                    v.plate_number
             FROM ratings r
             JOIN customers c ON c.customer_id = r.customer_id
             JOIN users cu ON cu.user_id = c.user_id
             LEFT JOIN drivers d ON d.driver_id = r.driver_id
             LEFT JOIN users du ON du.user_id = d.user_id
             LEFT JOIN vans v ON v.van_id = r.van_id
             ORDER BY r.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toggleVisibility($ratingId, $hide, $reason = null) {
        $stmt = $this->db->prepare(
            "UPDATE ratings SET is_visible = ?, hidden_reason = ? WHERE rating_id = ?"
        );
        $stmt->execute([$hide ? 0 : 1, $hide ? $reason : null, $ratingId]);

        // I-recalculate ulit ang driver average dahil nagbago ang visibility
        $stmt = $this->db->prepare("SELECT driver_id FROM ratings WHERE rating_id = ?");
        $stmt->execute([$ratingId]);
        $driverId = $stmt->fetchColumn();
        if ($driverId) {
            $this->recalculateDriverRating($driverId);
        }
    }
public function getByBooking($bookingId) {
    $stmt = $this->db->prepare("SELECT * FROM ratings WHERE booking_id = ? LIMIT 1");
    $stmt->execute([$bookingId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Rating ng isang van rental (katulad ng getByBooking)
public function getByRental($rentalId) {
    $stmt = $this->db->prepare("SELECT * FROM ratings WHERE rental_id = ? LIMIT 1");
    $stmt->execute([$rentalId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

public function update($ratingId, $data) {
    $stmt = $this->db->prepare(
        "UPDATE ratings
         SET overall_rating = ?, punctuality_rating = ?, cleanliness_rating = ?, driving_rating = ?, comment = ?
         WHERE rating_id = ?"
    );
    $stmt->execute([
        $data['overall_rating'],
        $data['punctuality_rating'] ?: null,
        $data['cleanliness_rating'] ?: null,
        $data['driving_rating'] ?: null,
        $data['comment'] ?: null,
        $ratingId,
    ]);

    // I-recalculate ang driver average dahil nagbago ang rating
    $this->recalculateDriverRating($data['driver_id']);
}

// Base query para sa history ng mga rating ng customer - kasama ang
// detalye ng biyahe (ref code, ruta, driver, plate) sa bawat rating.
// Kasama na rito ang mga RENTAL rating (booking_id ay nullable, may
// rental_id) - ang ruta ng rental ang ipinapakita para sa mga ito.
protected function historySql() {
    return "(SELECT r.*,
                    COALESCE(rs.reference_code, vr.reference_code) AS reference_code,
                    b.travel_date,
                    COALESCE(o.name, rr.origin_name, pl.name) AS pickup_name,
                    COALESCE(dl.name, rr.destination_name) AS dropoff_name,
                    CONCAT(du.first_name, ' ', du.last_name) AS driver_name,
                    v.plate_number
             FROM ratings r
             LEFT JOIN bookings b ON b.booking_id = r.booking_id
             LEFT JOIN reservations rs ON rs.reservation_id = b.reservation_id
             LEFT JOIN locations o ON o.location_id = b.pickup_location_id
             LEFT JOIN locations dl ON dl.location_id = b.dropoff_location_id
             LEFT JOIN van_rentals vr ON vr.rental_id = r.rental_id
             LEFT JOIN (
                 SELECT ro.route_id,
                        o3.name AS origin_name,
                        d3.name AS destination_name
                 FROM routes ro
                 JOIN locations o3 ON o3.location_id = ro.origin_location_id
                 JOIN locations d3 ON d3.location_id = ro.destination_location_id
             ) rr ON rr.route_id = vr.route_id
             LEFT JOIN locations pl ON pl.location_id = vr.pickup_location_id
             LEFT JOIN drivers dd ON dd.driver_id = r.driver_id
             LEFT JOIN users du ON du.user_id = dd.user_id
             LEFT JOIN vans v ON v.van_id = r.van_id) AS rh";
}

// Lahat ng rating ng customer - pinakabago muna (para sa Rate History)
public function getHistoryForCustomer($customerId) {
    $stmt = $this->db->prepare(
        "SELECT rh.* FROM " . $this->historySql() . " WHERE rh.customer_id = ? ORDER BY rh.created_at DESC, rh.rating_id DESC"
    );
    $stmt->execute([$customerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Pinakabagong rating ng customer - iisa lang (para sa Itaas ng Rate page)
public function getLatestForCustomer($customerId) {
    $stmt = $this->db->prepare(
        "SELECT rh.* FROM " . $this->historySql() . " WHERE rh.customer_id = ? ORDER BY rh.created_at DESC, rh.rating_id DESC LIMIT 1"
    );
    $stmt->execute([$customerId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

}