<?php

class Van extends Model {
    protected $table = 'vans';

    public function getAll() {
        // Fleet Overview: hindi kasama ang pending (nakabinbing approval
        // ng admin mula sa driver na nag-register)
        $stmt = $this->db->query(
            "SELECT * FROM vans WHERE deleted_at IS NULL AND status != 'pending' ORDER BY created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllByStatus($status) {
        $stmt = $this->db->prepare(
            "SELECT v.*, CONCAT(u.first_name, ' ', u.last_name) AS owner_name
             FROM vans v
             LEFT JOIN drivers d ON d.driver_id = v.driver_id
             LEFT JOIN users u ON u.user_id = d.user_id
             WHERE v.deleted_at IS NULL AND v.status = ?
             ORDER BY v.created_at DESC"
        );
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDriver($driverId) {
        $stmt = $this->db->prepare(
            "SELECT v.*,
                (SELECT vi.image_path FROM van_images vi
                  WHERE vi.van_id = v.van_id AND vi.is_primary = 1 LIMIT 1) AS primary_image
             FROM vans v
             WHERE v.driver_id = ? AND v.deleted_at IS NULL
             ORDER BY v.created_at DESC"
        );
        $stmt->execute([$driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Van na irehistro ng mismong driver - naka-pending muna hanggang
    // i-approve ng admin sa Pending Vans page.
    public function createForDriver($data, $driverId) {
        $stmt = $this->db->prepare(
            "INSERT INTO vans (plate_number, make, model, year_model, color, van_type, driver_id,
                seating_capacity, luggage_capacity, has_aircon, has_wifi, description,
                base_fare, fare_per_km, whole_van_day_rate, status)
             VALUES (:plate_number, :make, :model, :year_model, :color, :van_type, :driver_id,
                :seating_capacity, :luggage_capacity, :has_aircon, :has_wifi, :description,
                :base_fare, :fare_per_km, :whole_van_day_rate, 'pending')"
        );
        $stmt->execute([
            'plate_number' => $data['plate_number'],
            'make' => $data['make'],
            'model' => $data['model'],
            'year_model' => $data['year_model'] ?: null,
            'color' => $data['color'] ?: null,
            'van_type' => $data['van_type'],
            'driver_id' => $driverId,
            'seating_capacity' => $data['seating_capacity'],
            'luggage_capacity' => $data['luggage_capacity'] ?: 0,
            'has_aircon' => isset($data['has_aircon']) ? 1 : 0,
            'has_wifi' => isset($data['has_wifi']) ? 1 : 0,
            'description' => $data['description'] ?: null,
            'base_fare' => $data['base_fare'] ?: 0,
            'fare_per_km' => $data['fare_per_km'] ?: 0,
            'whole_van_day_rate' => $data['whole_van_day_rate'] ?: 0,
        ]);
        return $this->db->lastInsertId();
    }

    // Edit ng driver sa sarili niyang van (ownership-guarded)
    public function updateByOwner($vanId, $driverId, $data) {
        $stmt = $this->db->prepare(
            "UPDATE vans SET plate_number = :plate_number, make = :make, model = :model,
                year_model = :year_model, color = :color, van_type = :van_type,
                seating_capacity = :seating_capacity, luggage_capacity = :luggage_capacity,
                has_aircon = :has_aircon, has_wifi = :has_wifi, description = :description,
                base_fare = :base_fare, fare_per_km = :fare_per_km, whole_van_day_rate = :whole_van_day_rate
             WHERE van_id = :van_id AND driver_id = :driver_id AND deleted_at IS NULL"
        );
        $stmt->execute([
            'plate_number' => $data['plate_number'],
            'make' => $data['make'],
            'model' => $data['model'],
            'year_model' => $data['year_model'] ?: null,
            'color' => $data['color'] ?: null,
            'van_type' => $data['van_type'],
            'seating_capacity' => $data['seating_capacity'],
            'luggage_capacity' => $data['luggage_capacity'] ?: 0,
            'has_aircon' => isset($data['has_aircon']) ? 1 : 0,
            'has_wifi' => isset($data['has_wifi']) ? 1 : 0,
            'description' => $data['description'] ?: null,
            'base_fare' => $data['base_fare'] ?: 0,
            'fare_per_km' => $data['fare_per_km'] ?: 0,
            'whole_van_day_rate' => $data['whole_van_day_rate'] ?: 0,
            'van_id' => $vanId,
            'driver_id' => $driverId,
        ]);
        return $stmt->rowCount() > 0;
    }

    // Inline edit ng admin sa Fleet Overview (plate/van/type/seats)
    public function updateAdmin($vanId, $data) {
        $stmt = $this->db->prepare(
            "UPDATE vans SET plate_number = ?, make = ?, model = ?, van_type = ?, seating_capacity = ?
             WHERE van_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([
            $data['plate_number'], $data['make'], $data['model'],
            $data['van_type'], $data['seating_capacity'], $vanId
        ]);
    }

    public function softDelete($vanId) {
        $stmt = $this->db->prepare(
            "UPDATE vans SET deleted_at = NOW(), status = 'retired' WHERE van_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$vanId]);
    }

    public function getById($id) {
        $stmt = $this->db->prepare(
            "SELECT * FROM vans WHERE van_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO vans (plate_number, make, model, year_model, color, van_type,
                seating_capacity, luggage_capacity, has_aircon, has_wifi, description,
                base_fare, fare_per_km, whole_van_day_rate, status)
             VALUES (:plate_number, :make, :model, :year_model, :color, :van_type,
                :seating_capacity, :luggage_capacity, :has_aircon, :has_wifi, :description,
                :base_fare, :fare_per_km, :whole_van_day_rate, 'active')"
        );
        $stmt->execute([
            'plate_number' => $data['plate_number'],
            'make' => $data['make'],
            'model' => $data['model'],
            'year_model' => $data['year_model'] ?: null,
            'color' => $data['color'] ?: null,
            'van_type' => $data['van_type'],
            'seating_capacity' => $data['seating_capacity'],
            'luggage_capacity' => $data['luggage_capacity'] ?: 0,
            'has_aircon' => isset($data['has_aircon']) ? 1 : 0,
            'has_wifi' => isset($data['has_wifi']) ? 1 : 0,
            'description' => $data['description'] ?: null,
            'base_fare' => $data['base_fare'] ?: 0,
            'fare_per_km' => $data['fare_per_km'] ?: 0,
            'whole_van_day_rate' => $data['whole_van_day_rate'] ?: 0,
        ]);
        return $this->db->lastInsertId();
    }

    public function plateExists($plate, $excludeVanId = null) {
        if ($excludeVanId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM vans WHERE plate_number = ? AND van_id != ?");
            $stmt->execute([$plate, $excludeVanId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM vans WHERE plate_number = ?");
            $stmt->execute([$plate]);
        }
        return $stmt->fetchColumn() > 0;
    }

    public function updateStatus($vanId, $status) {
        $stmt = $this->db->prepare(
            "UPDATE vans SET status = ? WHERE van_id = ?"
        );
        $stmt->execute([$status, $vanId]);
    }

    public function updateRentPrice($vanId, $price) {
        $stmt = $this->db->prepare(
            "UPDATE vans SET whole_van_day_rate = ? WHERE van_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$price, $vanId]);
    }

    // Lahat ng van para sa "Rent a Van" - may flags na agad kung may
    // upcoming na biyahe, aktwal na biyahe, o sabayang rental ang van.
    public function getForRent() {
        $stmt = $this->db->query(
            "SELECT v.*,
                CONCAT(du.first_name, ' ', du.last_name) AS driver_name,
                (SELECT vi.image_path FROM van_images vi
                  WHERE vi.van_id = v.van_id AND vi.is_primary = 1 LIMIT 1) AS primary_image,
                EXISTS(SELECT 1 FROM trip_schedules ts
                  WHERE ts.van_id = v.van_id AND ts.status = 'scheduled'
                    AND ts.departure_date >= CURDATE()
                    AND NOT EXISTS(SELECT 1 FROM bookings b2
                      WHERE b2.schedule_id = ts.schedule_id
                        AND b2.status IN ('en_route','completed'))) AS has_upcoming_trip,
                EXISTS(SELECT 1 FROM bookings b
                  WHERE b.van_id = v.van_id AND b.status IN ('accepted','en_route')) AS has_active_booking,
                EXISTS(SELECT 1 FROM van_rentals vr
                  WHERE vr.van_id = v.van_id AND vr.status = 'active') AS has_active_rental,
                EXISTS(SELECT 1 FROM van_rentals vr
                  WHERE vr.van_id = v.van_id AND vr.status IN ('pending','confirmed')
                    AND vr.end_date > CURDATE()) AS has_future_rental
             FROM vans v
             LEFT JOIN drivers d ON d.driver_id = v.driver_id
             LEFT JOIN users du ON du.user_id = d.user_id
             WHERE v.deleted_at IS NULL
             ORDER BY v.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getForRentById($vanId) {
        $stmt = $this->db->prepare(
            "SELECT v.*,
                CONCAT(du.first_name, ' ', du.last_name) AS driver_name,
                (SELECT vi.image_path FROM van_images vi
                  WHERE vi.van_id = v.van_id AND vi.is_primary = 1 LIMIT 1) AS primary_image,
                EXISTS(SELECT 1 FROM trip_schedules ts
                  WHERE ts.van_id = v.van_id AND ts.status = 'scheduled'
                    AND ts.departure_date >= CURDATE()
                    AND NOT EXISTS(SELECT 1 FROM bookings b2
                      WHERE b2.schedule_id = ts.schedule_id
                        AND b2.status IN ('en_route','completed'))) AS has_upcoming_trip,
                EXISTS(SELECT 1 FROM bookings b
                  WHERE b.van_id = v.van_id AND b.status IN ('accepted','en_route')) AS has_active_booking,
                EXISTS(SELECT 1 FROM van_rentals vr
                  WHERE vr.van_id = v.van_id AND vr.status = 'active') AS has_active_rental,
                EXISTS(SELECT 1 FROM van_rentals vr
                  WHERE vr.van_id = v.van_id AND vr.status IN ('pending','confirmed')
                    AND vr.end_date > CURDATE()) AS has_future_rental
             FROM vans v
             LEFT JOIN drivers d ON d.driver_id = v.driver_id
             LEFT JOIN users du ON du.user_id = d.user_id
             WHERE v.van_id = ? AND v.deleted_at IS NULL"
        );
        $stmt->execute([$vanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStats() {
    $stmt = $this->db->query(
        "SELECT
            COUNT(*) AS total_vans,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_count,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_count
         FROM vans WHERE deleted_at IS NULL"
    );
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
}