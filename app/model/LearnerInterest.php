<?php

class LearnerInterest {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function add($learnerId, $categoryId) {
        $sql = "INSERT IGNORE INTO learner_interests(learner_id, category_id) VALUES (?,?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $lid = (int)$learnerId;
        $cid = (int)$categoryId;

        $stmt->bind_param("ii", $lid, $cid);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function remove($learnerId, $categoryId) {
        $sql = "DELETE FROM learner_interests WHERE learner_id = ? AND category_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $lid = (int)$learnerId;
        $cid = (int)$categoryId;

        $stmt->bind_param("ii", $lid, $cid);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function listByLearner($learnerId) {
        $sql = "SELECT learner_id, category_id, created_at
                FROM learner_interests
                WHERE learner_id = ?
                ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $lid = (int)$learnerId;
        $stmt->bind_param("i", $lid);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["learner_id"] = (int)$row["learner_id"];
                $row["category_id"] = (int)$row["category_id"];
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }

    public function listCategoryIdsByLearner($learnerId) {
        $items = $this->listByLearner($learnerId);
        $ids = [];
        foreach ($items as $it) {
            $ids[] = (int)$it["category_id"];
        }
        return array_values(array_unique($ids));
    }
}
