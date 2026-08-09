<?php

class Rating extends Model {
    protected $table = 'ratings';

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO ratings
                (booking_id, customer_id, driver_id, van_id, overall_rating,
                 punctuality_rating, cleanliness_rating, driving_rating, comment)
             VALUES
                (:booking_id, :customer_id, :driver_id, :van_id, :overall_rating,
                 :punctuality_rating, :cleanliness_rating, :driving_rating, :comment)"
        );
        $stmt->execute([
            'booking_id' => $data['booking_id'],
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
}