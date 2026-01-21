<?php

class OfferingDurationOption {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function addDuration($offeringId, $durationMinutes) {
        $sql = "INSERT INTO offering_duration_options(offering_id, duration_minutes) VALUES (?,?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $oid = (int)$offeringId;
        $minutes = (int)$durationMinutes;

        $stmt->bind_param("ii", $oid, $minutes);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function removeDuration($offeringId, $durationMinutes) {
        $sql = "DELETE FROM offering_duration_options WHERE offering_id = ? AND duration_minutes = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $oid = (int)$offeringId;
        $minutes = (int)$durationMinutes;

        $stmt->bind_param("ii", $oid, $minutes);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function listByOffering($offeringId) {
        $sql = "SELECT offering_id, duration_minutes, created_at FROM offering_duration_options WHERE offering_id = ? ORDER BY duration_minutes ASC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $oid = (int)$offeringId;
        $stmt->bind_param("i", $oid);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["offering_id"] = (int)$row["offering_id"];
                $row["duration_minutes"] = (int)$row["duration_minutes"];
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }
}
