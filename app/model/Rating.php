<?php

class Rating {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function create($sessionId, $offeringId, $mentorId, $ratedByLearnerId, $ratingValue, $review = null, $recommend = "yes") {
        $sql = "INSERT INTO rating(session_id, offering_id, mentor_id, rated_by_learner_id, rating, review, recommend) VALUES (?,?,?,?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;

        $sid = (int)$sessionId;
        $oid = (int)$offeringId;
        $mid = (int)$mentorId;
        $lid = (int)$ratedByLearnerId;
        $val = (int)$ratingValue;
        $rev = $review;
        $rec = $recommend;

        $stmt->bind_param("iiiiiss", $sid, $oid, $mid, $lid, $val, $rev, $rec);
        $ok = $stmt->execute();
        $newId = $ok ? (int)$this->conn->insert_id : 0;
        $stmt->close();
        return $newId;
    }

    public function getBySessionId($sessionId) {
        $sql = "SELECT rating_id, session_id, offering_id, mentor_id, rated_by_learner_id, rating, review, recommend, created_at FROM rating WHERE session_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;

        $sid = (int)$sessionId;
        $stmt->bind_param("i", $sid);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows < 1) {
            $stmt->close();
            return null;
        }

        $ratingId = null;
        $sessionIdDb = null;
        $offeringIdDb = null;
        $mentorIdDb = null;
        $learnerIdDb = null;
        $ratingVal = null;
        $review = null;
        $recommend = null;
        $createdAt = null;

        $stmt->bind_result($ratingId, $sessionIdDb, $offeringIdDb, $mentorIdDb, $learnerIdDb, $ratingVal, $review, $recommend, $createdAt);
        $stmt->fetch();
        $stmt->close();

        return [
            "rating_id" => (int)$ratingId,
            "session_id" => (int)$sessionIdDb,
            "offering_id" => (int)$offeringIdDb,
            "mentor_id" => (int)$mentorIdDb,
            "rated_by_learner_id" => (int)$learnerIdDb,
            "rating" => (int)$ratingVal,
            "review" => $review,
            "recommend" => $recommend,
            "created_at" => $createdAt
        ];
    }

    public function listByLearner($learnerId, $limit = 200) {
        $sql = "SELECT rating_id, session_id, offering_id, mentor_id, rated_by_learner_id, rating, review, recommend, created_at FROM rating WHERE rated_by_learner_id = ? ORDER BY rating_id DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $lid = (int)$learnerId;
        $lim = (int)$limit;

        $stmt->bind_param("ii", $lid, $lim);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["rating_id"] = (int)$row["rating_id"];
                $row["session_id"] = (int)$row["session_id"];
                $row["offering_id"] = (int)$row["offering_id"];
                $row["mentor_id"] = (int)$row["mentor_id"];
                $row["rated_by_learner_id"] = (int)$row["rated_by_learner_id"];
                $row["rating"] = (int)$row["rating"];
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }

    public function listByMentor($mentorId, $limit = 200) {
        $sql = "SELECT rating_id, session_id, offering_id, mentor_id, rated_by_learner_id, rating, review, recommend, created_at FROM rating WHERE mentor_id = ? ORDER BY rating_id DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $mid = (int)$mentorId;
        $lim = (int)$limit;

        $stmt->bind_param("ii", $mid, $lim);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["rating_id"] = (int)$row["rating_id"];
                $row["session_id"] = (int)$row["session_id"];
                $row["offering_id"] = (int)$row["offering_id"];
                $row["mentor_id"] = (int)$row["mentor_id"];
                $row["rated_by_learner_id"] = (int)$row["rated_by_learner_id"];
                $row["rating"] = (int)$row["rating"];
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }
}
