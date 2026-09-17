<?php

class TripSchedule extends Model {
    protected $table = 'trip_schedules';

    public function getAll() {
        // Hindi kasama ang mga schedule na may biyahe nang COMPLETED -
        // tapos na ang trabaho diyan kahit hindi napuno ang upuan,
        // kaya wala nang silbi na ipakita pa sa admin.
        $stmt = $this->db->query(
            "SELECT ts.*, r.route_code, r.route_name, v.plate_number, v.make, v.model,
                    CONCAT(u.first_name, ' ', u.last_name) AS driver_name
             FROM trip_schedules ts
             JOIN routes r ON r.route_id = ts.route_id
             JOIN vans v ON v.van_id = ts.van_id
             LEFT JOIN drivers d ON d.driver_id = ts.driver_id
             LEFT JOIN users u ON u.user_id = d.user_id
             WHERE NOT EXISTS (
                 SELECT 1 FROM bookings b
                 WHERE b.schedule_id = ts.schedule_id AND b.status = 'completed'
             )
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

    // May bayad nang booking sa schedule na ito? (deposit man o buo)
    // Kung mayroon, hindi na puwedeng kanselahin ng admin ang schedule.
    public function hasPaidBooking($scheduleId) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM bookings b
             JOIN reservations rs ON rs.reservation_id = b.reservation_id
             WHERE b.schedule_id = ? AND b.status IN ('pending','accepted','en_route')
               AND rs.payment_status IN ('paid','partially_paid')"
        );
        $stmt->execute([$scheduleId]);
        return $stmt->fetchColumn() > 0;
    }
    // Dati itong SQL VIEW sa database (vw_available_schedules). Sa
    // InfinityFree free hosting ay wala ang CREATE VIEW privilege, kaya
    // iniline na lang natin ang definition bilang derived table. Pareho
    // lang ang ibinabalik - hindi na lang umaasa sa DB privileges.
    protected function availableSchedulesSql() {
        return "(SELECT ts.schedule_id, ts.departure_date, ts.departure_time,
                ts.estimated_arrival, ts.available_seats, ts.total_seats,
                ts.fare_per_seat, ts.booking_mode,
                r.route_id, r.route_code, r.route_name, r.distance_km,
                r.estimated_duration_minutes,
                origin.location_id AS origin_id, origin.name AS origin_name,
                dest.location_id AS destination_id, dest.name AS destination_name,
                v.van_id, v.plate_number, v.make, v.model, v.van_type,
                v.has_aircon, v.has_wifi, d.driver_id,
                CONCAT(du.first_name, ' ', du.last_name) AS driver_name,
                d.rating_average, d.rating_count,
                (SELECT vi.image_path FROM van_images vi
                  WHERE vi.van_id = v.van_id AND vi.is_primary = 1 LIMIT 1) AS primary_image
             FROM trip_schedules ts
             JOIN routes r ON r.route_id = ts.route_id AND r.is_active = 1
             JOIN locations origin ON origin.location_id = r.origin_location_id
             JOIN locations dest ON dest.location_id = r.destination_location_id
             JOIN vans v ON v.van_id = ts.van_id AND v.status = 'active' AND v.deleted_at IS NULL
             LEFT JOIN drivers d ON d.driver_id = ts.driver_id
             LEFT JOIN users du ON du.user_id = d.user_id
             WHERE ts.status = 'scheduled'
               AND ts.available_seats > 0
               AND TIMESTAMP(ts.departure_date, ts.departure_time) > NOW()
               AND NOT EXISTS(SELECT 1 FROM bookings b
                 WHERE b.schedule_id = ts.schedule_id
                   AND b.status IN ('en_route','completed'))) AS vw_available_schedules";
    }

    public function search($origin = null, $destination = null, $date = null) {
    $sql = "SELECT * FROM " . $this->availableSchedulesSql() . " WHERE 1=1";
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
    $sql = "SELECT * FROM " . $this->availableSchedulesSql() . " WHERE route_id = ?";
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