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
public function getActiveWithDynamicText() {
    $settingModel = new SystemSetting();
    $depositPercentage = $settingModel->getValue('deposit_percentage', 30);

    // Malinis na display: "30" sa halip na "30.00" kung walang desimal
    $displayPercentage = rtrim(rtrim(number_format($depositPercentage, 2), '0'), '.');

    $methods = $this->getActive();

    foreach ($methods as &$method) {
        if ($method['instructions']) {
            $method['instructions'] = str_replace(
                '{deposit_percentage}',
                $displayPercentage,
                $method['instructions']
            );
        }
    }

    return $methods;
}
}