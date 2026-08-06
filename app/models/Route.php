<?php

class Route extends Model {
    protected $table = 'routes';

    public function getAll() {
        $stmt = $this->db->query(
            "SELECT r.*, o.name AS origin_name, d.name AS destination_name
             FROM routes r
             JOIN locations o ON o.location_id = r.origin_location_id
             JOIN locations d ON d.location_id = r.destination_location_id
             WHERE r.is_active = 1
             ORDER BY r.route_code"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO routes (route_code, route_name, origin_location_id, destination_location_id,
                distance_km, estimated_duration_minutes, base_fare, fare_per_passenger, road_condition)
             VALUES (:route_code, :route_name, :origin_location_id, :destination_location_id,
                :distance_km, :estimated_duration_minutes, :base_fare, :fare_per_passenger, :road_condition)"
        );
        $stmt->execute([
            'route_code' => $data['route_code'],
            'route_name' => $data['route_name'],
            'origin_location_id' => $data['origin_location_id'],
            'destination_location_id' => $data['destination_location_id'],
            'distance_km' => $data['distance_km'] ?: 0,
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?: 0,
            'base_fare' => $data['base_fare'] ?: 0,
            'fare_per_passenger' => $data['fare_per_passenger'] ?: 0,
            'road_condition' => $data['road_condition'],
        ]);
        return $this->db->lastInsertId();
    }

    public function codeExists($code) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM routes WHERE route_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetchColumn() > 0;
    }
}