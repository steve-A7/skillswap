<?php
include "../Model/DatabaseConnection.php";
include "../Model/Rating.php";
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

function getLearnerIdFromUser($conn, $userId) {
    $sql = "SELECT learner_id FROM learner_profiles WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    return $row ? (int)$row["learner_id"] : 0;
}

$learnerId = getLearnerIdFromUser($conn, (int)$userId);
if ($learnerId <= 0) {
    echo json_encode(["ok" => false, "message" => "Learner profile not found"]);
    exit();
}

$action = $_GET["action"] ?? ($_POST["action"] ?? "list");

if ($action === "list") {

    $pending = [];
    $sqlPending = "
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

            um.username AS mentor_username,
            mp.profile_picture_path AS mentor_picture

        FROM booking_request br
        INNER JOIN mentor_skill_offerings mso ON br.offering_id = mso.offering_id
        INNER JOIN mentor_profiles mp ON mso.mentor_id = mp.mentor_id
        INNER JOIN users um ON mp.user_id = um.user_id

        WHERE br.requested_learner_id = ?
          AND br.request_status = 'pending'
        ORDER BY br.created_at DESC
    ";

    $stmt = $conn->prepare($sqlPending);
    $stmt->bind_param("i", $learnerId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $pending[] = [
            "booking_id" => (int)$row["booking_id"],
            "offering_id" => (int)$row["offering_id"],

            "skill_title" => $row["skill_title"],
            "offering_picture_path" => makeAssetUrl($row["offering_picture_path"]),

            "mentor_username" => $row["mentor_username"],
            "mentor_picture" => makeAssetUrl($row["mentor_picture"]),

            "booked_day_of_week" => $row["booked_day_of_week"],
            "booked_start_time" => $row["booked_start_time"],
            "booked_end_time" => $row["booked_end_time"],
            "booked_duration_minutes" => (int)$row["booked_duration_minutes"],

            "meeting_mode" => $row["learner_pref"],
        ];
    }
    $stmt->close();

    $ongoing = [];
    $sqlOngoing = "
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

            um.username AS mentor_username,
            mp.profile_picture_path AS mentor_picture

        FROM sessions s
        INNER JOIN booking_request br ON s.booking_id = br.booking_id
        INNER JOIN mentor_skill_offerings mso ON br.offering_id = mso.offering_id
        INNER JOIN mentor_profiles mp ON mso.mentor_id = mp.mentor_id
        INNER JOIN users um ON mp.user_id = um.user_id

        WHERE br.requested_learner_id = ?
          AND s.session_status = 'scheduled'
        ORDER BY s.created_at DESC
    ";

    $stmt2 = $conn->prepare($sqlOngoing);
    $stmt2->bind_param("i", $learnerId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    while ($row = $res2->fetch_assoc()) {
        $ongoing[] = [
            "session_id" => (int)$row["session_id"],
            "booking_id" => (int)$row["booking_id"],

            "scheduled_start" => $row["scheduled_start"],
            "duration_minutes" => (int)$row["duration_minutes"],
            "meeting_mode" => $row["meeting_mode"],
            "meeting_link" => $row["meeting_link"],

            "skill_title" => $row["skill_title"],
            "offering_picture_path" => makeAssetUrl($row["offering_picture_path"]),

            "mentor_username" => $row["mentor_username"],
            "mentor_picture" => makeAssetUrl($row["mentor_picture"]),
        ];
    }
    $stmt2->close();

    $completed = [];
    $sqlCompleted = "
        SELECT
            s.session_id,
            s.booking_id,
            s.scheduled_start,
            s.duration_minutes,
            s.meeting_mode,
            s.session_status,

            mso.skill_title,
            mso.offering_picture_path,

            um.username AS mentor_username,
            mp.profile_picture_path AS mentor_picture,

            r.rating AS rating_value,
            r.review AS review_text

        FROM sessions s
        INNER JOIN booking_request br ON s.booking_id = br.booking_id
        INNER JOIN mentor_skill_offerings mso ON br.offering_id = mso.offering_id
        INNER JOIN mentor_profiles mp ON mso.mentor_id = mp.mentor_id
        INNER JOIN users um ON mp.user_id = um.user_id

        LEFT JOIN rating r
            ON r.session_id = s.session_id
           AND r.rated_by_learner_id = ?

        WHERE br.requested_learner_id = ?
          AND s.session_status = 'completed'
        ORDER BY s.created_at DESC
    ";

    $stmt3 = $conn->prepare($sqlCompleted);
    $stmt3->bind_param("ii", $learnerId, $learnerId);
    $stmt3->execute();
    $res3 = $stmt3->get_result();

    while ($row = $res3->fetch_assoc()) {
        $hasReview = ($row["rating_value"] !== null);

        $completed[] = [
            "session_id" => (int)$row["session_id"],
            "booking_id" => (int)$row["booking_id"],

            "scheduled_start" => $row["scheduled_start"],
            "duration_minutes" => (int)$row["duration_minutes"],
            "meeting_mode" => $row["meeting_mode"],

            "skill_title" => $row["skill_title"],
            "offering_picture_path" => makeAssetUrl($row["offering_picture_path"]),

            "mentor_username" => $row["mentor_username"],
            "mentor_picture" => makeAssetUrl($row["mentor_picture"]),

            "has_review" => $hasReview,
            "rating_value" => $hasReview ? (int)$row["rating_value"] : null,
            "review_text" => $hasReview ? ($row["review_text"] ?? "") : "",
        ];
    }
    $stmt3->close();

    echo json_encode([
        "ok" => true,
        "pending" => $pending,
        "ongoing" => $ongoing,
        "completed" => $completed
    ]);
    exit();
}


if ($action === "review") {

    $sessionId = (int)($_POST["session_id"] ?? 0);
    $ratingValue = (int)($_POST["rating_value"] ?? 0);
    $reviewText = trim($_POST["review_text"] ?? "");

    if ($sessionId <= 0) {
        echo json_encode(["ok" => false, "message" => "Invalid session id"]);
        exit();
    }

    if ($ratingValue < 1 || $ratingValue > 5) {
        echo json_encode(["ok" => false, "message" => "Rating must be between 1 and 5"]);
        exit();
    }

    if ($reviewText === "") {
        echo json_encode(["ok" => false, "message" => "Please enter feedback text"]);
        exit();
    }

    $sqlMeta = "
        SELECT 
            s.session_id,
            br.offering_id,
            mso.mentor_id
        FROM sessions s
        INNER JOIN booking_request br ON s.booking_id = br.booking_id
        INNER JOIN mentor_skill_offerings mso ON br.offering_id = mso.offering_id
        WHERE s.session_id = ?
          AND br.requested_learner_id = ?
          AND s.session_status = 'completed'
        LIMIT 1
    ";
    $stmt = $conn->prepare($sqlMeta);
    $stmt->bind_param("ii", $sessionId, $learnerId);
    $stmt->execute();
    $res = $stmt->get_result();
    $meta = $res->fetch_assoc();
    $stmt->close();

    if (!$meta) {
        echo json_encode(["ok" => false, "message" => "Completed session not found"]);
        exit();
    }

    $offeringId = (int)$meta["offering_id"];
    $mentorId = (int)$meta["mentor_id"];
    $sqlDup = "SELECT rating_id FROM rating WHERE session_id = ? LIMIT 1";
    $stmt2 = $conn->prepare($sqlDup);
    $stmt2->bind_param("i", $sessionId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $dup = $res2->fetch_assoc();
    $stmt2->close();

    if ($dup) {
        echo json_encode(["ok" => false, "message" => "You already reviewed this session"]);
        exit();
    }

    $ratingModel = new Rating($db);
    $newId = $ratingModel->create(
        $sessionId,
        $offeringId,
        $mentorId,
        $learnerId,
        $ratingValue,
        $reviewText,
        "yes"
    );

    if ($newId > 0) {
        echo json_encode(["ok" => true, "message" => "Review submitted successfully"]);
        exit();
    }

    echo json_encode(["ok" => false, "message" => "Failed to submit review"]);
    exit();
}


if ($action === "autocomplete") {

    $sqlAuto = "
        UPDATE sessions s
        INNER JOIN booking_request br ON s.booking_id = br.booking_id
        INNER JOIN mentor_skill_offerings mso ON br.offering_id = mso.offering_id
        SET s.session_status='completed',
            mso.current_status='completed'
        WHERE br.requested_learner_id = ?
          AND s.session_status = 'scheduled'
          AND TIMESTAMPDIFF(MINUTE, s.created_at, NOW()) >= 3
    ";

    $stmt = $conn->prepare($sqlAuto);
    $stmt->bind_param("i", $learnerId);
    $stmt->execute();

    $updated = $stmt->affected_rows;
    $stmt->close();

    echo json_encode(["ok" => true, "updated" => $updated]);
    exit();
}

echo json_encode(["ok" => false, "message" => "Invalid action"]);
exit();
?>
