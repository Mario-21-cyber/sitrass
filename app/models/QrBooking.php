<?php

class QrBooking extends Model {
    protected $table = 'qr_bookings';

    // Ang laman ng QR ay ang Reference Code mismo ng booking - simple at
    // makabuluhan, madaling makilala ng customer at driver. Ang expiry ay
    // batay sa oras ng biyahe (hindi sa oras ng paggawa), dahil ginagawa na
    // natin ito agad noong mag-book pa lang, hindi lang kapag binuksan ng
    // customer ang QR page.
    public function getOrCreate($bookingId) {
        $stmt = $this->db->prepare("SELECT * FROM qr_bookings WHERE booking_id = ?");
        $stmt->execute([$bookingId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $existing['raw_token'] = $existing['qr_image_path'];
            return $existing;
        }

        $stmt = $this->db->prepare(
            "SELECT rs.reference_code, b.travel_date, b.pickup_time
             FROM bookings b
             JOIN reservations rs ON rs.reservation_id = b.reservation_id
             WHERE b.booking_id = ?"
        );
        $stmt->execute([$bookingId]);
        $bookingInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bookingInfo) {
            return null;
        }

        $token = $bookingInfo['reference_code'];
        $tokenHash = hash('sha256', $token);

        $stmt = $this->db->prepare(
            "INSERT INTO qr_bookings (booking_id, token_hash, qr_image_path, expires_at)
             VALUES (?, ?, ?, ADDTIME(TIMESTAMP(?, ?), '12:00:00'))"
        );
        $stmt->execute([$bookingId, $tokenHash, $token, $bookingInfo['travel_date'], $bookingInfo['pickup_time']]);

        return [
            'qr_id' => $this->db->lastInsertId(),
            'booking_id' => $bookingId,
            'token_hash' => $tokenHash,
            'status' => 'active',
            'raw_token' => $token,
        ];
    }

    public function getByToken($token) {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->db->prepare(
            "SELECT qb.*, b.travel_date, b.pickup_time, b.seats_booked, b.driver_id, b.status AS booking_status,
                    rs.reference_code, CONCAT(cu.first_name, ' ', cu.last_name) AS customer_name
             FROM qr_bookings qb
             JOIN bookings b ON b.booking_id = qb.booking_id
             JOIN reservations rs ON rs.reservation_id = b.reservation_id
             JOIN customers c ON c.customer_id = rs.customer_id
             JOIN users cu ON cu.user_id = c.user_id
             WHERE qb.token_hash = ?"
        );
        $stmt->execute([$tokenHash]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markScanned($qrId, $scannedByUserId) {
        $stmt = $this->db->prepare(
            "UPDATE qr_bookings
             SET status = 'used', scanned_at = NOW(), scanned_by = ?, scan_count = scan_count + 1
             WHERE qr_id = ?"
        );
        $stmt->execute([$scannedByUserId, $qrId]);
    }
}