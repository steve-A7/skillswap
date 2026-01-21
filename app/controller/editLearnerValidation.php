<?php
include "..\\Model\\DatabaseConnection.php";
include "..\\Model\\User.php";
include "..\\Model\\LearnerProfile.php";
include "..\\Model\\LearnerInterest.php";
include "..\\Model\\Skill.php";

session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    Header("Location: ..\\View\\landing.php");
    exit();
}

$db = new DatabaseConnection();
$conn = $db->getConnection();
$conn->set_charset("utf8mb4");

$userModel = new User($db);
$learnerModel = new LearnerProfile($db);
$interestModel = new LearnerInterest($db);
$skillModel = new Skill($db);

$userId = (int)($_SESSION["user_id"] ?? 0);

$user = $userModel->findById($userId);
$learner = $learnerModel->getByUserId($userId);

if (!$user || !$learner) {
    $_SESSION["editStatus"] = "Something went wrong";
    $_SESSION["editStatusType"] = "error";
    Header("Location: ..\\View\\learnerProfile.php");
    exit();
}

$learnerId = (int)($learner["learner_id"] ?? 0);

$currentPicPath = $learner["profile_picture_path"] ?? null;
$currentPayMethod = $learner["preferred_payment_method"] ?? "paypal";

$sex = trim($_REQUEST["learner_sex"] ?? "");
$age = trim($_REQUEST["learner_age"] ?? "");
$edu = trim($_REQUEST["learner_edu"] ?? "");
$learnMode = trim($_REQUEST["learner_preferred"] ?? "");
$payMethod = trim($_REQUEST["learner_payment_method"] ?? "");
$payDetail = trim($_REQUEST["learner_payment_detail"] ?? "");
$bio = trim($_REQUEST["learner_bio"] ?? "");

$newPassword = $_REQUEST["new_password"] ?? "";
$confirmPassword = $_REQUEST["confirm_password"] ?? "";

$categoryIds = $_REQUEST["learner_interest_ids"] ?? [];
if (!is_array($categoryIds)) $categoryIds = [];
$categoryIds = array_values(array_unique(array_map("intval", $categoryIds)));

$newInterestsText = trim($_REQUEST["learner_new_interests"] ?? "");

$errors = [];

if ($newPassword !== "" || $confirmPassword !== "") {
    if ($newPassword === "" || $confirmPassword === "") {
        $errors[] = "Retype password is required";
    } else if ($newPassword !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
}
if (count($categoryIds) < 1 && $newInterestsText === "") {
    $existing = [];
    if ($learnerId > 0) {
        $stmtC = $conn->prepare("SELECT category_id FROM learner_interests WHERE learner_id = ?");
        if ($stmtC) {
            $stmtC->bind_param("i", $learnerId);
            $stmtC->execute();
            $resC = $stmtC->get_result();
            if ($resC) {
                while ($row = $resC->fetch_assoc()) {
                    $existing[] = (int)$row["category_id"];
                }
            }
            $stmtC->close();
        }
    }

    if (count($existing) > 0) {
        $categoryIds = $existing;
    } else {
        $errors[] = "Select at least 1 category or add one";
    }
}

$newProfilePath = $currentPicPath;
$hasNewImage = (isset($_FILES["profile_picture"]) && ($_FILES["profile_picture"]["error"] ?? 4) !== 4);

if ($hasNewImage) {
    $fileErr = $_FILES["profile_picture"]["error"];
    $fileSize = $_FILES["profile_picture"]["size"];
    $fileName = $_FILES["profile_picture"]["name"] ?? "";

    if ($fileErr != 0) {
        $errors[] = "Invalid image upload";
    } else {
        if ($fileSize > (10 * 1024 * 1024)) {
            $errors[] = "Image size must be less than 10MB";
        } else {
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ["jpg", "jpeg", "png"])) {
                $errors[] = "Only JPG or PNG images are allowed";
            }
        }
    }
}

if ($newInterestsText !== "") {
    $parts = array_filter(array_map("trim", explode(",", $newInterestsText)));
    $parts = array_values(array_unique($parts));

    foreach ($parts as $name) {
        $cid = 0;
        if (method_exists($skillModel, "getOrCreateCategory")) {
            $cid = (int)$skillModel->getOrCreateCategory($name);
        } else {
            $stmt = $conn->prepare("SELECT category_id FROM skill_categories WHERE LOWER(category_name) = LOWER(?) LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $name);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($cid);
                    $stmt->fetch();
                }
                $stmt->close();
            }
        }

        if ($cid > 0) $categoryIds[] = $cid;
    }
}

$categoryIds = array_values(array_unique(array_filter(array_map("intval", $categoryIds), function ($v) {
    return $v > 0;
})));

$allowedSex = ["male", "female", "other"];
if ($sex === "" || !in_array($sex, $allowedSex)) {
    $sex = $learner["sex"] ?? "other";
}

$allowedLearn = ["audio", "video", "both"];
if ($learnMode === "" || !in_array($learnMode, $allowedLearn)) {
    $learnMode = $learner["preferred_way_to_learn"] ?? "both";
}

$allowedPay = ["paypal", "credit_card", "debit_card", "bkash", "nagad"];
if ($payMethod === "" || !in_array($payMethod, $allowedPay)) {
    $payMethod = $currentPayMethod;
}

$paypal = $learner["paypal_email"] ?? null;
$bkash = $learner["bkash_number"] ?? null;
$nagad = $learner["nagad_number"] ?? null;
$cardLast4 = $learner["card_last4"] ?? null;

if ($payMethod === "paypal") {
    $paypal = $payDetail !== "" ? $payDetail : $paypal;
    $bkash = null;
    $nagad = null;
    $cardLast4 = null;
} else if ($payMethod === "bkash") {
    $bkash = $payDetail !== "" ? $payDetail : $bkash;
    $paypal = null;
    $nagad = null;
    $cardLast4 = null;
} else if ($payMethod === "nagad") {
    $nagad = $payDetail !== "" ? $payDetail : $nagad;
    $paypal = null;
    $bkash = null;
    $cardLast4 = null;
} else if ($payMethod === "credit_card" || $payMethod === "debit_card") {
    $cardLast4 = $payDetail !== "" ? $payDetail : $cardLast4;
    $paypal = null;
    $bkash = null;
    $nagad = null;
}

if (count($errors) > 0) {
    $_SESSION["editStatus"] = $errors[0];
    $_SESSION["editStatusType"] = "error";
    Header("Location: ..\\View\\learnerProfile.php");
    exit();
}

$finalAbsPath = null;
$finalDbPath = null;

if ($hasNewImage) {
    $ext = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
    if ($ext === "jpeg") $ext = "jpg";

    $username = $user["username"] ?? "learner";
    $uploadDirAbs = dirname(__DIR__, 2) . "\\public\\assets\\uploads\\";
    if (!is_dir($uploadDirAbs)) {
        mkdir($uploadDirAbs, 0777, true);
    }

    $finalName = $username . "." . $ext;
    $finalAbsPath = $uploadDirAbs . $finalName;
    $finalDbPath = "public/assets/uploads/" . $finalName;
}

$conn->begin_transaction();

try {

    if ($newPassword !== "") {
        $ok = $userModel->updatePassword($userId, $newPassword);
        if (!$ok) {
            $conn->rollback();
            $_SESSION["editStatus"] = "Something went wrong";
            $_SESSION["editStatusType"] = "error";
            Header("Location: ..\\View\\learnerProfile.php");
            exit();
        }
    }

    if ($hasNewImage) {
        if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $finalAbsPath)) {
            $conn->rollback();
            $_SESSION["editStatus"] = "Image upload failed";
            $_SESSION["editStatusType"] = "error";
            Header("Location: ..\\View\\learnerProfile.php");
            exit();
        }
        $newProfilePath = $finalDbPath;
    }

    $profile = [
        "sex" => $sex,
        "age" => $age,
        "educational_qualification" => $edu,
        "preferred_way_to_learn" => $learnMode,
        "profile_picture_path" => $newProfilePath,
        "preferred_payment_method" => $payMethod,
        "paypal_email" => $paypal,
        "bkash_number" => $bkash,
        "nagad_number" => $nagad,
        "card_last4" => $cardLast4,
        "bio" => $bio
    ];

    $ok = $learnerModel->updateByUserId($userId, $profile);
    if (!$ok) {
        $conn->rollback();
        $_SESSION["editStatus"] = "Something went wrong";
        $_SESSION["editStatusType"] = "error";
        Header("Location: ..\\View\\learnerProfile.php");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM learner_interests WHERE learner_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $learnerId);
        $stmt->execute();
        $stmt->close();
    }

    foreach ($categoryIds as $cid) {
        $interestModel->add($learnerId, $cid);
    }

    $conn->commit();

    $_SESSION["editStatus"] = "Profile successfully edited";
    $_SESSION["editStatusType"] = "success";
    Header("Location: ..\\View\\learnerProfile.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION["editStatus"] = "Something went wrong";
    $_SESSION["editStatusType"] = "error";
    Header("Location: ..\\View\\learnerProfile.php");
    exit();
}
