<?php
include "../Model/DatabaseConnection.php";
session_start();

header("Content-Type: application/json; charset=UTF-8");

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn || (($_SESSION["Role"] ?? "") !== "learner")) {
    echo json_encode(["ok" => false, "offerings" => []]);
    exit();
}

$db = new DatabaseConnection();
$conn = $db->getConnection();

$userId = (int)($_SESSION["user_id"] ?? $_SESSION["UserId"] ?? 0);
if ($userId < 1) {
    echo json_encode(["ok" => false, "offerings" => []]);
    exit();
}

$learnerId = 0;
$stmt = $conn->prepare("SELECT learner_id FROM learner_profiles WHERE user_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($lid);
    if ($stmt->fetch()) {
        $learnerId = (int)$lid;
    }
    $stmt->close();
}

if ($learnerId < 1) {
    echo json_encode(["ok" => false, "offerings" => []]);
    exit();
}

$mode = $_GET["mode"] ?? "your";


$upd = $conn->prepare("
    UPDATE mentor_skill_offerings
    SET current_status = 'expired'
    WHERE current_status = 'available'
      AND DATE_ADD(created_at, INTERVAL offered_for HOUR) <= NOW()
");
if ($upd) {
    $upd->execute();
    $upd->close();
}

$catIds = [];

if ($mode === "your") {
    $stmt2 = $conn->prepare("SELECT category_id FROM learner_interests WHERE learner_id = ?");
    if ($stmt2) {
        $stmt2->bind_param("i", $learnerId);
        $stmt2->execute();
        $res = $stmt2->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $catIds[] = (int)$r["category_id"];
            }
        }
        $stmt2->close();
    }
}

/* ✅ Base query */
$sql = "
    SELECT 
        mso.offering_id,
        mso.skill_title,
        mso.offering_picture_path,
        mso.created_at,
        mso.offered_for,
        DATE_ADD(mso.created_at, INTERVAL mso.offered_for HOUR) AS expires_at
    FROM mentor_skill_offerings mso
    WHERE mso.current_status='available'
      AND DATE_ADD(mso.created_at, INTERVAL mso.offered_for HOUR) > NOW()
";

$params = [];
$types = "";

if ($mode === "your" && count($catIds) > 0) {
    $placeholders = implode(",", array_fill(0, count($catIds), "?"));
    $sql .= " AND mso.category_id IN ($placeholders)";
    $types .= str_repeat("i", count($catIds));
    foreach ($catIds as $cid) $params[] = $cid;
}

$sql .= " ORDER BY mso.created_at DESC LIMIT 250";

$stmt3 = $conn->prepare($sql);
if (!$stmt3) {
    echo json_encode(["ok" => false, "offerings" => []]);
    exit();
}

if (count($params) > 0) {
    $stmt3->bind_param($types, ...$params);
}

$stmt3->execute();
$result = $stmt3->get_result();

$items = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {

        $pic = $row["offering_picture_path"] ?? "";
        $pic = str_replace("\\", "/", $pic);

        $picUrl = "";
        if ($pic !== "") {
            $picUrl = "../../" . ltrim($pic, "/");
        }

        $items[] = [
            "offering_id" => (int)$row["offering_id"],
            "skill_title" => $row["skill_title"],
            "created_at" => $row["created_at"],
            "offered_for" => (int)$row["offered_for"],
            "expires_at" => $row["expires_at"],
            "offering_picture_path" => $picUrl
        ];
    }
}

$stmt3->close();

echo json_encode(["ok" => true, "offerings" => $items]);
exit();
