<?php

class Model {
    protected $db;

    public function __construct() {
        $config = require __DIR__ . '/../../config/database.php';
        
        $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'] . ';charset=' . $config['charset'];
        
        try {
            $this->db = new PDO($dsn, $config['user'], $config['pass']);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public function getPending() {
    $stmt = $this->db->query(
        "SELECT * FROM users WHERE status = 'pending' AND deleted_at IS NULL ORDER BY created_at ASC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function approve($userId) {
    $stmt = $this->db->prepare(
        "UPDATE users SET status = 'active', email_verified_at = NOW() WHERE user_id = ? AND status = 'pending'"
    );
    $stmt->execute([$userId]);
    return $stmt->rowCount() > 0;
}
}