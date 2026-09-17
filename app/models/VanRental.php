<?php

class VanRental extends Model {
    protected $table = 'van_rentals';

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO van_rentals (reference_code, van_id, route_id, customer_id, start_date, end_date,
                pickup_location_id, days, price_per_day, total_price, deposit_required, status, payment_status, notes)
             VALUES (:reference_code, :van_id, :route_id, :customer_id, :start_date, :end_date,
                :pickup_location_id, :days, :price_per_day, :total_price, :deposit_required, 'pending', 'pending', :notes)"
        );
        $stmt->execute([
            'reference_code' => $data['reference_code'] ?? null,
            'van_id' => $data['van_id'],
            'route_id' => !empty($data['route_id']) ? $data['route_id'] : null,
            'customer_id' => $data['customer_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'pickup_location_id' => $data['pickup_location_id'] ?: null,
            'days' => $data['days'],
            'price_per_day' => $data['price_per_day'],
            'total_price' => $data['total_price'],
            'deposit_required' => $data['deposit_required'] ?? 0.00,
            'notes' => $data['notes'] ?: null,
        ]);
        return $this->db->lastInsertId();
    }

    // May sabayang rental ang van sa mga petsang iyon? (double-booking guard)
    public function hasConflict($vanId, $start, $end) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM van_rentals
             WHERE van_id = ? AND status IN ('pending','confirmed','active')
               AND NOT (end_date < ? OR start_date > ?)"
        );
        $stmt->execute([$vanId, $start, $end]);
        return $stmt->fetchColumn() > 0;
    }

    public function getForCustomer($customerId) {
        $stmt = $this->db->prepare(
            "SELECT vr.*, v.plate_number, v.make, v.model,
                    l.name AS pickup_name,
                    CONCAT(drv_u.first_name, ' ', drv_u.last_name) AS driver_name,
                    drv_u.phone AS driver_phone,
                    CONCAT(r.origin_name, ' → ', r.destination_name) AS route_label,
                    rt.rating_id AS existing_rating_id
             FROM van_rentals vr
             JOIN vans v ON v.van_id = vr.van_id
             LEFT JOIN drivers d ON d.driver_id = v.driver_id
             LEFT JOIN users drv_u ON drv_u.user_id = d.user_id
             LEFT JOIN locations l ON l.location_id = vr.pickup_location_id
             LEFT JOIN ratings rt ON rt.rental_id = vr.rental_id
             LEFT JOIN (
                 SELECT ro.route_id,
                        o.name AS origin_name,
                        d2.name AS destination_name
                 FROM routes ro
                 JOIN locations o ON o.location_id = ro.origin_location_id
                 JOIN locations d2 ON d2.location_id = ro.destination_location_id
             ) r ON r.route_id = vr.route_id
             WHERE vr.customer_id = ?
             ORDER BY vr.created_at DESC"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($rentalId) {
        $stmt = $this->db->prepare(
            "SELECT vr.*, v.driver_id, v.plate_number, v.make, v.model, v.seating_capacity,
                    CONCAT(r.origin_name, ' → ', r.destination_name) AS route_label,
                    l.name AS pickup_name,
                    COALESCE(p_o.latitude, r.origin_lat) AS pickup_lat,
                    COALESCE(p_o.longitude, r.origin_lng) AS pickup_lng,
                    r.dest_lat AS dropoff_lat, r.dest_lng AS dropoff_lng,
                    CONCAT(drv_u.first_name, ' ', drv_u.last_name) AS driver_name,
                    drv_u.phone AS driver_phone
             FROM van_rentals vr
             JOIN vans v ON v.van_id = vr.van_id
             LEFT JOIN drivers d ON d.driver_id = v.driver_id
             LEFT JOIN users drv_u ON drv_u.user_id = d.user_id
             LEFT JOIN (
                 SELECT ro.route_id,
                        o.name AS origin_name, o.latitude AS origin_lat, o.longitude AS origin_lng,
                        d2.name AS destination_name, d2.latitude AS dest_lat, d2.longitude AS dest_lng
                 FROM routes ro
                 JOIN locations o ON o.location_id = ro.origin_location_id
                 JOIN locations d2 ON d2.location_id = ro.destination_location_id
             ) r ON r.route_id = vr.route_id
             LEFT JOIN locations l ON l.location_id = vr.pickup_location_id
             LEFT JOIN locations p_o ON p_o.location_id = vr.pickup_location_id
             WHERE vr.rental_id = ?"
        );
        $stmt->execute([$rentalId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByRefAndCustomer($referenceCode, $customerId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM van_rentals WHERE reference_code = ? AND customer_id = ?"
        );
        $stmt->execute([$referenceCode, $customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Pag na-verify na ng admin ang isang payment ng rental - katulad ng
    // reservation flow: deposit muna, tapos balance hanggang maging buo.
    // Ang payment_status ay nakabatay sa kabuuang na-verify na bayad kumpara
    // sa total, at ang status ay 'confirmed' LANG kapag pending pa ito -
    // hindi inaano ang 'active' na rental na na-pick up na ng driver.
    public function markPaid($rentalId) {
        $stmt = $this->db->prepare("SELECT total_price FROM van_rentals WHERE rental_id = ?");
        $stmt->execute([$rentalId]);
        $total = (float)$stmt->fetchColumn();

        $sum = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE rental_id = ? AND status = 'verified'"
        );
        $sum->execute([$rentalId]);
        $verified = (float)$sum->fetchColumn();

        if ($total > 0 && $verified >= $total - 0.005) {
            $paymentStatus = 'paid';
        } elseif ($verified > 0) {
            $paymentStatus = 'partially_paid';
        } else {
            $paymentStatus = 'pending';
        }

        $stmt = $this->db->prepare(
            "UPDATE van_rentals
             SET payment_status = :ps,
                 status = CASE WHEN status = 'pending' THEN 'confirmed' ELSE status END
             WHERE rental_id = :id"
        );
        $stmt->execute(['ps' => $paymentStatus, 'id' => $rentalId]);
    }

    // Driver pickup verification - maging 'active' ang rental
    public function markActive($rentalId) {
        $stmt = $this->db->prepare(
            "UPDATE van_rentals SET status = 'active' WHERE rental_id = ? AND status = 'confirmed'"
        );
        $stmt->execute([$rentalId]);
    }

    // Driver-side lookup gamit ang RNT reference code (walang customer scope)
    public function getByReference($referenceCode) {
        $stmt = $this->db->prepare(
            "SELECT vr.*, v.driver_id, v.plate_number, v.make, v.model,
                    CONCAT(cu.first_name, ' ', cu.last_name) AS customer_name, cu.phone AS customer_phone
             FROM van_rentals vr
             JOIN vans v ON v.van_id = vr.van_id
             JOIN customers c ON c.customer_id = vr.customer_id
             JOIN users cu ON cu.user_id = c.user_id
             WHERE vr.reference_code = ?"
        );
        $stmt->execute([strtoupper(trim($referenceCode))]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function refExists($referenceCode) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM van_rentals WHERE reference_code = ?");
        $stmt->execute([$referenceCode]);
        return $stmt->fetchColumn() > 0;
    }

    // Mga confirmed (bayad na) rental ng mga van ng driver - para sa
    // pickup verification sa driver dashboard
    public function getPickupsForDriver($driverId) {
        $stmt = $this->db->prepare(
            "SELECT vr.*, v.plate_number, v.make, v.model,
                    CONCAT(cu.first_name, ' ', cu.last_name) AS customer_name, cu.phone AS customer_phone
             FROM van_rentals vr
             JOIN vans v ON v.van_id = vr.van_id
             JOIN customers c ON c.customer_id = vr.customer_id
             JOIN users cu ON cu.user_id = c.user_id
             WHERE v.driver_id = ? AND vr.status = 'confirmed'
               AND vr.end_date >= CURDATE()
             ORDER BY vr.start_date ASC"
        );
        $stmt->execute([$driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Aktibong rental (nasa biyahe na) ng mga van ng driver - para sa
    // "Kasalukuyang Biyahe" na card sa dashboard, katulad ng shared booking.
    // Kasama ang mga koordinate (pickup -> destination) para sa live na mapa.
    public function getActiveForDriver($driverId) {
        $stmt = $this->db->prepare(
            "SELECT vr.*, v.plate_number, v.make, v.model,
                    CONCAT(cu.first_name, ' ', cu.last_name) AS customer_name, cu.phone AS customer_phone,
                    CONCAT(r.origin_name, ' → ', r.destination_name) AS route_label,
                    l.name AS pickup_name,
                    COALESCE(p_o.latitude, r.origin_lat) AS pickup_lat,
                    COALESCE(p_o.longitude, r.origin_lng) AS pickup_lng,
                    r.dest_lat AS dropoff_lat, r.dest_lng AS dropoff_lng
             FROM van_rentals vr
             JOIN vans v ON v.van_id = vr.van_id
             JOIN customers c ON c.customer_id = vr.customer_id
             JOIN users cu ON cu.user_id = c.user_id
             LEFT JOIN (
                 SELECT ro.route_id,
                        o.name AS origin_name, o.latitude AS origin_lat, o.longitude AS origin_lng,
                        d2.name AS destination_name, d2.latitude AS dest_lat, d2.longitude AS dest_lng
                 FROM routes ro
                 JOIN locations o ON o.location_id = ro.origin_location_id
                 JOIN locations d2 ON d2.location_id = ro.destination_location_id
             ) r ON r.route_id = vr.route_id
             LEFT JOIN locations l ON l.location_id = vr.pickup_location_id
             LEFT JOIN locations p_o ON p_o.location_id = vr.pickup_location_id
             WHERE v.driver_id = ? AND vr.status = 'active'
             ORDER BY vr.start_date ASC
             LIMIT 1"
        );
        $stmt->execute([$driverId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Mga tapos nang rental ng customer na hindi pa ni na-r-rate - para sa
    // pahina ng "Rate" na katulad ng mga shared trips.
    public function getCompletedUnratedForCustomer($customerId) {
        $stmt = $this->db->prepare(
            "SELECT vr.*, v.plate_number, v.make, v.model,
                    CONCAT(r.origin_name, ' → ', r.destination_name) AS route_label,
                    CONCAT(du.first_name, ' ', du.last_name) AS driver_name
             FROM van_rentals vr
             JOIN vans v ON v.van_id = vr.van_id
             LEFT JOIN (
                 SELECT ro.route_id,
                        o.name AS origin_name,
                        d2.name AS destination_name
                 FROM routes ro
                 JOIN locations o ON o.location_id = ro.origin_location_id
                 JOIN locations d2 ON d2.location_id = ro.destination_location_id
             ) r ON r.route_id = vr.route_id
             LEFT JOIN drivers d ON d.driver_id = v.driver_id
             LEFT JOIN users du ON du.user_id = d.user_id
             LEFT JOIN ratings rt ON rt.rental_id = vr.rental_id
             WHERE vr.customer_id = ?
               AND vr.status = 'completed'
               AND rt.rating_id IS NULL
             ORDER BY vr.end_date DESC"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Aktibong rental ng customer - para sa dashboard (katulad ng
    // getActiveBookingForCustomer ng shared booking)
    public function getActiveForCustomer($customerId) {
        $stmt = $this->db->prepare(
            "SELECT vr.*, v.driver_id, v.plate_number, v.make, v.model,
                    CONCAT(drv_u.first_name, ' ', drv_u.last_name) AS driver_name,
                    drv_u.phone AS driver_phone,
                    drv_u.profile_picture AS driver_photo,
                    COALESCE(p_o.latitude, r.origin_lat) AS pickup_lat,
                    COALESCE(p_o.longitude, r.origin_lng) AS pickup_lng,
                    r.dest_lat AS dropoff_lat, r.dest_lng AS dropoff_lng,
                    CONCAT(r.origin_name, ' → ', r.destination_name) AS route_label,
                    l.name AS pickup_name
             FROM van_rentals vr
             JOIN vans v ON v.van_id = vr.van_id
             LEFT JOIN drivers d ON d.driver_id = v.driver_id
             LEFT JOIN users drv_u ON drv_u.user_id = d.user_id
             LEFT JOIN (
                 SELECT ro.route_id,
                        o.name AS origin_name, o.latitude AS origin_lat, o.longitude AS origin_lng,
                        d2.name AS destination_name, d2.latitude AS dest_lat, d2.longitude AS dest_lng
                 FROM routes ro
                 JOIN locations o ON o.location_id = ro.origin_location_id
                 JOIN locations d2 ON d2.location_id = ro.destination_location_id
             ) r ON r.route_id = vr.route_id
             LEFT JOIN locations l ON l.location_id = vr.pickup_location_id
             LEFT JOIN locations p_o ON p_o.location_id = vr.pickup_location_id
             WHERE vr.customer_id = ? AND vr.status = 'active'
             ORDER BY vr.start_date ASC
             LIMIT 1"
        );
        $stmt->execute([$customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Pagtatapos ng rental (katulad ng endTrip) - mawawala na ito sa mga
    // aktibong listahan at lilipat sa kasaysayan bilang 'completed'.
    public function markCompleted($rentalId) {
        $stmt = $this->db->prepare(
            "UPDATE van_rentals SET status = 'completed' WHERE rental_id = ? AND status = 'active'"
        );
        $stmt->execute([$rentalId]);
        return $stmt->rowCount() > 0;
    }

    // Kanselasyon ng customer - pending o confirmed pa lang (hindi pa
    // na-pick up ng driver). Ang mga 'cancelled' na rental ay hindi na
    // kinukwenta sa hasConflict, kaya muli na namang available ang van.
    public function cancelForCustomer($rentalId, $customerId) {
        $stmt = $this->db->prepare(
            "UPDATE van_rentals SET status = 'cancelled'
             WHERE rental_id = ? AND customer_id = ? AND status IN ('pending', 'confirmed')"
        );
        $stmt->execute([$rentalId, $customerId]);
        return $stmt->rowCount() > 0;
    }

    // Kasaysayan ng mga rental ng customer - completed, cancelled, o
    // lumipas na ang petsa (katulad ng fallback ng history ng shared bookings)
    public function getHistoryForCustomer($customerId) {
        $stmt = $this->db->prepare(
            "SELECT vr.*, v.plate_number, v.make, v.model,
                    CONCAT(r.origin_name, ' → ', r.destination_name) AS route_label,
                    l.name AS pickup_name
             FROM van_rentals vr
             JOIN vans v ON v.van_id = vr.van_id
             LEFT JOIN (
                 SELECT ro.route_id,
                        o.name AS origin_name,
                        d2.name AS destination_name
                 FROM routes ro
                 JOIN locations o ON o.location_id = ro.origin_location_id
                 JOIN locations d2 ON d2.location_id = ro.destination_location_id
             ) r ON r.route_id = vr.route_id
             LEFT JOIN locations l ON l.location_id = vr.pickup_location_id
             WHERE vr.customer_id = ?
               AND (vr.status IN ('completed', 'cancelled') OR vr.end_date < CURDATE())
             ORDER BY vr.end_date DESC"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Kasaysayan ng mga rental ng mga van ng driver - completed, cancelled,
    // o lumipas na ang petsa
    public function getHistoryForDriver($driverId) {
        $stmt = $this->db->prepare(
            "SELECT vr.*, v.plate_number, v.make, v.model,
                    CONCAT(cu.first_name, ' ', cu.last_name) AS customer_name
             FROM van_rentals vr
             JOIN vans v ON v.van_id = vr.van_id
             JOIN customers c ON c.customer_id = vr.customer_id
             JOIN users cu ON cu.user_id = c.user_id
             WHERE v.driver_id = ?
               AND (vr.status IN ('completed', 'cancelled') OR vr.end_date < CURDATE())
             ORDER BY vr.end_date DESC"
        );
        $stmt->execute([$driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
