<?php

class User extends Model {
    protected $table = 'users';

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE deleted_at IS NULL");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? AND deleted_at IS NULL");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
    $stmt = $this->db->prepare(
        "INSERT INTO users (uuid, role, first_name, last_name, email, phone, password_hash, status)
         VALUES (UUID(), :role, :first_name, :last_name, :email, :phone, :password_hash, 'pending')"
    );
    $stmt->execute([
        'role' => $data['role'],
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
    ]);
    return $this->db->lastInsertId();
}

public function emailExists($email) {
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn() > 0;
}
}