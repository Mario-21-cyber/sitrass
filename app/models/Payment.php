<?php

class Payment extends Model {
    protected $table = 'payments';

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO payments
                (reservation_id, method_id, payment_type, amount, reference_number, proof_image, paid_at)
             VALUES
                (:reservation_id, :method_id, :payment_type, :amount, :reference_number, :proof_image, NOW())"
        );
        $stmt->execute([
            'reservation_id' => $data['reservation_id'],
            'method_id' => $data['method_id'],
            'payment_type' => $data['payment_type'],
            'amount' => $data['amount'],
            'reference_number' => $data['reference_number'] ?: null,
            'proof_image' => $data['proof_image'] ?: null,
        ]);
        return $this->db->lastInsertId();
    }

        public function getPending() {
        // Hindi na kasama dito ang F2F balance payments - dito na sila naka-
        // assign sa driver na mag-veverify, hindi na sa admin.
        $stmt = $this->db->query(
            "SELECT p.*, pm.method_name, rs.reference_code, rs.customer_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS customer_name
             FROM payments p
             JOIN payment_methods pm ON pm.method_id = p.method_id
             JOIN reservations rs ON rs.reservation_id = p.reservation_id
             JOIN customers c ON c.customer_id = rs.customer_id
             JOIN users u ON u.user_id = c.user_id
             WHERE p.status = 'pending'
               AND NOT (pm.is_online = 0 AND p.payment_type = 'balance')
             ORDER BY p.created_at ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingCashBalanceForDriver($driverId) {
        $stmt = $this->db->prepare(
            "SELECT p.*, pm.method_name, rs.reference_code, rs.customer_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS customer_name
             FROM payments p
             JOIN payment_methods pm ON pm.method_id = p.method_id
             JOIN reservations rs ON rs.reservation_id = p.reservation_id
             JOIN customers c ON c.customer_id = rs.customer_id
             JOIN users u ON u.user_id = c.user_id
             JOIN bookings b ON b.reservation_id = rs.reservation_id
             WHERE p.status = 'pending' AND pm.is_online = 0 AND p.payment_type = 'balance'
               AND b.driver_id = ?
             GROUP BY p.payment_id
             ORDER BY p.created_at ASC"
        );
        $stmt->execute([$driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE payment_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function verify($paymentId, $verifiedByUserId) {
        $db = $this->db;
        $db->beginTransaction();

        try {
            $payment = $this->getById($paymentId);
            if (!$payment || $payment['status'] !== 'pending') {
                $db->rollBack();
                return false;
            }

            $receiptNumber = 'RCT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

            $stmt = $db->prepare(
                "UPDATE payments SET status = 'verified', verified_by = ?, verified_at = NOW(), receipt_number = ?
                 WHERE payment_id = ?"
            );
            $stmt->execute([$verifiedByUserId, $receiptNumber, $paymentId]);

            // I-update ang reservation: idagdag ang bayad sa amount_paid, i-recalculate ang payment_status
            $stmt = $db->prepare(
                "UPDATE reservations SET amount_paid = amount_paid + ? WHERE reservation_id = ?"
            );
            $stmt->execute([$payment['amount'], $payment['reservation_id']]);

            $stmt = $db->prepare(
                "SELECT total_amount, amount_paid, deposit_required FROM reservations WHERE reservation_id = ?"
            );
            $stmt->execute([$payment['reservation_id']]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reservation['amount_paid'] >= $reservation['total_amount']) {
                $newPaymentStatus = 'paid';
            } elseif ($reservation['amount_paid'] >= $reservation['deposit_required']) {
                $newPaymentStatus = 'partially_paid';
            } else {
                $newPaymentStatus = 'partially_paid';
            }

            $newStatus = $reservation['amount_paid'] >= $reservation['deposit_required'] ? 'confirmed' : 'pending';

            $stmt = $db->prepare(
                "UPDATE reservations SET payment_status = ?, status = ?,
                    confirmed_at = CASE WHEN ? = 'confirmed' THEN NOW() ELSE confirmed_at END
                 WHERE reservation_id = ?"
            );
            $stmt->execute([$newPaymentStatus, $newStatus, $newStatus, $payment['reservation_id']]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public function reject($paymentId, $reason) {
        $stmt = $this->db->prepare(
            "UPDATE payments SET status = 'rejected', rejection_reason = ? WHERE payment_id = ? AND status = 'pending'"
        );
        $stmt->execute([$reason, $paymentId]);
        return $stmt->rowCount() > 0;
    }
    public function referenceExists($methodId, $referenceNumber) {
    if (empty($referenceNumber)) {
        return false;
    }
    $stmt = $this->db->prepare(
        "SELECT COUNT(*) FROM payments WHERE method_id = ? AND reference_number = ?"
    );
    $stmt->execute([$methodId, $referenceNumber]);
    return $stmt->fetchColumn() > 0;
}
public function getRevenueStats() {
    $stmt = $this->db->query(
        "SELECT
            COALESCE(SUM(CASE WHEN status = 'verified' THEN amount ELSE 0 END), 0) AS total_verified,
            COALESCE(SUM(CASE WHEN status = 'verified' AND DATE(verified_at) = CURDATE() THEN amount ELSE 0 END), 0) AS today_verified,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count
         FROM payments"
    );
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getDailyRevenue($days = 7) {
    $stmt = $this->db->query(
        "SELECT * FROM vw_daily_revenue ORDER BY revenue_date DESC LIMIT " . (int)$days
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}