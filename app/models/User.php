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

    public function phoneExists($phone) {
    $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    return $stmt->fetchColumn() > 0;
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
public function recordFailedAttempt($userId) {
    $stmt = $this->db->prepare(
        "UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE user_id = ?"
    );
    $stmt->execute([$userId]);

    // Kunin ang bagong bilang para malaman kung kailangan nang i-lock
    $stmt = $this->db->prepare("SELECT failed_login_attempts FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $attempts = $stmt->fetchColumn();

    if ($attempts >= 5) {
        $stmt = $this->db->prepare(
            "UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
    }
}

public function resetFailedAttempts($userId) {
    $stmt = $this->db->prepare(
        "UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE user_id = ?"
    );
    $stmt->execute([$userId]);
}

public function isLocked($user) {
    return !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
}
public function createResetToken($userId) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    $stmt = $this->db->prepare(
        "INSERT INTO password_resets (user_id, token_hash, expires_at, ip_address)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), ?)"
    );
    $stmt->execute([$userId, $tokenHash, $_SERVER['REMOTE_ADDR'] ?? null]);

    // Ibabalik ang RAW token (hindi ang hash) para ilagay sa link
    return $token;
}

public function verifyResetToken($token) {
    $tokenHash = hash('sha256', $token);

    $stmt = $this->db->prepare(
        "SELECT pr.*, u.user_id, u.email FROM password_resets pr
         JOIN users u ON u.user_id = pr.user_id
         WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()"
    );
    $stmt->execute([$tokenHash]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function resetPassword($userId, $newPassword, $resetTokenHash) {
    $this->db->beginTransaction();
    try {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

        $stmt = $this->db->prepare("UPDATE password_resets SET used_at = NOW() WHERE token_hash = ?");
        $stmt->execute([$resetTokenHash]);

        $this->db->commit();
        return true;
    } catch (Exception $e) {
        $this->db->rollBack();
        return false;
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
public function getStats() {
    $stmt = $this->db->query(
        "SELECT
            SUM(CASE WHEN role = 'customer' AND status = 'active' THEN 1 ELSE 0 END) AS active_customers,
            SUM(CASE WHEN role = 'driver' AND status = 'active' THEN 1 ELSE 0 END) AS active_drivers,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_accounts
         FROM users WHERE deleted_at IS NULL"
    );
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
}