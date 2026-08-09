<?php

class Driver extends Model {
    protected $table = 'drivers';

    public function create($userId, $data) {
        $stmt = $this->db->prepare(
            "INSERT INTO drivers (user_id, license_number, license_expiry, years_experience, is_approved)
             VALUES (?, ?, ?, ?, 0)"
        );
        $stmt->execute([
            $userId,
            $data['license_number'],
            $data['license_expiry'],
            $data['years_experience'] ?: 0,
        ]);
        return $this->db->lastInsertId();
    }

    public function licenseExists($licenseNumber) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM drivers WHERE license_number = ?");
        $stmt->execute([$licenseNumber]);
        return $stmt->fetchColumn() > 0;
    }

    public function getByUserId($userId) {
        $stmt = $this->db->prepare("SELECT * FROM drivers WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function approve($driverId, $approvedByUserId) {
        $stmt = $this->db->prepare(
            "UPDATE drivers SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE driver_id = ?"
        );
        $stmt->execute([$approvedByUserId, $driverId]);
    }

    public function getAllApproved() {
        $stmt = $this->db->query(
            "SELECT d.*, u.first_name, u.last_name, u.email, u.phone
             FROM drivers d
             JOIN users u ON u.user_id = d.user_id
             WHERE d.is_approved = 1 AND u.deleted_at IS NULL
             ORDER BY u.first_name ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function updateLicenseImage($driverId, $path) {
    $stmt = $this->db->prepare(
        "UPDATE drivers SET license_image = ? WHERE driver_id = ?"
    );
    $stmt->execute([$path, $driverId]);
}
}