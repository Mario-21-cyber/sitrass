<?php

class Booking extends Model {
    protected $table = 'bookings';

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO bookings
                (reservation_id, schedule_id, route_id, van_id, driver_id, booking_mode,
                 pickup_location_id, dropoff_location_id, travel_date, pickup_time,
                 seats_booked, fare_amount)
             VALUES
                (:reservation_id, :schedule_id, :route_id, :van_id, :driver_id, :booking_mode,
                 :pickup_location_id, :dropoff_location_id, :travel_date, :pickup_time,
                 :seats_booked, :fare_amount)"
        );
        $stmt->execute([
            'reservation_id' => $data['reservation_id'],
            'schedule_id' => $data['schedule_id'],
            'route_id' => $data['route_id'],
            'van_id' => $data['van_id'],
            'driver_id' => $data['driver_id'],
            'booking_mode' => $data['booking_mode'],
            'pickup_location_id' => $data['pickup_location_id'],
            'dropoff_location_id' => $data['dropoff_location_id'],
            'travel_date' => $data['travel_date'],
            'pickup_time' => $data['pickup_time'],
            'seats_booked' => $data['seats_booked'],
            'fare_amount' => $data['fare_amount'],
        ]);
        return $this->db->lastInsertId();
    }
}