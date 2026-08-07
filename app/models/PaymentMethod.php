<?php

class PaymentMethod extends Model {
    protected $table = 'payment_methods';

    public function getActive() {
        $stmt = $this->db->query(
            "SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM payment_methods WHERE method_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function update($id, $data) {
    $stmt = $this->db->prepare(
        "UPDATE payment_methods
         SET account_name = ?, account_number = ?, instructions = ?
         WHERE method_id = ?"
    );
    $stmt->execute([
        $data['account_name'] ?: null,
        $data['account_number'] ?: null,
        $data['instructions'] ?: null,
        $id,
    ]);
}
}