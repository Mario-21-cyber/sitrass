<?php

class AuditLog extends Model {
    protected $table = 'audit_logs';

    public function log($userId, $action, $entityType = null, $entityId = null) {
        $stmt = $this->db->prepare(
            "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public function getRecent($limit = 100) {
        $stmt = $this->db->query(
            "SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name
             FROM audit_logs al
             LEFT JOIN users u ON u.user_id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT " . (int)$limit
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}