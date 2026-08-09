<?php

class SystemSetting extends Model {
    protected $table = 'system_settings';

    public function getAllGrouped() {
        $stmt = $this->db->query(
            "SELECT * FROM system_settings ORDER BY group_name, setting_key"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['group_name']][] = $row;
        }
        return $grouped;
    }

    public function update($settingId, $value, $updatedByUserId) {
        $stmt = $this->db->prepare(
            "UPDATE system_settings SET setting_value = ?, updated_by = ? WHERE setting_id = ?"
        );
        $stmt->execute([$value, $updatedByUserId, $settingId]);
    }
}