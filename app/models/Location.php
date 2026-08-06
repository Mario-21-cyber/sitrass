<?php

class Location extends Model {
    protected $table = 'locations';

    public function getAll() {
        $stmt = $this->db->query(
            "SELECT * FROM locations WHERE is_active = 1 ORDER BY municipality, sort_order"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO locations (name, location_type, category, barangay, municipality, latitude, longitude, landmark)
             VALUES (:name, :location_type, :category, :barangay, :municipality, :latitude, :longitude, :landmark)"
        );
        $stmt->execute([
            'name' => $data['name'],
            'location_type' => $data['location_type'],
            'category' => $data['category'],
            'barangay' => $data['barangay'] ?: null,
            'municipality' => $data['municipality'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'landmark' => $data['landmark'] ?: null,
        ]);
        return $this->db->lastInsertId();
    }
}