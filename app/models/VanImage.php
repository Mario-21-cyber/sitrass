<?php

class VanImage extends Model {
    protected $table = 'van_images';

    public function getByVanId($vanId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM van_images WHERE van_id = ? ORDER BY is_primary DESC, sort_order ASC"
        );
        $stmt->execute([$vanId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($vanId, $imagePath, $thumbnailPath, $isPrimary, $uploadedBy) {
        $stmt = $this->db->prepare(
            "INSERT INTO van_images (van_id, image_path, thumbnail_path, is_primary, uploaded_by)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$vanId, $imagePath, $thumbnailPath, $isPrimary ? 1 : 0, $uploadedBy]);
        return $this->db->lastInsertId();
    }

    public function clearPrimary($vanId) {
        $stmt = $this->db->prepare(
            "UPDATE van_images SET is_primary = 0 WHERE van_id = ?"
        );
        $stmt->execute([$vanId]);
    }

    public function countByVanId($vanId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM van_images WHERE van_id = ?");
        $stmt->execute([$vanId]);
        return $stmt->fetchColumn();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM van_images WHERE image_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM van_images WHERE image_id = ?");
        $stmt->execute([$id]);
    }
}