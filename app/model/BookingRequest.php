<?php

class BookingRequest {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function create($offeringId, $learnerId, $p) {
        $sql = "INSERT INTO booking_request(offering_id, requested_learner_id, learner_pref, requested_slot_id, booked_day_of_week, booked_start_time, booked_end_time, booked_duration_minutes, message, preferred_time, request_type, previous_session_id, request_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;

        $oid = (int)$offeringId;
        $lid = (int)$learnerId;
        $learnerPref = $p["learner_pref"] ?? "both";
        $requestedSlotId = isset($p["requested_slot_id"]) && $p["requested_slot_id"] !== "" ? (int)$p["requested_slot_id"] : null;
        $dow = $p["booked_day_of_week"] ?? null;
        $start = $p["booked_start_time"] ?? null;
        $end = $p["booked_end_time"] ?? null;
        $dur = isset($p["booked_duration_minutes"]) && $p["booked_duration_minutes"] !== "" ? (int)$p["booked_duration_minutes"] : null;
        $message = $p["message"] ?? null;
        $prefTime = $p["preferred_time"] ?? null;
        $reqType = $p["request_type"] ?? "initial";
        $prevSessionId = isset($p["previous_session_id"]) && $p["previous_session_id"] !== "" ? (int)$p["previous_session_id"] : null;
        $status = $p["request_status"] ?? "pending";

        $stmt->bind_param(
            "iisisssisssis",
            $oid,
            $lid,
            $learnerPref,
            $requestedSlotId,
            $dow,
            $start,
            $end,
            $dur,
            $message,
            $prefTime,
            $reqType,
            $prevSessionId,
            $status
        );

        $ok = $stmt->execute();
        $newId = $ok ? (int)$this->conn->insert_id : 0;
        $stmt->close();
        return $newId;
    }

    public function getById($bookingId) {
        $sql = "SELECT booking_id, offering_id, requested_learner_id, learner_pref, requested_slot_id, booked_day_of_week, booked_start_time, booked_end_time, booked_duration_minutes, message, preferred_time, request_type, previous_session_id, request_status, created_at, updated_at FROM booking_request WHERE booking_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;

        $bid = (int)$bookingId;
        $stmt->bind_param("i", $bid);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows < 1) {
            $stmt->close();
            return null;
        }

        $bookingIdDb = null;
        $offeringIdDb = null;
        $learnerIdDb = null;
        $learnerPref = null;
        $requestedSlotId = null;
        $dow = null;
        $start = null;
        $end = null;
        $dur = null;
        $message = null;
        $prefTime = null;
        $reqType = null;
        $prevSessionId = null;
        $status = null;
        $createdAt = null;
        $updatedAt = null;

        $stmt->bind_result(
            $bookingIdDb,
            $offeringIdDb,
            $learnerIdDb,
            $learnerPref,
            $requestedSlotId,
            $dow,
            $start,
            $end,
            $dur,
            $message,
            $prefTime,
            $reqType,
            $prevSessionId,
            $status,
            $createdAt,
            $updatedAt
        );
        $stmt->fetch();
        $stmt->close();

        return [
            "booking_id" => (int)$bookingIdDb,
            "offering_id" => (int)$offeringIdDb,
            "requested_learner_id" => (int)$learnerIdDb,
            "learner_pref" => $learnerPref,
            "requested_slot_id" => $requestedSlotId !== null ? (int)$requestedSlotId : null,
            "booked_day_of_week" => $dow,
            "booked_start_time" => $start,
            "booked_end_time" => $end,
            "booked_duration_minutes" => $dur !== null ? (int)$dur : null,
            "message" => $message,
            "preferred_time" => $prefTime,
            "request_type" => $reqType,
            "previous_session_id" => $prevSessionId !== null ? (int)$prevSessionId : null,
            "request_status" => $status,
            "created_at" => $createdAt,
            "updated_at" => $updatedAt
        ];
    }

    public function listByLearner($learnerId) {
        $sql = "SELECT booking_id, offering_id, requested_learner_id, learner_pref, requested_slot_id, booked_day_of_week, booked_start_time, booked_end_time, booked_duration_minutes, message, preferred_time, request_type, previous_session_id, request_status, created_at, updated_at FROM booking_request WHERE requested_learner_id = ? ORDER BY booking_id DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $lid = (int)$learnerId;
        $stmt->bind_param("i", $lid);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["booking_id"] = (int)$row["booking_id"];
                $row["offering_id"] = (int)$row["offering_id"];
                $row["requested_learner_id"] = (int)$row["requested_learner_id"];
                $row["requested_slot_id"] = $row["requested_slot_id"] !== null ? (int)$row["requested_slot_id"] : null;
                $row["booked_duration_minutes"] = $row["booked_duration_minutes"] !== null ? (int)$row["booked_duration_minutes"] : null;
                $row["previous_session_id"] = $row["previous_session_id"] !== null ? (int)$row["previous_session_id"] : null;
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }

    public function listByOffering($offeringId) {
        $sql = "SELECT booking_id, offering_id, requested_learner_id, learner_pref, requested_slot_id, booked_day_of_week, booked_start_time, booked_end_time, booked_duration_minutes, message, preferred_time, request_type, previous_session_id, request_status, created_at, updated_at FROM booking_request WHERE offering_id = ? ORDER BY booking_id DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];

        $oid = (int)$offeringId;
        $stmt->bind_param("i", $oid);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row["booking_id"] = (int)$row["booking_id"];
                $row["offering_id"] = (int)$row["offering_id"];
                $row["requested_learner_id"] = (int)$row["requested_learner_id"];
                $row["requested_slot_id"] = $row["requested_slot_id"] !== null ? (int)$row["requested_slot_id"] : null;
                $row["booked_duration_minutes"] = $row["booked_duration_minutes"] !== null ? (int)$row["booked_duration_minutes"] : null;
                $row["previous_session_id"] = $row["previous_session_id"] !== null ? (int)$row["previous_session_id"] : null;
                $rows[] = $row;
            }
        }

        $stmt->close();
        return $rows;
    }

    public function updateStatus($bookingId, $status) {
        $sql = "UPDATE booking_request SET request_status = ? WHERE booking_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $bid = (int)$bookingId;
        $st = $status;
        $stmt->bind_param("si", $st, $bid);

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function setBookedDetails($bookingId, $dayOfWeek, $startTime, $endTime, $durationMinutes, $requestedSlotId = null) {
        $sql = "UPDATE booking_request SET requested_slot_id = ?, booked_day_of_week = ?, booked_start_time = ?, booked_end_time = ?, booked_duration_minutes = ? WHERE booking_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $bid = (int)$bookingId;
        $slotId = $requestedSlotId !== null ? (int)$requestedSlotId : null;
        $dow = $dayOfWeek;
        $start = $startTime;
        $end = $endTime;
        $dur = $durationMinutes !== null ? (int)$durationMinutes : null;

        $stmt->bind_param("isssii", $slotId, $dow, $start, $end, $dur, $bid);

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function setPreviousSession($bookingId, $previousSessionId) {
        $sql = "UPDATE booking_request SET previous_session_id = ? WHERE booking_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $bid = (int)$bookingId;
        $sid = $previousSessionId !== null ? (int)$previousSessionId : null;

        $stmt->bind_param("ii", $sid, $bid);

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
