<?php
include "../Model/DatabaseConnection.php";
session_start();

header("Content-Type: application/json; charset=UTF-8");

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
$userId = $_SESSION["user_id"] ?? null;

if (!$isLoggedIn || !$userId) {
    echo json_encode(["ok" => false, "message" => "Not logged in"]);
    exit();
}

date_default_timezone_set("Asia/Dhaka");

$db = new DatabaseConnection();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(["ok" => false, "message" => "Database connection failed"]);
    exit();
}


function normPath($p) {
    if (!$p) return "";
    return str_replace("\\", "/", $p);
}

function makeAssetUrl($path) {
    $path = trim((string)$path);
    if ($path === "") return "";

    if (stripos($path, "http://") === 0 || stripos($path, "https://") === 0) {
        return $path;
    }

    $path = normPath($path);

    if (strpos($path, "../../") === 0) return $path;

    return "../../" . ltrim($path, "/");
}

function getMentorIdFromUser($conn, $userId) {
    $sql = "SELECT mentor_id FROM mentor_profiles WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    return $row ? (int)$row["mentor_id"] : 0;
}

function randomMeetLink() {
    $letters = "abcdefghijklmnopqrstuvwxyz";
    $p1 = "";
    $p2 = "";
    $p3 = "";
    for ($i = 0; $i < 3; $i++) $p1 .= $letters[rand(0, 25)];
    for ($i = 0; $i < 4; $i++) $p2 .= $letters[rand(0, 25)];
    for ($i = 0; $i < 3; $i++) $p3 .= $letters[rand(0, 25)];
    return "https://meet.google.com/" . $p1 . "-" . $p2 . "-" . $p3;
}

function nextDateTimeForDay($dayOfWeek, $startTime) {


    if (!$dayOfWeek || !$startTime) {
        return (new DateTime())->format("Y-m-d H:i:s");
    }

    $map = [
        "sun" => "Sunday",
        "mon" => "Monday",
        "tue" => "Tuesday",
        "wed" => "Wednesday",
        "thu" => "Thursday",
        "fri" => "Friday",
        "sat" => "Saturday",
    ];

    $dayKey = strtolower(trim($dayOfWeek));
    if (!isset($map[$dayKey])) {
        return (new DateTime())->format("Y-m-d H:i:s");
    }

    $target = $map[$dayKey];

    $now = new DateTime();
    $candidate = new DateTime("next " . $target);

    $todayName = $now->format("l");
    if ($todayName === $target) {
        $candidate = new DateTime("today");
    }

    $candidate->setTime(
        (int)substr($startTime, 0, 2),
        (int)substr($startTime, 3, 2),
        (int)substr($startTime, 6, 2)
    );

    if ($candidate <= $now) {
        $candidate->modify("+7 days");
    }

    return $candidate->format("Y-m-d H:i:s");
}


$mentorId = getMentorIdFromUser($conn, (int)$userId);
if ($mentorId <= 0) {
    echo json_encode(["ok" => false, "message" => "Mentor profile not found"]);
    exit();
}

$action = $_GET["action"] ?? ($_POST["action"] ?? "list");

if ($action === "list") {

    $requests = [];
    $sqlReq = "
        SELECT 
            br.booking_id,
            br.offering_id,
            br.learner_pref,
            br.booked_day_of_week,
            br.booked_start_time,
            br.booked_end_time,
            br.booked_duration_minutes,

            mso.skill_title,
            mso.offering_picture_path,

            u.username AS learner_username,
            lp.profile_picture_path AS learner_picture

        FROM booking_request br
        INNER JOIN mentor_skill_offerings mso ON br.offering_id = mso.offering_id
        INNER JOIN learner_profiles lp ON br.requested_learner_id = lp.learner_id
        INNER JOIN users u ON lp.user_id = u.user_id

        WHERE mso.mentor_id = ?
          AND br.request_status = 'pending'
        ORDER BY br.created_at DESC
    ";

    $stmt = $conn->prepare($sqlReq);
    $stmt->bind_param("i", $mentorId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $requests[] = [
            "booking_id" => (int)$row["booking_id"],
            "offering_id" => (int)$row["offering_id"],
            "skill_title" => $row["skill_title"],
            "offering_picture_path" => makeAssetUrl($row["offering_picture_path"]),
            "learner_username" => $row["learner_username"],
            "learner_picture" => makeAssetUrl($row["learner_picture"]),

            "booked_day_of_week" => $row["booked_day_of_week"],
            "booked_start_time" => $row["booked_start_time"],
            "booked_end_time" => $row["booked_end_time"],
            "booked_duration_minutes" => $row["booked_duration_minutes"],

            "learner_preference" => $row["learner_pref"],
        ];
    }

    $stmt->close();

    $sessions = [];
    $sqlSes = "
        SELECT
            s.session_id,
            s.booking_id,
            s.scheduled_start,
            s.duration_minutes,
            s.meeting_mode,
            s.meeting_link,
            s.session_status,

            mso.skill_title,
            mso.offering_picture_path,

            u.username AS learner_username,
            lp.profile_picture_path AS learner_picture

        FROM sessions s
        INNER JOIN booking_request br ON s.booking_id = br.booking_id
        INNER JOIN mentor_skill_offerings mso ON br.offering_id = mso.offering_id
        INNER JOIN learner_profiles lp ON br.requested_learner_id = lp.learner_id
        INNER JOIN users u ON lp.user_id = u.user_id

        WHERE mso.mentor_id = ?
          AND s.session_status = 'scheduled'
        ORDER BY s.created_at DESC
    ";

    $stmt2 = $conn->prepare($sqlSes);
    $stmt2->bind_param("i", $mentorId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    while ($row = $res2->fetch_assoc()) {
        $sessions[] = [
            "session_id" => (int)$row["session_id"],
            "booking_id" => (int)$row["booking_id"],
            "scheduled_start" => $row["scheduled_start"],
            "duration_minutes" => (int)$row["duration_minutes"],
            "meeting_mode" => $row["meeting_mode"],
            "meeting_link" => $row["meeting_link"],
            "session_status" => $row["session_status"],

            "skill_title" => $row["skill_title"],
            "offering_picture_path" => makeAssetUrl($row["offering_picture_path"]),
            "learner_username" => $row["learner_username"],
            "learner_picture" => makeAssetUrl($row["learner_picture"]),
        ];
    }

    $stmt2->close();

    echo json_encode(["ok" => true, "requests" => $requests, "sessions" => $sessions]);
    exit();
}

if ($action === "accept") {

    $bookingId = (int)($_POST["booking_id"] ?? 0);
    if ($bookingId <= 0) {
        echo json_encode(["ok" => false, "message" => "Invalid booking id"]);
        exit();
    }

    $sqlCheck = "
        SELECT br.booking_id, br.learner_pref, br.booked_day_of_week, br.booked_start_time, br.booked_duration_minutes,
               br.request_status, br.offering_id
        FROM booking_request br
        INNER JOIN mentor_skill_offerings mso ON br.offering_id = mso.offering_id
        WHERE br.booking_id = ?
          AND mso.mentor_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sqlCheck);
    $stmt->bind_param("ii", $bookingId, $mentorId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(["ok" => false, "message" => "Booking not found"]);
        exit();
    }

    if ($row["request_status"] !== "pending") {
        echo json_encode(["ok" => false, "message" => "Request already processed"]);
        exit();
    }

    $meetingMode = $row["learner_pref"] ?: "both";
    $duration = (int)($row["booked_duration_minutes"] ?: 60);
    $offeringId = (int)$row["offering_id"];

    $scheduledStart = nextDateTimeForDay($row["booked_day_of_week"], $row["booked_start_time"]);
    $meetLink = randomMeetLink();

    $conn->begin_transaction();

    try {

        $sqlIns = "
            INSERT INTO sessions (booking_id, scheduled_start, duration_minutes, meeting_mode, meeting_link, session_status)
            VALUES (?, ?, ?, ?, ?, 'scheduled')
        ";
        $stmt2 = $conn->prepare($sqlIns);
        $stmt2->bind_param("isiss", $bookingId, $scheduledStart, $duration, $meetingMode, $meetLink);
        $ok1 = $stmt2->execute();
        $newSessionId = $stmt2->insert_id;
        $stmt2->close();

        if (!$ok1) {
            throw new Exception("Session insert failed");
        }

        $sqlUp = "UPDATE booking_request SET request_status='accepted' WHERE booking_id = ? LIMIT 1";
        $stmt3 = $conn->prepare($sqlUp);
        $stmt3->bind_param("i", $bookingId);
        $ok2 = $stmt3->execute();
        $stmt3->close();

        $sqlOff = "UPDATE mentor_skill_offerings SET current_status='booked' WHERE offering_id=? LIMIT 1";
        $stmt4 = $conn->prepare($sqlOff);
        $stmt4->bind_param("i", $offeringId);
        $stmt4->execute();
        $stmt4->close();

        $conn->commit();

        echo json_encode([
            "ok" => true,
            "message" => $ok2 ? "Request accepted, session created" : "Session created but request update failed",
            "session_id" => $newSessionId
        ]);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["ok" => false, "message" => "Failed to accept: " . $e->getMessage()]);
        exit();
    }
}

if ($action === "reject") {

    $bookingId = (int)($_POST["booking_id"] ?? 0);
    if ($bookingId <= 0) {
        echo json_encode(["ok" => false, "message" => "Invalid booking id"]);
        exit();
    }

    $sqlReject = "
        UPDATE booking_request br
        INNER JOIN mentor_skill_offerings mso ON br.offering_id = mso.offering_id
        SET br.request_status='rejected'
        WHERE br.booking_id = ?
          AND mso.mentor_id = ?
          AND br.request_status='pending'
        LIMIT 1
    ";

    $stmt = $conn->prepare($sqlReject);
    $stmt->bind_param("ii", $bookingId, $mentorId);
    $stmt->execute();

    $aff = $stmt->affected_rows;
    $stmt->close();

    if ($aff > 0) {
        echo json_encode(["ok" => true, "message" => "Request rejected"]);
        exit();
    }

    echo json_encode(["ok" => false, "message" => "Request not found / already processed"]);
    exit();
}

if ($action === "autocomplete") {

    
    $sqlAuto = "
        UPDATE sessions s
        INNER JOIN booking_request br ON s.booking_id = br.booking_id
        INNER JOIN mentor_skill_offerings mso ON br.offering_id = mso.offering_id
        SET s.session_status='completed',
            mso.current_status='completed'
        WHERE mso.mentor_id = ?
          AND s.session_status = 'scheduled'
          AND TIMESTAMPDIFF(MINUTE, s.created_at, NOW()) >= 3
    ";

    $stmt = $conn->prepare($sqlAuto);
    $stmt->bind_param("i", $mentorId);
    $stmt->execute();

    $updated = $stmt->affected_rows;
    $stmt->close();

    echo json_encode(["ok" => true, "updated" => $updated]);
    exit();
}

echo json_encode(["ok" => false, "message" => "Invalid action"]);
exit();
?>
