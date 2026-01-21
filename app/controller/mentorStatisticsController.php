<?php
include "../Model/DatabaseConnection.php";

session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    header("Location: ../view/login.php");
    exit();
}

$userId = (int)($_SESSION["user_id"] ?? $_SESSION["UserId"] ?? 0);
if ($userId <= 0) {
    header("Location: ../view/login.php");
    exit();
}

$db = new DatabaseConnection();
$conn = $db->getConnection();

$mentorId = 0;
$mentorName = "Mentor";

$sqlMentor = "
    SELECT mp.mentor_id, u.username
    FROM mentor_profiles mp
    JOIN users u ON mp.user_id = u.user_id
    WHERE mp.user_id = ?
    LIMIT 1
";
$stmtMentor = $conn->prepare($sqlMentor);
if ($stmtMentor) {
    $stmtMentor->bind_param("i", $userId);
    $stmtMentor->execute();
    $stmtMentor->bind_result($mentorIdDb, $mentorUsernameDb);
    if ($stmtMentor->fetch()) {
        $mentorId = (int)$mentorIdDb;
        $mentorName = $mentorUsernameDb ?? "Mentor";
    }
    $stmtMentor->close();
}

if ($mentorId <= 0) {
    header("Location: ../view/mentorDashboard.php");
    exit();
}

$avgRating = 0;
$totalReviews = 0;

$sqlAvg = "
    SELECT AVG(r.rating) AS avg_rating, COUNT(*) AS total_reviews
    FROM rating r
    JOIN sessions s ON r.session_id = s.session_id
    WHERE r.mentor_id = ?
      AND s.session_status = 'completed'
";
$stmtAvg = $conn->prepare($sqlAvg);
if ($stmtAvg) {
    $stmtAvg->bind_param("i", $mentorId);
    $stmtAvg->execute();
    $stmtAvg->bind_result($avgDb, $countDb);
    if ($stmtAvg->fetch()) {
        $avgRating = ($avgDb !== null) ? (float)$avgDb : 0;
        $totalReviews = (int)$countDb;
    }
    $stmtAvg->close();
}

$reviews = [];

$sqlReviews = "
    SELECT
        r.rating_id,
        r.rating,
        r.review,
        r.created_at,

        mso.offering_id,
        mso.skill_title,

        lp.learner_id,
        lp.profile_picture_path AS learner_pic,

        u.username AS learner_username,
        u.user_id AS learner_user_id

    FROM rating r
    JOIN sessions s ON r.session_id = s.session_id
    JOIN mentor_skill_offerings mso ON r.offering_id = mso.offering_id
    JOIN learner_profiles lp ON r.rated_by_learner_id = lp.learner_id
    JOIN users u ON lp.user_id = u.user_id

    WHERE r.mentor_id = ?
      AND s.session_status = 'completed'

    ORDER BY r.created_at DESC
";

$stmtReviews = $conn->prepare($sqlReviews);
if ($stmtReviews) {
    $stmtReviews->bind_param("i", $mentorId);
    $stmtReviews->execute();
    $result = $stmtReviews->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
    }
    $stmtReviews->close();
}

include "../view/mentorStatistics.php";
