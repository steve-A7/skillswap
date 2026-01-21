<?php

class MentorCategory {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function add($mentorId, $categoryId) {
        $sql = "INSERT IGNORE INTO mentor_categories(mentor_id, category_id) VALUES (?,?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $mid = (int)$mentorId;
        $cid = (int)$categoryId;

        $stmt->bind_param("ii", $mid, $cid);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

        public function remove($mentorId, $categoryId) {
        $sql = "DELETE FROM mentor_categories WHERE mentor_id = ? AND category_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $mid = (int)$mentorId;
        $cid = (int)$categoryId;

        $stmt->bind_param("ii", $mid, $cid);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

        public function exists($mentorId, $categoryId) {
        $sql = "SELECT mentor_id FROM mentor_categories WHERE mentor_id = ? AND category_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $mid = (int)$mentorId;
        $cid = (int)$categoryId;

        $stmt->bind_param("ii", $mid, $cid);
        $stmt->execute();
        $stmt->store_result();
        $found = $stmt->num_rows > 0;
        $stmt->close();

        return $found;
    }
}
