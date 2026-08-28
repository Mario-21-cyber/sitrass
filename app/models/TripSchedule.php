<?php

class TripSchedule extends Model {
    protected $table = 'trip_schedules';

    public function getAll() {
        $stmt = $this->db->query(
            "SELECT ts.*, r.route_code, r.route_name, v.plate_number, v.make, v.model,
                    CONCAT(u.first_name, ' ', u.last_name) AS driver_name
             FROM trip_schedules ts
             JOIN routes r ON r.route_id = ts.route_id
             JOIN vans v ON v.van_id = ts.van_id
             LEFT JOIN drivers d ON d.driver_id = ts.driver_id
             LEFT JOIN users u ON u.user_id = d.user_id
             ORDER BY ts.departure_date DESC, ts.departure_time DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO trip_schedules (route_id, van_id, driver_id, departure_date, departure_time,
                estimated_arrival, total_seats, available_seats, fare_per_seat, booking_mode, created_by)
             VALUES (:route_id, :van_id, :driver_id, :departure_date, :departure_time,
                :estimated_arrival, :total_seats, :total_seats, :fare_per_seat, :booking_mode, :created_by)"
        );
        $stmt->execute([
            'route_id' => $data['route_id'],
            'van_id' => $data['van_id'],
            'driver_id' => $data['driver_id'] ?: null,
            'departure_date' => $data['departure_date'],
            'departure_time' => $data['departure_time'],
            'estimated_arrival' => $data['estimated_arrival'] ?: null,
            'total_seats' => $data['total_seats'],
            'fare_per_seat' => $data['fare_per_seat'],
            'booking_mode' => $data['booking_mode'],
            'created_by' => $data['created_by'],
        ]);
        return $this->db->lastInsertId();
    }

        public function slotTaken($vanId, $date, $time) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM trip_schedules WHERE van_id = ? AND departure_date = ? AND departure_time = ?"
        );
        $stmt->execute([$vanId, $date, $time]);
        return $stmt->fetchColumn() > 0;
    }

    // Hindi lang exact time match ang tinitignan - kinukuwenta rin natin ang
    // tantiyang tagal ng bawat biyahe (batay sa ruta), para hindi din maka-
    // gawa ng schedule na nag-o-overlap sa oras, kahit magkaiba ang exact
    // na oras ng alis.
    public function hasVanConflict($vanId, $date, $time, $durationMinutes, $excludeScheduleId = null) {
        $sql = "SELECT ts.departure_time, r.estimated_duration_minutes
                FROM trip_schedules ts
                JOIN routes r ON r.route_id = ts.route_id
                WHERE ts.van_id = ? AND ts.departure_date = ? AND ts.status != 'cancelled'";
        $params = [$vanId, $date];
        if ($excludeScheduleId) {
            $sql .= " AND ts.schedule_id != ?";
            $params[] = $excludeScheduleId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->rangesOverlap($stmt->fetchAll(PDO::FETCH_ASSOC), $time, $durationMinutes);
    }

    public function hasDriverConflict($driverId, $date, $time, $durationMinutes, $excludeScheduleId = null) {
        if (empty($driverId)) {
            return false;
        }
        $sql = "SELECT ts.departure_time, r.estimated_duration_minutes
                FROM trip_schedules ts
                JOIN routes r ON r.route_id = ts.route_id
                WHERE ts.driver_id = ? AND ts.departure_date = ? AND ts.status != 'cancelled'";
        $params = [$driverId, $date];
        if ($excludeScheduleId) {
            $sql .= " AND ts.schedule_id != ?";
            $params[] = $excludeScheduleId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->rangesOverlap($stmt->fetchAll(PDO::FETCH_ASSOC), $time, $durationMinutes);
    }

    protected function rangesOverlap($existingSchedules, $newTime, $newDurationMinutes) {
        $newStart = strtotime($newTime);
        $newEnd = $newStart + (($newDurationMinutes ?: 60) * 60);

        foreach ($existingSchedules as $row) {
            $existStart = strtotime($row['departure_time']);
            $existEnd = $existStart + (((int)$row['estimated_duration_minutes'] ?: 60) * 60);

            // May overlap kung nagsisimula ang isa bago pa matapos ang isa pa.
            if ($newStart < $existEnd && $existStart < $newEnd) {
                return true;
            }
        }
        return false;
    }

    public function cancel($scheduleId, $reason) {
        $stmt = $this->db->prepare(
            "UPDATE trip_schedules SET status = 'cancelled', cancellation_reason = ? WHERE schedule_id = ?"
        );
        $stmt->execute([$reason, $scheduleId]);
    }
    public function search($origin = null, $destination = null, $date = null) {
    $sql = "SELECT * FROM vw_available_schedules WHERE 1=1";
    $params = [];

    if ($origin) {
        $sql .= " AND origin_id = ?";
        $params[] = $origin;
    }
    if ($destination) {
        $sql .= " AND destination_id = ?";
        $params[] = $destination;
    }
    if ($date) {
        $sql .= " AND departure_date = ?";
        $params[] = $date;
    }

    $sql .= " ORDER BY departure_date ASC, departure_time ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getById($id) {
    $stmt = $this->db->prepare("SELECT * FROM trip_schedules WHERE schedule_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Atomic na pagbawas ng available_seats. Ang WHERE clause mismo ang
// nagsisilbing proteksyon: kung 0 na ang natitirang upuan, walang row
// na mata-tamaan, kaya mag-re-return ng 0 rows affected sa halip na
// pumunta sa negatibong bilang.
public function decrementSeats($scheduleId, $seatsToBook) {
    $stmt = $this->db->prepare(
        "UPDATE trip_schedules
         SET available_seats = available_seats - ?
         WHERE schedule_id = ? AND available_seats >= ?"
    );
    $stmt->execute([$seatsToBook, $scheduleId, $seatsToBook]);
    return $stmt->rowCount() > 0;
}
// Kabaligtaran ng decrementSeats() - ginagamit kapag nagkansela o nag-reschedule
public function incrementSeats($scheduleId, $seatsToRestore) {
    $stmt = $this->db->prepare(
        "UPDATE trip_schedules
         SET available_seats = LEAST(available_seats + ?, total_seats)
         WHERE schedule_id = ?"
    );
    $stmt->execute([$seatsToRestore, $scheduleId]);
}

public function getByRoute($routeId, $excludeScheduleId = null) {
    $sql = "SELECT * FROM vw_available_schedules WHERE route_id = ?";
    $params = [$routeId];

    if ($excludeScheduleId) {
        $sql .= " AND schedule_id != ?";
        $params[] = $excludeScheduleId;
    }

    $sql .= " ORDER BY departure_date ASC, departure_time ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}