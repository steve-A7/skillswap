<?php
include "../Model/DatabaseConnection.php";
include "../Model/MentorSkillOffering.php";

session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["ok" => false, "message" => "Not logged in"]);
    exit();
}

function updateExpiredOfferings($conn){
    $sql = "UPDATE mentor_skill_offerings 
            SET current_status='expired' 
            WHERE current_status='available'
              AND offered_for IS NOT NULL 
              AND offered_for > 0
              AND DATE_ADD(created_at, INTERVAL offered_for HOUR) <= NOW()";
    $conn->query($sql);
}


function getMentorIdFromSessionOrDb($conn) {
    if (isset($_SESSION["mentor_id"])) return (int)$_SESSION["mentor_id"];
    if (isset($_SESSION["MentorID"])) return (int)$_SESSION["MentorID"];

    $userId = 0;
    if (isset($_SESSION["user_id"])) $userId = (int)$_SESSION["user_id"];
    if (isset($_SESSION["UserID"])) $userId = (int)$_SESSION["UserID"];

    if ($userId <= 0) return 0;

    $sql = "SELECT mentor_id FROM mentor_profiles WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows < 1) {
        $stmt->close();
        return 0;
    }

    $mentorId = 0;
    $stmt->bind_result($mentorId);
    $stmt->fetch();
    $stmt->close();

    $_SESSION["mentor_id"] = (int)$mentorId;
    return (int)$mentorId;
}

$db = new DatabaseConnection();
$conn = $db->getConnection();
$offeringModel = new MentorSkillOffering($db);

$action = $_GET["action"] ?? $_POST["action"] ?? "";

if ($action === "list") {
    header("Content-Type: application/json; charset=UTF-8");

    $mentorId = getMentorIdFromSessionOrDb($conn);
    if ($mentorId <= 0) {
        echo json_encode(["ok" => true, "data" => []]);
        $db->close();
        exit();
    }
    
    updateExpiredOfferings($conn);

    $all = $offeringModel->listByMentor($mentorId);
    $available = [];

    foreach ($all as $row) {
        if (($row["current_status"] ?? "") === "available") {
            $pic = $row["offering_picture_path"] ?? "";
            $pic = str_replace("\\", "/", $pic);

            $picUrl = "";
            if ($pic !== "") {
            $picUrl = "../../" . ltrim($pic, "/");
            }

            $available[] = [
            "offering_id" => (int)$row["offering_id"],
            "skill_title" => $row["skill_title"] ?? "",
            "skill_code" => $row["skill_code"] ?? "",
            "offering_picture_path" => $picUrl
            ];
        }
    }

    echo json_encode(["ok" => true, "data" => $available]);
    $db->close();
    exit();
}

if ($action === "select") {
    header("Content-Type: application/json; charset=UTF-8");
    
    updateExpiredOfferings($conn);

    $mentorId = getMentorIdFromSessionOrDb($conn);
    $offeringId = (int)($_POST["offering_id"] ?? 0);

    if ($mentorId <= 0 || $offeringId <= 0) {
        echo json_encode(["ok" => false, "message" => "Invalid selection"]);
        $db->close();
        exit();
    }

    $offering = $offeringModel->getById($offeringId);
    if (!$offering) {
        echo json_encode(["ok" => false, "message" => "Offering not found"]);
        $db->close();
        exit();
    }

    if ((int)$offering["mentor_id"] !== $mentorId) {
        echo json_encode(["ok" => false, "message" => "Unauthorized"]);
        $db->close();
        exit();
    }

    if (($offering["current_status"] ?? "") !== "available") {
        echo json_encode(["ok" => false, "message" => "Offering not available"]);
        $db->close();
        exit();
    }

    $_SESSION["selectedOfferingId"] = $offeringId;

    echo json_encode(["ok" => true, "message" => "Selected"]);
    $db->close();
    exit();
}

header("Location: ../View/editSkillOffering.php");
$db->close();
exit();
