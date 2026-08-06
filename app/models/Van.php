<?php

class Van extends Model {
    protected $table = 'vans';

    public function getAll() {
        $stmt = $this->db->query(
            "SELECT * FROM vans WHERE deleted_at IS NULL ORDER BY created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function plateExists($plate) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM vans WHERE plate_number = ?");
        $stmt->execute([$plate]);
        return $stmt->fetchColumn() > 0;
    }

    public function updateStatus($vanId, $status) {
        $stmt = $this->db->prepare(
            "UPDATE vans SET status = ? WHERE van_id = ?"
        );
        $stmt->execute([$status, $vanId]);
    }
}