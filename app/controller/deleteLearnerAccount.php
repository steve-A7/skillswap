<?php
session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    Header("Location: ..\\View\\landing.php");
    exit();
}

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) {
    Header("Location: ..\\View\\landing.php");
    exit();
}

include "..\\Model\\DatabaseConnection.php";

$db = new DatabaseConnection();
$conn = $db->getConnection();

try {
    $stmtPic = $conn->prepare("SELECT profile_picture_path FROM learner_profiles WHERE user_id = ? LIMIT 1");
    if ($stmtPic) {
        $stmtPic->bind_param("i", $userId);
        $stmtPic->execute();
        $res = $stmtPic->get_result();

        if ($res && $row = $res->fetch_assoc()) {
            $path = $row["profile_picture_path"] ?? "";
            $path = str_replace("\\", "/", $path);

            if ($path !== "") {
                $abs = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, ltrim($path, "/"));
                if (file_exists($abs)) {
                    @unlink($abs);
                }
            }
        }

        $stmtPic->close();
    }
} catch (Exception $e) {
}

$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
if (!$stmt) {
    $_SESSION["editStatus"] = "Something went wrong";
    $_SESSION["editStatusType"] = "error";
    Header("Location: ..\\View\\learnerProfile.php");
    exit();
}

$stmt->bind_param("i", $userId);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    $_SESSION["editStatus"] = "Something went wrong";
    $_SESSION["editStatusType"] = "error";
    Header("Location: ..\\View\\learnerProfile.php");
    exit();
}

$_SESSION = [];
if (session_id() !== "") {
    session_destroy();
}

Header("Location: ..\\View\\landing.php");
exit();
?>
