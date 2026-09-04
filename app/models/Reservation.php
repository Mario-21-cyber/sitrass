<?php

class Reservation extends Model {
    protected $table = 'reservations';

    public function create($data) {
        $referenceCode = $this->generateReferenceCode();

        $settingModel = new SystemSetting();
        $depositPercentage = (float)$settingModel->getValue('deposit_percentage', 30);

        $stmt = $this->db->prepare(
            "INSERT INTO reservations
                (reference_code, customer_id, booking_type, passenger_count, total_amount,
                 deposit_percentage, deposit_required, hold_expires_at)
             VALUES
                (:reference_code, :customer_id, :booking_type, :passenger_count, :total_amount,
                 :deposit_percentage, :deposit_required, DATE_ADD(NOW(), INTERVAL 2 HOUR))"
        );
        $stmt->execute([
            'reference_code' => $referenceCode,
            'customer_id' => $data['customer_id'],
            'booking_type' => $data['booking_type'],
            'passenger_count' => $data['passenger_count'],
            'total_amount' => $data['total_amount'],
            'deposit_percentage' => $depositPercentage,
            'deposit_required' => round($data['total_amount'] * ($depositPercentage / 100), 2),
        ]);

        return [
            'reservation_id' => $this->db->lastInsertId(),
            'reference_code' => $referenceCode,
        ];
    }

    protected function generateReferenceCode() {
        // Format: SIT-YYYYMMDD-XXXX (random 4-char suffix)
        do {
            $code = 'SIT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE reference_code = ?");
            $stmt->execute([$code]);
            $exists = $stmt->fetchColumn() > 0;
        } while ($exists);

        return $code;
    }

        public function getByCustomerId($customerId) {
        // Kung walang totoong booking record ang isang reservation (dapat
        // bihira na lang mangyari ito), gamitin na lang ang petsa ng
        // paggawa ng reservation bilang fallback, para may makita pa ring
        // makabuluhang petsa sa halip na blangko. Manatiling ORDER BY
        // created_at DESC para tama ang pagkakasunud-sunod - pinakabago
        // muna, tulad ng dapat.
        $stmt = $this->db->prepare(
            "SELECT vrs.*,
                    COALESCE(
                        (SELECT b.travel_date FROM bookings b
                           WHERE b.reservation_id = vrs.reservation_id
                           ORDER BY b.travel_date ASC LIMIT 1),
                        DATE(vrs.created_at)
                    ) AS first_travel_date,
                    (SELECT b.booking_id FROM bookings b
                       WHERE b.reservation_id = vrs.reservation_id
                       ORDER BY b.travel_date ASC LIMIT 1) AS first_booking_id,
                    (SELECT b.status FROM bookings b
                       WHERE b.reservation_id = vrs.reservation_id
                       ORDER BY b.travel_date ASC LIMIT 1) AS first_booking_status
             FROM vw_reservation_summary vrs
             WHERE vrs.customer_id = ?
             ORDER BY vrs.created_at DESC"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByReferenceCode($code, $customerId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM vw_reservation_summary WHERE reference_code = ? AND customer_id = ?"
        );
        $stmt->execute([$code, $customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getById($id) {
    $stmt = $this->db->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function cancel($reservationId, $cancelledByUserId, $reason) {
    $stmt = $this->db->prepare(
        "UPDATE reservations
         SET status = 'cancelled', cancelled_at = NOW(), cancelled_by = ?, cancellation_reason = ?
         WHERE reservation_id = ?"
    );
    $stmt->execute([$cancelledByUserId, $reason, $reservationId]);
}
public function getStats() {
    $stmt = $this->db->query(
        "SELECT
            COUNT(*) AS total_reservations,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
         FROM reservations"
    );
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

}