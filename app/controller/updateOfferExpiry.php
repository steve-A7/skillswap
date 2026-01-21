<?php
include "../Model/DatabaseConnection.php";
session_start();

header("Content-Type: application/json; charset=UTF-8");

$db = new DatabaseConnection();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(["ok" => false, "updated" => 0]);
    exit();
}

$sql = "
    UPDATE mentor_skill_offerings
    SET current_status='expired'
    WHERE current_status='available'
      AND DATE_ADD(created_at, INTERVAL offered_for HOUR) <= NOW()
";

$conn->query($sql);

$updated = $conn->affected_rows;

echo json_encode(["ok" => true, "updated" => $updated]);
exit();
?>