<?php

class Reservation extends Model {
    protected $table = 'reservations';

    public function create($data) {
        $referenceCode = $this->generateReferenceCode();

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
            'deposit_percentage' => 30.00,
            'deposit_required' => round($data['total_amount'] * 0.30, 2),
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
        $stmt = $this->db->prepare(
            "SELECT * FROM vw_reservation_summary WHERE customer_id = ? ORDER BY created_at DESC"
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