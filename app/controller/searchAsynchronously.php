<?php
include "../Model/DatabaseConnection.php";
session_start();

header("Content-Type: application/json; charset=UTF-8");

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn || (($_SESSION["Role"] ?? "") !== "learner")) {
    echo json_encode(["ok" => false, "data" => []]);
    exit();
}

$action = $_GET["action"] ?? "";
if ($action !== "list") {
    echo json_encode(["ok" => false, "data" => []]);
    exit();
}

$q = trim($_GET["q"] ?? "");

$db = new DatabaseConnection();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(["ok" => false, "data" => []]);
    exit();
}

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

if ($q !== "") {
    $sql .= " AND mso.skill_title LIKE ?";
    $types .= "s";
    $params[] = "%" . $q . "%";
}

$sql .= " ORDER BY mso.created_at DESC LIMIT 200";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["ok" => false, "data" => []]);
    exit();
}

if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

$data = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {

        $pic = $row["offering_picture_path"] ?? "";
        $pic = str_replace("\\", "/", $pic);

        $picUrl = "";
        if ($pic !== "") {
            $picUrl = "../../" . ltrim($pic, "/");
        }

        $row["offering_picture_path"] = $picUrl;
        $data[] = $row;
    }
}

$stmt->close();

echo json_encode(["ok" => true, "data" => $data]);
exit();
