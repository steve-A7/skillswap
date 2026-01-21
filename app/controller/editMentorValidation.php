<?php
include "..\\Model\\DatabaseConnection.php";
include "..\\Model\\User.php";
include "..\\Model\\MentorProfile.php";
include "..\\Model\\MentorCategory.php";
include "..\\Model\\Skill.php";

session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    Header("Location: ..\View\\landing.php");
    exit();
}

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) {
    Header("Location: ..\View\\landing.php");
    exit();
}

$db = new DatabaseConnection();
$conn = $db->getConnection();

$userModel = new User($db);
$mentorModel = new MentorProfile($db);
$mentorCategoryModel = new MentorCategory($db);
$skillModel = new Skill($db);

$user = $userModel->findById($userId);
$mentor = $mentorModel->getByUserId($userId);

if (!$user || !$mentor) {
    $_SESSION["editStatus"] = "Something went wrong";
    $_SESSION["editStatusType"] = "error";
    Header("Location: ..\View\\mentorProfile.php");
    exit();
}

$mentorId = (int)($mentor["mentor_id"] ?? 0);

// Current values (fallback)
$currentSex = $mentor["sex"] ?? "other";
$currentAge = $mentor["age"] ?? null;
$currentLang = $mentor["language_proficiency"] ?? "";
$currentPrice = $mentor["available_for_price_range"] ?? "1000-1999";
$currentPreferred = $mentor["preferred_mentoring"] ?? "both";
$currentPayMethod = $mentor["payment_method"] ?? "paypal";
$currentBio = $mentor["bio"] ?? null;
$currentPicPath = $mentor["profile_picture_path"] ?? null;
$currentQualExp = $mentor["qualification_experience"] ?? "";

$sex = trim($_REQUEST["mentor_sex"] ?? "");
$age = trim($_REQUEST["mentor_age"] ?? "");
$qualification = trim($_REQUEST["mentor_qualification"] ?? "");
$experience = trim($_REQUEST["mentor_experience"] ?? "");
$language = trim($_REQUEST["mentor_language"] ?? "");
$priceRange = trim($_REQUEST["mentor_price_range"] ?? "");
$preferred = trim($_REQUEST["mentor_preferred"] ?? "");
$payMethod = trim($_REQUEST["mentor_payment_method"] ?? "");
$payDetail = trim($_REQUEST["mentor_payment_detail"] ?? "");
$bio = trim($_REQUEST["mentor_bio"] ?? "");

$newPassword = $_REQUEST["new_password"] ?? "";
$confirmPassword = $_REQUEST["confirm_password"] ?? "";

$categoryIds = $_REQUEST["mentor_category_ids"] ?? [];
if (!is_array($categoryIds)) $categoryIds = [];
$categoryIds = array_values(array_unique(array_map("intval", $categoryIds)));

$newCategoriesText = trim($_REQUEST["mentor_new_categories"] ?? "");

$errors = [];

// Password validation
if ($newPassword !== "" || $confirmPassword !== "") {
    if ($newPassword === "" || $confirmPassword === "") {
        $errors[] = "Retype password is required";
    } else if ($newPassword !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
}


if (count($categoryIds) < 1 && $newCategoriesText === "") {
    $existing = [];
    if ($mentorId > 0) {
        $stmtC = $conn->prepare("SELECT category_id FROM mentor_categories WHERE mentor_id = ?");
        if ($stmtC) {
            $stmtC->bind_param("i", $mentorId);
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

if (count($errors) > 0) {
    $_SESSION["editStatus"] = implode(" | ", $errors);
    $_SESSION["editStatusType"] = "error";
    Header("Location: ..\View\\mentorProfile.php");
    exit();
}

if ($newCategoriesText !== "") {
    $parts = array_map("trim", explode(",", $newCategoriesText));
    $parts = array_values(array_filter($parts, function($v){ return $v !== ""; }));
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

$categoryIds = array_values(array_unique(array_filter(array_map("intval", $categoryIds), function($v){ return $v > 0; })));


$allowedPrices = ["500-999", "1000-1999", "2000-2999", "3000-4999", "5000+"];
if ($priceRange === "" || !in_array($priceRange, $allowedPrices)) {
    $priceRange = $currentPrice;
}

$allowedPreferred = ["audio", "video", "both"]; 
if ($preferred === "" || !in_array($preferred, $allowedPreferred)) {
    $preferred = $currentPreferred;
}

$allowedPay = ["paypal", "credit_card", "debit_card", "bkash", "nagad"]; 
if ($payMethod === "" || !in_array($payMethod, $allowedPay)) {
    $payMethod = $currentPayMethod;
}

$allowedSex = ["male", "female", "other"]; 
if ($sex === "" || !in_array($sex, $allowedSex)) {
    $sex = $currentSex;
}

if ($language === "") {
    $language = $currentLang;
}

$finalAge = $currentAge;
if ($age !== "") {
    $finalAge = (int)$age;
}

$qualExp = $currentQualExp;
if ($qualification !== "" || $experience !== "") {
    $qualExp = trim($qualification) . " | " . trim($experience);
}

$finalBio = $currentBio;
if ($bio !== "") {
    $finalBio = $bio;
}

$paypal = null;
$bkash = null;
$nagad = null;
$cardLast4 = null;

if ($payMethod === "paypal") {
    $paypal = $payDetail !== "" ? $payDetail : ($mentor["paypal_email"] ?? null);
} else if ($payMethod === "bkash") {
    $bkash = $payDetail !== "" ? $payDetail : ($mentor["bkash_number"] ?? null);
} else if ($payMethod === "nagad") {
    $nagad = $payDetail !== "" ? $payDetail : ($mentor["nagad_number"] ?? null);
} else if ($payMethod === "credit_card" || $payMethod === "debit_card") {
    $cardLast4 = $payDetail !== "" ? $payDetail : ($mentor["card_last4"] ?? null);
}

$finalAbsPath = null;
$finalDbPath = null;

if ($hasNewImage) {
    $ext = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
    if ($ext === "jpeg") $ext = "jpg";

    $username = $user["username"] ?? "mentor";
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
            Header("Location: ..\View\\mentorProfile.php");
            exit();
        }
    }

    if ($hasNewImage) {
        if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $finalAbsPath)) {
            $conn->rollback();
            $_SESSION["editStatus"] = "Could not save image";
            $_SESSION["editStatusType"] = "error";
            Header("Location: ..\View\\mentorProfile.php");
            exit();
        }
        $newProfilePath = $finalDbPath;
    }

    $profile = [];
    $profile["sex"] = $sex;
    $profile["age"] = $finalAge;
    $profile["qualification_experience"] = $qualExp;
    $profile["language_proficiency"] = $language;
    $profile["available_for_price_range"] = $priceRange;
    $profile["payment_method"] = $payMethod;
    $profile["paypal_email"] = $paypal;
    $profile["bkash_number"] = $bkash;
    $profile["nagad_number"] = $nagad;
    $profile["card_last4"] = $cardLast4;
    $profile["preferred_mentoring"] = $preferred;
    $profile["profile_picture_path"] = $newProfilePath;
    $profile["bio"] = $finalBio;

    $ok = $mentorModel->updateByUserId($userId, $profile);
    if (!$ok) {
        $conn->rollback();
        $_SESSION["editStatus"] = "Something went wrong";
        $_SESSION["editStatusType"] = "error";
        Header("Location: ..\View\\mentorProfile.php");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM mentor_categories WHERE mentor_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $mentorId);
        $stmt->execute();
        $stmt->close();
    }

    foreach ($categoryIds as $cid) {
        $mentorCategoryModel->add($mentorId, $cid);
    }

    $conn->commit();
    $_SESSION["editStatus"] = "Profile successfully edited";
    $_SESSION["editStatusType"] = "success";
    Header("Location: ..\View\\mentorProfile.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION["editStatus"] = "Something went wrong";
    $_SESSION["editStatusType"] = "error";
    Header("Location: ..\View\\mentorProfile.php");
    exit();
}
?>
