<?php

class QrBooking extends Model {
    protected $table = 'qr_bookings';

    // Ang raw token ay iniimbak DIN sa qr_image_path column bilang backup para
    // makita ito ng customer sa susunod pang pagbisita. Ito ay technically hindi
    // kasing-ligtas ng purong hash-only storage, pero kinakailangan para gumana
    // ang "ipakita ang QR code ko" nang paulit-ulit nang hindi gumagawa ng bagong
    // token kada pagkakataon (na sisira sa dating naka-print o naka-screenshot
    // na QR). Ang column na ito ay hindi ginagamit sa verification - ang
    // token_hash pa rin ang tanging batayan doon.
    public function getOrCreate($bookingId) {
        $stmt = $this->db->prepare("SELECT * FROM qr_bookings WHERE booking_id = ?");
        $stmt->execute([$bookingId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $existing['raw_token'] = $existing['qr_image_path']; // dito naka-store ang plain token
            return $existing;
        }

        $token = bin2hex(random_bytes(16));
        $tokenHash = hash('sha256', $token);

        $stmt = $this->db->prepare(
            "INSERT INTO qr_bookings (booking_id, token_hash, qr_image_path, expires_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))"
        );
        $stmt->execute([$bookingId, $tokenHash, $token]);

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