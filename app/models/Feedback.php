<?php

class Feedback extends Model {
    protected $table = 'feedback';

    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO feedback (user_id, category, subject, message, contact_email)
             VALUES (:user_id, :category, :subject, :message, :contact_email)"
        );
        $stmt->execute([
            'user_id' => $data['user_id'] ?: null,
            'category' => $data['category'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'contact_email' => $data['contact_email'] ?: null,
        ]);
        return $this->db->lastInsertId();
    }

    public function getAll() {
        $stmt = $this->db->query(
            "SELECT f.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name
             FROM feedback f
             LEFT JOIN users u ON u.user_id = f.user_id
             ORDER BY f.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function respond($feedbackId, $response, $handledByUserId) {
        $stmt = $this->db->prepare(
            "UPDATE feedback SET response = ?, status = 'resolved', handled_by = ?, resolved_at = NOW() WHERE feedback_id = ?"
        );
        $stmt->execute([$response, $handledByUserId, $feedbackId]);
    }

    public function updateStatus($feedbackId, $status) {
        $stmt = $this->db->prepare(
            "UPDATE feedback SET status = ? WHERE feedback_id = ?"
        );
        $stmt->execute([$status, $feedbackId]);
    }
}