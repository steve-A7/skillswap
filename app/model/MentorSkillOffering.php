<?php

class MentorSkillOffering {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function create($mentorId, $categoryId, $skillCode, $skillTitle, $difficulty, $prerequisites, $price, $description, $offeredFor, $offeringPicturePath, $status = "available") {
        $sql = "INSERT INTO mentor_skill_offerings(mentor_id, category_id, skill_code, skill_title, difficulty, prerequisites, current_status, price, description, offered_for, offering_picture_path) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;

        $mid = (int)$mentorId;
        $cid = (int)$categoryId;
        $scode = $skillCode;
        $stitle = $skillTitle;
        $diff = $difficulty;
        $preq = $prerequisites;
        $st = $status;
        $pr = (float)$price;
        $desc = $description;
        $of = (int)$offeredFor;
        $pic = $offeringPicturePath;

        $stmt->bind_param("iisssssdsis", $mid, $cid, $scode, $stitle, $diff, $preq, $st, $pr, $desc, $of, $pic);
        $ok = $stmt->execute();
        $newId = $ok ? (int)$this->conn->insert_id : 0;
        $stmt->close();

        return $newId;
    }

    public function getById($offeringId) {
        $sql = "SELECT offering_id, mentor_id, category_id, skill_code, skill_title, difficulty, prerequisites, current_status, price, description, offered_for, created_at, updated_at, offering_picture_path FROM mentor_skill_offerings WHERE offering_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;

        $oid = (int)$offeringId;
        $stmt->bind_param("i", $oid);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows < 1) {
            $stmt->close();
            return null;
        }

        $id = null;
        $mentorIdDb = null;
        $categoryIdDb = null;
        $skillCodeDb = null;
        $skillTitleDb = null;
        $difficultyDb = null;
        $prereqDb = null;
        $statusDb = null;
        $priceDb = null;
        $descDb = null;
        $offeredForDb = null;
        $createdAt = null;
        $updatedAt = null;
        $picPath = null;

        $stmt->bind_result($id, $mentorIdDb, $categoryIdDb, $skillCodeDb, $skillTitleDb, $difficultyDb, $prereqDb, $statusDb, $priceDb, $descDb, $offeredForDb, $createdAt, $updatedAt, $picPath);
        $stmt->fetch();
        $stmt->close();

        return [
            "offering_id" => (int)$id,
            "mentor_id" => (int)$mentorIdDb,
            "category_id" => (int)$categoryIdDb,
            "skill_code" => $skillCodeDb,
            "skill_title" => $skillTitleDb,
            "difficulty" => $difficultyDb,
            "prerequisites" => $prereqDb,
            "current_status" => $statusDb,
            "price" => $priceDb !== null ? (float)$priceDb : null,
            "description" => $descDb,
            "offered_for" => (int)$offeredForDb,
            "created_at" => $createdAt,
            "updated_at" => $updatedAt,
            "offering_picture_path" => $picPath
        ];
    }

    public function listByMentor($mentorId) {
        $sql = "SELECT offering_id, mentor_id, category_id, skill_code, skill_title, difficulty, prerequisites, current_status, price, description, offered_for, created_at, updated_at, offering_picture_path FROM mentor_skill_offerings WHERE mentor_id = ? ORDER BY offering_id DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $mid = (int)$mentorId;
        $stmt->bind_param("i", $mid);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["offering_id"] = (int)$row["offering_id"];
                $row["mentor_id"] = (int)$row["mentor_id"];
                $row["category_id"] = (int)$row["category_id"];
                $row["price"] = (float)$row["price"];
                $row["offered_for"] = (int)$row["offered_for"];
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }

    public function updateStatus($offeringId, $status) {
        $sql = "UPDATE mentor_skill_offerings SET current_status = ? WHERE offering_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $oid = (int)$offeringId;
        $st = $status;

        $stmt->bind_param("si", $st, $oid);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function updateOffering($offeringId, $skillTitle, $difficulty, $prerequisites, $price, $description, $offeredFor, $categoryId) {
        $sql = "UPDATE mentor_skill_offerings SET skill_title = ?, difficulty = ?, prerequisites = ?, price = ?, description = ?, offered_for = ?, category_id = ? WHERE offering_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $oid = (int)$offeringId;
        $stitle = $skillTitle;
        $diff = $difficulty;
        $preq = $prerequisites;
        $pr = (float)$price;
        $desc = $description;
        $of = (int)$offeredFor;
        $cid = (int)$categoryId;

        $stmt->bind_param("sssdsiii", $stitle, $diff, $preq, $pr, $desc, $of, $cid, $oid);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function deleteById($offeringId) {
        $sql = "DELETE FROM mentor_skill_offerings WHERE offering_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $oid = (int)$offeringId;
        $stmt->bind_param("i", $oid);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}
