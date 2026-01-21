<?php

class OfferingTimeSlot {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function create($offeringId, $dayOfWeek, $startTime, $endTime) {
        $sql = "INSERT INTO offering_time_slots(offering_id, day_of_week, start_time, end_time) VALUES (?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;

        $oid = (int)$offeringId;
        $dow = $dayOfWeek;
        $start = $startTime;
        $end = $endTime;

        $stmt->bind_param("isss", $oid, $dow, $start, $end);
        $ok = $stmt->execute();
        $newId = $ok ? (int)$this->conn->insert_id : 0;
        $stmt->close();
        return $newId;
    }

    public function listByOffering($offeringId) {
        $sql = "SELECT slot_id, offering_id, day_of_week, start_time, end_time, created_at FROM offering_time_slots WHERE offering_id = ? ORDER BY day_of_week ASC, start_time ASC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $oid = (int)$offeringId;
        $stmt->bind_param("i", $oid);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["slot_id"] = (int)$row["slot_id"];
                $row["offering_id"] = (int)$row["offering_id"];
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }

    public function update($slotId, $dayOfWeek, $startTime, $endTime) {
        $sql = "UPDATE offering_time_slots SET day_of_week = ?, start_time = ?, end_time = ? WHERE slot_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $sid = (int)$slotId;
        $dow = $dayOfWeek;
        $start = $startTime;
        $end = $endTime;

        $stmt->bind_param("sssi", $dow, $start, $end, $sid);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function deleteById($slotId) {
        $sql = "DELETE FROM offering_time_slots WHERE slot_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $sid = (int)$slotId;
        $stmt->bind_param("i", $sid);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
