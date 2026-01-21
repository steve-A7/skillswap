<?php

class Session {
    private $db;
    private $conn;

    public function __construct($db) {
        $this->db = $db;
        $this->conn = $this->db->getConnection();
    }

    public function create($bookingId, $scheduledStart, $durationMinutes, $meetingMode = "both", $meetingLink = null, $status = "scheduled") {
        $sql = "INSERT INTO sessions(booking_id, scheduled_start, duration_minutes, meeting_mode, meeting_link, session_status) VALUES (?,?,?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;

        $bid = (int)$bookingId;
        $start = $scheduledStart;
        $dur = (int)$durationMinutes;
        $mode = $meetingMode;
        $link = $meetingLink;
        $st = $status;

        $stmt->bind_param("isisss", $bid, $start, $dur, $mode, $link, $st);
        $ok = $stmt->execute();
        $newId = $ok ? (int)$this->conn->insert_id : 0;
        $stmt->close();
        return $newId;
    }

    public function getById($sessionId) {
        $sql = "SELECT session_id, booking_id, scheduled_start, duration_minutes, meeting_mode, meeting_link, session_status, created_at, updated_at FROM sessions WHERE session_id = ? LIMIT 1";
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

        $id = null;
        $bookingIdDb = null;
        $scheduledStart = null;
        $durationMinutes = null;
        $meetingMode = null;
        $meetingLink = null;
        $status = null;
        $createdAt = null;
        $updatedAt = null;

        $stmt->bind_result($id, $bookingIdDb, $scheduledStart, $durationMinutes, $meetingMode, $meetingLink, $status, $createdAt, $updatedAt);
        $stmt->fetch();
        $stmt->close();

        return [
            "session_id" => (int)$id,
            "booking_id" => (int)$bookingIdDb,
            "scheduled_start" => $scheduledStart,
            "duration_minutes" => (int)$durationMinutes,
            "meeting_mode" => $meetingMode,
            "meeting_link" => $meetingLink,
            "session_status" => $status,
            "created_at" => $createdAt,
            "updated_at" => $updatedAt
        ];
    }

    public function getByBookingId($bookingId) {
        $sql = "SELECT session_id, booking_id, scheduled_start, duration_minutes, meeting_mode, meeting_link, session_status, created_at, updated_at FROM sessions WHERE booking_id = ? LIMIT 1";
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

        $id = null;
        $bookingIdDb = null;
        $scheduledStart = null;
        $durationMinutes = null;
        $meetingMode = null;
        $meetingLink = null;
        $status = null;
        $createdAt = null;
        $updatedAt = null;

        $stmt->bind_result($id, $bookingIdDb, $scheduledStart, $durationMinutes, $meetingMode, $meetingLink, $status, $createdAt, $updatedAt);
        $stmt->fetch();
        $stmt->close();

        return [
            "session_id" => (int)$id,
            "booking_id" => (int)$bookingIdDb,
            "scheduled_start" => $scheduledStart,
            "duration_minutes" => (int)$durationMinutes,
            "meeting_mode" => $meetingMode,
            "meeting_link" => $meetingLink,
            "session_status" => $status,
            "created_at" => $createdAt,
            "updated_at" => $updatedAt
        ];
    }

    public function updateStatus($sessionId, $status) {
        $sql = "UPDATE sessions SET session_status = ? WHERE session_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $sid = (int)$sessionId;
        $st = $status;
        $stmt->bind_param("si", $st, $sid);

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateMeetingLink($sessionId, $meetingLink) {
        $sql = "UPDATE sessions SET meeting_link = ? WHERE session_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $sid = (int)$sessionId;
        $link = $meetingLink;
        $stmt->bind_param("si", $link, $sid);

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
