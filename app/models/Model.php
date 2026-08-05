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
}