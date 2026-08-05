<?php

class Customer extends Model {
    protected $table = 'customers';

    public function create($userId) {
        $stmt = $this->db->prepare("INSERT INTO customers (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        return $this->db->lastInsertId();
    }
}