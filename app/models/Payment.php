<?php

class Payment extends Model {
    protected $table = 'payments';

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO payments
                (reservation_id, rental_id, method_id, payment_type, amount, reference_number, proof_image, paid_at)
             VALUES
                (:reservation_id, :rental_id, :method_id, :payment_type, :amount, :reference_number, :proof_image, NOW())"
        );
        $stmt->execute([
            'reservation_id' => $data['reservation_id'] ?? null,
            'rental_id' => $data['rental_id'] ?? null,
            'method_id' => $data['method_id'],
            'payment_type' => $data['payment_type'],
            'amount' => $data['amount'],
            'reference_number' => $data['reference_number'] ?: null,
            'proof_image' => $data['proof_image'] ?: null,
        ]);
        return $this->db->lastInsertId();
    }

        public function getPending() {
        // Admin verification queue:
        // - LAHAT ng GCash (online) payments - shared o rental man
        // - F2F (cash) rental payments para sa mga van na WALANG driver
        //   (fallback: kung walang driver ang van, admin ang mag-verify)
        $stmt = $this->db->query(
            "SELECT p.*, pm.method_name,
                    COALESCE(rs.reference_code, vr.reference_code) AS reference_code,
                    CASE
                        WHEN p.rental_id IS NOT NULL THEN CONCAT(u2.first_name, ' ', u2.last_name)
                        ELSE CONCAT(u.first_name, ' ', u.last_name)
                    END AS customer_name
             FROM payments p
             JOIN payment_methods pm ON pm.method_id = p.method_id
             LEFT JOIN reservations rs ON rs.reservation_id = p.reservation_id
             LEFT JOIN customers c ON c.customer_id = rs.customer_id
             LEFT JOIN users u ON u.user_id = c.user_id
             LEFT JOIN van_rentals vr ON vr.rental_id = p.rental_id
             LEFT JOIN vans v ON v.van_id = vr.van_id
             LEFT JOIN customers c2 ON c2.customer_id = vr.customer_id
             LEFT JOIN users u2 ON u2.user_id = c2.user_id
             WHERE p.status = 'pending'
               AND (
                   pm.is_online = 1
                   OR (pm.is_online = 0 AND p.rental_id IS NOT NULL AND v.driver_id IS NULL)
               )
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

            // GUARD: Para sa rental payments, suriin kung lalampas na sa total_price
            // ang pag-verify ng payment na ito. Kung oo, auto-reject na lang para
            // hindi mag-double-pay ang customer.
            if (!empty($payment['rental_id'])) {
                $stmt = $db->prepare("SELECT total_price FROM van_rentals WHERE rental_id = ?");
                $stmt->execute([$payment['rental_id']]);
                $totalPrice = (float)$stmt->fetchColumn();

                $sum = $db->prepare(
                    "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE rental_id = ? AND status = 'verified'"
                );
                $sum->execute([$payment['rental_id']]);
                $alreadyVerified = (float)$sum->fetchColumn();

                if ($alreadyVerified + (float)$payment['amount'] > $totalPrice + 0.005) {
                    // Lampas na — auto-reject ang duplicate payment
                    $stmt = $db->prepare(
                        "UPDATE payments SET status = 'rejected', rejection_reason = 'Duplicate payment - rental already fully paid' WHERE payment_id = ?"
                    );
                    $stmt->execute([$paymentId]);
                    $db->commit();
                    return false;
                }
            }

            $receiptNumber = 'RCT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

            $stmt = $db->prepare(
                "UPDATE payments SET status = 'verified', verified_by = ?, verified_at = NOW(), receipt_number = ?
                 WHERE payment_id = ?"
            );
            $stmt->execute([$verifiedByUserId, $receiptNumber, $paymentId]);

            // Ang rental payments ay walang reservation - laktawan ang mga
            // update sa reservation (hawak na ng VanRental::markPaid ang
            // payment_status/status ng rental pagkatapos ng verification).
            if (!empty($payment['reservation_id'])) {
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
            }

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
    // Dati itong SQL VIEW (vw_daily_revenue). Sa InfinityFree free hosting
    // ay bawal ang CREATE VIEW, kaya iniline na lang ang definition dito
    // bilang derived table - pareho ang laman, walang DB privilege na kailangan.
    $stmt = $this->db->query(
        "SELECT * FROM (
            SELECT DATE(p.verified_at) AS revenue_date,
                   pm.method_code,
                   COUNT(*) AS transaction_count,
                   SUM(CASE WHEN p.payment_type = 'refund' THEN -p.amount ELSE p.amount END) AS net_amount
             FROM payments p
             JOIN payment_methods pm ON pm.method_id = p.method_id
             WHERE p.status = 'verified' AND p.verified_at IS NOT NULL
             GROUP BY DATE(p.verified_at), pm.method_code
         ) AS vw_daily_revenue
         ORDER BY revenue_date DESC LIMIT " . (int)$days
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    public function getPendingCashBalanceForReservation($reservationId) {
        $stmt = $this->db->prepare(
            "SELECT p.*, pm.method_name, rs.reference_code, rs.customer_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS customer_name
             FROM payments p
             JOIN payment_methods pm ON pm.method_id = p.method_id
             JOIN reservations rs ON rs.reservation_id = p.reservation_id
             JOIN customers c ON c.customer_id = rs.customer_id
             JOIN users u ON u.user_id = c.user_id
             WHERE p.status = 'pending' AND pm.is_online = 0 AND p.payment_type = 'balance'
               AND p.reservation_id = ?
             ORDER BY p.created_at DESC
             LIMIT 1"
        );
        $stmt->execute([$reservationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Katulad ng getPendingCashBalanceForReservation - pero para sa rental:
    // pending na F2F (cash) payment ng isang van rental (deposit o balance).
    public function getPendingCashBalanceForRental($rentalId) {
        $stmt = $this->db->prepare(
            "SELECT p.*, pm.method_name, vr.reference_code, vr.customer_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS customer_name
             FROM payments p
             JOIN payment_methods pm ON pm.method_id = p.method_id
             JOIN van_rentals vr ON vr.rental_id = p.rental_id
             JOIN customers c ON c.customer_id = vr.customer_id
             JOIN users u ON u.user_id = c.user_id
             WHERE p.status = 'pending' AND pm.is_online = 0
                AND p.rental_id = ?
             ORDER BY p.created_at DESC
             LIMIT 1"
        );
        $stmt->execute([$rentalId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
        public function getVerifiedForReservation($reservationId) {
        $stmt = $this->db->prepare(
            "SELECT p.*, pm.method_name
             FROM payments p
             JOIN payment_methods pm ON pm.method_id = p.method_id
             WHERE p.reservation_id = ? AND p.status = 'verified'
             ORDER BY p.verified_at ASC"
        );
        $stmt->execute([$reservationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVerifiedForRental($rentalId) {
        $stmt = $this->db->prepare(
            "SELECT p.*, pm.method_name
             FROM payments p
             JOIN payment_methods pm ON pm.method_id = p.method_id
             WHERE p.rental_id = ? AND p.status = 'verified'
             ORDER BY p.verified_at ASC"
        );
        $stmt->execute([$rentalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Persistent list ng lahat ng pending F2F (cash) payments para sa
    // mga VAN RENTAL ng isang driver - katulad ng shared booking flow.
    // Kasama ang F2F deposit at balance (lahat ng F2F rental payments
    // ay sa driver na-verify, GCash naman sa admin).
    public function getPendingCashBalanceForRentalForDriver($driverId) {
        $stmt = $this->db->prepare(
            "SELECT p.*, pm.method_name, vr.reference_code, vr.customer_id,
                    vr.rental_id, vr.start_date, vr.end_date,
                    CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
                    v.plate_number, v.make, v.model
             FROM payments p
             JOIN payment_methods pm ON pm.method_id = p.method_id
             JOIN van_rentals vr ON vr.rental_id = p.rental_id
             JOIN vans v ON v.van_id = vr.van_id
             JOIN customers c ON c.customer_id = vr.customer_id
             JOIN users u ON u.user_id = c.user_id
             WHERE p.status = 'pending' AND pm.is_online = 0
               AND p.rental_id IS NOT NULL
               AND v.driver_id = ?
             ORDER BY p.created_at ASC"
        );
        $stmt->execute([$driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}