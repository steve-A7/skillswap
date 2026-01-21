<?php
include "..\\Model\\DatabaseConnection.php";
include "..\\Model\\User.php";
include "..\\Model\\MentorProfile.php";

session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if(!$isLoggedIn){
    Header("Location: ..\\View\\landing.php");
    exit();
}

$userId = (int)($_SESSION["user_id"] ?? 0);
if($userId <= 0){
    Header("Location: ..\\View\\landing.php");
    exit();
}

$db = new DatabaseConnection();
$conn = $db->getConnection();

$userModel = new User($db);
$mentorModel = new MentorProfile($db);

$user = $userModel->findById($userId);
$mentor = $mentorModel->getByUserId($userId);

if(!$user || !$mentor){
    $_SESSION["editOfferStatus"] = "Something went wrong";
    $_SESSION["editOfferStatusType"] = "error";
    Header("Location: ..\\View\\editSkillPanel.php");
    exit();
}

$mentorId = (int)($mentor["mentor_id"] ?? 0);

$offeringId = (int)($_REQUEST["offering_id"] ?? 0);

$skillTitle = trim($_REQUEST["skill_title"] ?? "");
$skillCode = trim($_REQUEST["skill_code"] ?? "");
$categoryId = (int)($_REQUEST["category_id"] ?? 0);
$difficulty = trim($_REQUEST["difficulty"] ?? "");
$prereq = trim($_REQUEST["prerequisites"] ?? "");
$price = trim($_REQUEST["price"] ?? "");
$offeredFor = trim($_REQUEST["offered_for"] ?? "");
$timeSlotsRaw = trim($_REQUEST["time_slots"] ?? "");
$durationMinutes = trim($_REQUEST["duration_minutes"] ?? "");
$description = trim($_REQUEST["description"] ?? "");
$currentStatus = trim($_REQUEST["current_status"] ?? "available");

$errors = [];

if($offeringId <= 0) $errors[] = "Invalid offering";
if($skillTitle === "") $errors[] = "Skill title is required";
if($skillCode === "") $errors[] = "Skill code is required";
if($categoryId <= 0) $errors[] = "Skill category is required";

$allowedDiff = ["beginner","intermediate","advanced"];
if(!in_array($difficulty, $allowedDiff)) $errors[] = "Select a valid difficulty";

$allowedStatus = ["available","active","booked","expired","completed"];
if(!in_array($currentStatus, $allowedStatus)) $errors[] = "Select a valid status";

if($timeSlotsRaw === "") $errors[] = "Offered time slots are required";
if($durationMinutes === "" || (int)$durationMinutes <= 0) $errors[] = "Session duration is required";
if($offeredFor === "" || (int)$offeredFor <= 0) $errors[] = "Offered for is required";

if($price === "" || !is_numeric($price)){
    $errors[] = "Price is required";
}

$stmt = $conn->prepare("SELECT mentor_id, offered_for, created_at, current_status, offering_picture_path FROM mentor_skill_offerings WHERE offering_id = ? LIMIT 1");
$existing = null;
if($stmt){
    $stmt->bind_param("i", $offeringId);
    $stmt->execute();
    $res = $stmt->get_result();
    $existing = $res ? $res->fetch_assoc() : null;
    $stmt->close();
}

if(!$existing || (int)$existing["mentor_id"] !== $mentorId){
    $_SESSION["editOfferStatus"] = "Offering not found";
    $_SESSION["editOfferStatusType"] = "error";
    Header("Location: ..\\View\\editSkillOffering.php");
    exit();
}

$createdAt = $existing["created_at"] ?? "";
$oldOfferedFor = (int)($existing["offered_for"] ?? 0);

if($createdAt !== "" && $oldOfferedFor > 0){
    $expireTs = strtotime($createdAt . " +" . $oldOfferedFor . " hours");
    if($expireTs !== false && time() >= $expireTs){
        $conn->query("UPDATE mentor_skill_offerings SET current_status='expired' WHERE offering_id=".(int)$offeringId);
        $_SESSION["editOfferStatus"] = "This offering has expired and cannot be edited";
        $_SESSION["editOfferStatusType"] = "error";
        Header("Location: ..\\View\\editSkillOffering.php");
        exit();
    }
}

$stmt = $conn->prepare("SELECT category_id FROM mentor_categories WHERE mentor_id = ? AND category_id = ? LIMIT 1");
if($stmt){
    $stmt->bind_param("ii", $mentorId, $categoryId);
    $stmt->execute();
    $stmt->store_result();
    $okCat = $stmt->num_rows > 0;
    $stmt->close();
    if(!$okCat) $errors[] = "Selected category is not in your profile";
}

$stmt = $conn->prepare("SELECT offering_id FROM mentor_skill_offerings WHERE mentor_id = ? AND skill_code = ? AND offering_id <> ? LIMIT 1");
if($stmt){
    $stmt->bind_param("isi", $mentorId, $skillCode, $offeringId);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows > 0){
        $errors[] = "Skill code already exists in your offerings";
    }
    $stmt->close();
}

$mentorPriceRange = $mentor["available_for_price_range"] ?? "";
$minPrice = 0;
$maxPrice = 999999;

if($mentorPriceRange && strpos($mentorPriceRange, "+") !== false){
    $minPrice = (int)str_replace("+", "", $mentorPriceRange);
    $maxPrice = 999999;
}else if($mentorPriceRange && strpos($mentorPriceRange, "-") !== false){
    $pr = array_map("trim", explode("-", $mentorPriceRange));
    $minPrice = isset($pr[0]) ? (int)$pr[0] : 0;
    $maxPrice = isset($pr[1]) ? (int)$pr[1] : 999999;
}

if(is_numeric($price)){
    $p = (float)$price;
    if($p < $minPrice || $p > $maxPrice){
        $errors[] = "Price must be between $minPrice and $maxPrice";
    }
}

function normalizeDay($day){
    $day = strtolower(trim((string)$day));
    $map = [
        "mon"=>"mon","monday"=>"mon",
        "tue"=>"tue","tues"=>"tue","tuesday"=>"tue",
        "wed"=>"wed","wednesday"=>"wed",
        "thu"=>"thu","thur"=>"thu","thurs"=>"thu","thursday"=>"thu",
        "fri"=>"fri","friday"=>"fri",
        "sat"=>"sat","saturday"=>"sat",
        "sun"=>"sun","sunday"=>"sun"
    ];
    return $map[$day] ?? "";
}

function parseTimeTo24($t){
    $t = strtolower(trim((string)$t));
    $t = str_replace([" ", "."], "", $t);
    if($t === "") return "";

    $ampm = "";
    if(substr($t, -2) === "am" || substr($t, -2) === "pm"){
        $ampm = substr($t, -2);
        $t = substr($t, 0, -2);
    }

    $h = 0;
    $m = 0;

    if(strpos($t, ":") !== false){
        $parts = explode(":", $t);
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);
    }else{
        $h = (int)$t;
        $m = 0;
    }

    if($h < 0 || $h > 23) return "";
    if($m < 0 || $m > 59) return "";

    if($ampm !== ""){
        if($h < 1 || $h > 12) return "";
        if($ampm === "pm" && $h < 12) $h += 12;
        if($ampm === "am" && $h === 12) $h = 0;
    }

    $hh = str_pad((string)$h, 2, "0", STR_PAD_LEFT);
    $mm = str_pad((string)$m, 2, "0", STR_PAD_LEFT);
    return $hh . ":" . $mm . ":00";
}

function parseSlots($raw){
    $raw = (string)$raw;
    $chunks = array_map("trim", explode(",", $raw));
    $chunks = array_values(array_filter($chunks, function($v){ return $v !== ""; }));

    $slots = [];

    foreach($chunks as $c){
        $c = trim($c);
        $c = preg_replace('/\s+/', ' ', $c);

        $dayPart = "";
        $timePart = "";

        if(preg_match('/^([A-Za-z]+)\s+(.*)$/', $c, $m)){
            $dayPart = trim($m[1] ?? "");
            $timePart = trim($m[2] ?? "");
        }

        $day = normalizeDay($dayPart);
        if($day === "") continue;

        $timePart = strtolower($timePart);
        $timePart = str_replace([" to "], "-", $timePart);
        $timePart = str_replace(["to"], "-", $timePart);
        $timePart = str_replace(["—","–"], "-", $timePart);

        $start = "";
        $end = "";

        if(strpos($timePart, "-") !== false){
            $tp = array_map("trim", explode("-", $timePart));
            $start = $tp[0] ?? "";
            $end = $tp[1] ?? "";
        }

        $start24 = parseTimeTo24($start);
        $end24 = parseTimeTo24($end);

        if($start24 === "" || $end24 === "") continue;

        $slots[] = ["day"=>$day,"start"=>$start24,"end"=>$end24];
    }

    return $slots;
}

$slots = parseSlots($timeSlotsRaw);
if(count($slots) < 1){
    $errors[] = "Time slot format is invalid. Example: Monday 15:00-18:00, Tuesday 10:00-12:00";
}

$newDbPicPath = $existing["offering_picture_path"] ?? "";

$replaceImage = false;
if(isset($_FILES["offering_picture"]) && $_FILES["offering_picture"]["error"] != 4){
    $fileErr = $_FILES["offering_picture"]["error"];
    $fileSize = $_FILES["offering_picture"]["size"];
    $fileName = $_FILES["offering_picture"]["name"] ?? "";

    if($fileErr != 0){
        $errors[] = "Invalid image upload";
    }else{
        if($fileSize > (10 * 1024 * 1024)){
            $errors[] = "Image size must be less than 10MB";
        }else{
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if(!in_array($ext, ["jpg","jpeg","png"])){
                $errors[] = "Only PNG / JPG / JPEG images are allowed";
            }else{
                $replaceImage = true;
            }
        }
    }
}

if(count($errors) > 0){
    $_SESSION["editOfferStatus"] = implode(" | ", $errors);
    $_SESSION["editOfferStatusType"] = "error";
    Header("Location: ..\\View\\editSkillPanel.php");
    exit();
}

function sanitizeOfferingNameForFile($name){
    $name = trim((string)$name);
    $name = preg_replace('/\s+/', '_', $name);
    $name = preg_replace('/[^A-Za-z0-9_\-]/', '', $name);
    $name = trim($name, "_-");
    return $name;
}

$finalAbsPath = "";
$finalDbPath = "";

if($replaceImage){
    $ext = strtolower(pathinfo($_FILES["offering_picture"]["name"], PATHINFO_EXTENSION));
    if($ext == "jpeg") $ext = "jpg";

    $safeOfferingName = sanitizeOfferingNameForFile($skillTitle);
    if($safeOfferingName === ""){
        $safeOfferingName = sanitizeOfferingNameForFile($skillCode);
    }
    if($safeOfferingName === ""){
        $safeOfferingName = "offering_" . time();
    }

    $uploadDirAbs = dirname(__DIR__, 2) . "\\public\\assets\\uploads\\";
    if(!is_dir($uploadDirAbs)){
        mkdir($uploadDirAbs, 0777, true);
    }

    $finalName = $safeOfferingName . "." . $ext;
    $finalAbsPath = $uploadDirAbs . $finalName;
    $finalDbPath = "public/assets/uploads/" . $finalName;
}

if($prereq === "") $prereq = "None";

$conn->begin_transaction();

try{

    if($replaceImage){
        if(!move_uploaded_file($_FILES["offering_picture"]["tmp_name"], $finalAbsPath)){
            $conn->rollback();
            $_SESSION["editOfferStatus"] = "Could not save offering picture";
            $_SESSION["editOfferStatusType"] = "error";
            Header("Location: ..\\View\\editSkillPanel.php");
            exit();
        }
        $newDbPicPath = $finalDbPath;
    }

    $sql = "UPDATE mentor_skill_offerings 
            SET category_id=?, skill_code=?, skill_title=?, difficulty=?, prerequisites=?, current_status=?, price=?, description=?, offered_for=?, offering_picture_path=? 
            WHERE offering_id=? AND mentor_id=?";
    $stmt = $conn->prepare($sql);

    if(!$stmt){
        $conn->rollback();
        $_SESSION["editOfferStatus"] = "Something went wrong";
        $_SESSION["editOfferStatusType"] = "error";
        Header("Location: ..\\View\\editSkillPanel.php");
        exit();
    }

    $p = (float)$price;
    $of = (int)$offeredFor;
    $mins = (int)$durationMinutes;
    $desc = ($description !== "") ? $description : null;

    $stmt->bind_param(
        "isssssdsisii",
        $categoryId,
        $skillCode,
        $skillTitle,
        $difficulty,
        $prereq,
        $currentStatus,
        $p,
        $desc,
        $of,
        $newDbPicPath,
        $offeringId,
        $mentorId
    );

    $ok = $stmt->execute();
    $stmt->close();

    if(!$ok){
        $conn->rollback();
        $_SESSION["editOfferStatus"] = "Something went wrong";
        $_SESSION["editOfferStatusType"] = "error";
        Header("Location: ..\\View\\editSkillPanel.php");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM offering_time_slots WHERE offering_id = ?");
    if($stmt){
        $stmt->bind_param("i", $offeringId);
        $stmt->execute();
        $stmt->close();
    }

    foreach($slots as $s){
        $sql = "INSERT INTO offering_time_slots(offering_id, day_of_week, start_time, end_time) VALUES (?,?,?,?)";
        $stmt = $conn->prepare($sql);
        if($stmt){
            $dow = $s["day"];
            $st = $s["start"];
            $en = $s["end"];
            $stmt->bind_param("isss", $offeringId, $dow, $st, $en);
            $stmt->execute();
            $stmt->close();
        }
    }

    $stmt = $conn->prepare("DELETE FROM offering_duration_options WHERE offering_id = ?");
    if($stmt){
        $stmt->bind_param("i", $offeringId);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("INSERT INTO offering_duration_options(offering_id, duration_minutes) VALUES (?,?)");
    if($stmt){
        $stmt->bind_param("ii", $offeringId, $mins);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    $_SESSION["editOfferStatus"] = "Offering updated successfully";
    $_SESSION["editOfferStatusType"] = "success";
    Header("Location: ..\\View\\editSkillPanel.php");
    exit();

}catch(Exception $e){
    $conn->rollback();
    $_SESSION["editOfferStatus"] = "Something went wrong";
    $_SESSION["editOfferStatusType"] = "error";
    Header("Location: ..\\View\\editSkillPanel.php");
    exit();
}
?>
