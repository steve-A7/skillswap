<?php
session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if(!$isLoggedIn){
    Header("Location: landing.php");
    exit();
}

include "..\\Model\\DatabaseConnection.php";
include "..\\Model\\User.php";
include "..\\Model\\MentorProfile.php";
include "..\\Model\\Skill.php";
include "..\\Model\\MentorSkillOffering.php";
include "..\\Model\\OfferingTimeSlot.php";
include "..\\Model\\OfferingDurationOption.php";

$db = new DatabaseConnection();
$conn = $db->getConnection();

$userModel = new User($db);
$mentorModel = new MentorProfile($db);
$skillModel = new Skill($db);

$offeringModel = new MentorSkillOffering($db);
$slotModel = new OfferingTimeSlot($db);
$durationModel = new OfferingDurationOption($db);

$userId = (int)($_SESSION["user_id"] ?? 0);
$user = $userModel->findById($userId);
$mentor = $mentorModel->getByUserId($userId);

if(!$user || !$mentor){
    $_SESSION["editOfferStatus"] = "Something went wrong";
    $_SESSION["editOfferStatusType"] = "error";
    Header("Location: editSkillOffering.php");
    exit();
}

$mentorId = (int)($mentor["mentor_id"] ?? 0);
$usernameTitle = $user["username"] ?? "Mentor";

$selectedOfferingId = (int)($_SESSION["selectedOfferingId"] ?? 0);
if($selectedOfferingId <= 0){
    $_SESSION["editOfferStatus"] = "No offering selected";
    $_SESSION["editOfferStatusType"] = "error";
    Header("Location: editSkillOffering.php");
    exit();
}

$off = $offeringModel->getById($selectedOfferingId);
if(!$off || (int)$off["mentor_id"] !== $mentorId){
    $_SESSION["editOfferStatus"] = "Offering not found";
    $_SESSION["editOfferStatusType"] = "error";
    Header("Location: editSkillOffering.php");
    exit();
}

$createdAt = $off["created_at"] ?? "";
$offeredFor = (int)($off["offered_for"] ?? 0);

if($createdAt !== "" && $offeredFor > 0){
    $expireTs = strtotime($createdAt . " +" . $offeredFor . " hours");
    if($expireTs !== false && time() >= $expireTs){
        $conn->query("UPDATE mentor_skill_offerings SET current_status='expired' WHERE offering_id=".(int)$selectedOfferingId);
        $_SESSION["editOfferStatus"] = "This offering has expired and cannot be edited";
        $_SESSION["editOfferStatusType"] = "error";
        Header("Location: editSkillOffering.php");
        exit();
    }
}

$mentorCategoryIds = [];
$sql = "SELECT category_id FROM mentor_categories WHERE mentor_id = ?";
$stmt = $conn->prepare($sql);
if($stmt){
    $stmt->bind_param("i", $mentorId);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res){
        while($row = $res->fetch_assoc()){
            $mentorCategoryIds[] = (int)$row["category_id"];
        }
    }
    $stmt->close();
}

$selectedCategories = [];
if(count($mentorCategoryIds) > 0 && method_exists($skillModel, "getCategoriesByIds")){
    $selectedCategories = $skillModel->getCategoriesByIds($mentorCategoryIds);
}

$slots = $slotModel->listByOffering($selectedOfferingId);
$durationRows = $durationModel->listByOffering($selectedOfferingId);

function slotDayLabel($d){
    $d = strtolower(trim((string)$d));
    $map = [
        "sat" => "Saturday",
        "sun" => "Sunday",
        "mon" => "Monday",
        "tue" => "Tuesday",
        "wed" => "Wednesday",
        "thu" => "Thursday",
        "fri" => "Friday"
    ];
    return $map[$d] ?? (($d !== "") ? $d : "Unknown");
}

$timeSlotsText = "";
if(count($slots) > 0){
    $parts = [];

    foreach($slots as $s){
        $day = $s["day_of_week"] ?? "";
        $st = $s["start_time"] ?? "";
        $en = $s["end_time"] ?? "";

        if($st !== "" && $en !== ""){
            $st = substr($st, 0, 5);
            $en = substr($en, 0, 5);

            $parts[] = slotDayLabel($day) . " " . $st . "-" . $en;
        }
    }

    $timeSlotsText = implode(", ", $parts);
}


$durationMinutes = 0;
if(count($durationRows) > 0){
    $durationMinutes = (int)($durationRows[0]["duration_minutes"] ?? 0);
}

$statusMsg = $_SESSION["editOfferStatus"] ?? "";
$statusType = $_SESSION["editOfferStatusType"] ?? "";
unset($_SESSION["editOfferStatus"]);
unset($_SESSION["editOfferStatusType"]);

$offerPic = $off["offering_picture_path"] ?? "";
$offerPic = str_replace("\\", "/", $offerPic);

$offerPicUrl = "";
if($offerPic !== ""){
    $offerPicUrl = "../../" . ltrim($offerPic, "/");
}

$priceRangeEnum = $mentor["available_for_price_range"] ?? "1000-1999";
$priceRangeEnum = trim((string)$priceRangeEnum);

if($priceRangeEnum === ""){
    $priceRangeEnum = "1000-1999";
}

$minPrice = 0;
$maxPrice = 999999;

if(strpos($priceRangeEnum, "+") !== false){
    $minPrice = (int)str_replace("+", "", $priceRangeEnum);
    $maxPrice = 999999;
}else{
    $parts = explode("-", $priceRangeEnum);
    $minPrice = isset($parts[0]) ? (int)trim($parts[0]) : 0;
    $maxPrice = isset($parts[1]) ? (int)trim($parts[1]) : 999999;
}
?>

<html>
<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SwillSwap</title>
    <link rel="stylesheet" href="../../public/css/editSkillPanelCSS.css">
    <script src="../controller/JS/editSkillPanel.js" defer></script>
</head>

<body class="view-mode">

<div class="bg-layer"></div>
<div class="tint-layer"></div>

<div class="page">

    <div class="topbar">
        <div class="logo-wrap">
            <img class="logo-img" src="../../public/assets/preloads/logo.svg" alt="Logo">
            <span class="logo-text">SkillSwap</span>
        </div>

        <form method="post" action="..\controller\logout.php">
            <button class="btn" type="submit">Logout</button>
        </form>
    </div>

    <hr class="hr-line">

    <div class="profile-card-wrap">
        <div class="profile-card">

            <div class="card-header">
                <div class="card-left">
                    <a class="icon-btn" href="editSkillOffering.php">
                        <img class="icon-img" src="../../public/assets/preloads/back.png" alt="Back">
                        <span>Back</span>
                    </a>
                </div>

                <div class="card-center">
                    <?php if($offerPicUrl !== ""): ?>
                        <img class="profile-pic" src="<?php echo htmlspecialchars($offerPicUrl); ?>" alt="Offering">
                    <?php else: ?>
                        <img class="profile-pic" src="../../public/assets/preloads/Edit_Skill_Offerings.png" alt="Offering">
                    <?php endif; ?>

                    <div class="profile-title">Edit Skill Panel</div>
                    <div class="profile-subtitle">
                        <?php echo htmlspecialchars($usernameTitle); ?>, edit everything and save changes.
                    </div>
                </div>

                <div class="card-right">
                    <a class="icon-btn" href="mentorDashboard.php">
                        <img class="icon-img" src="../../public/assets/preloads/home.png" alt="Home">
                        <span>Home</span>
                    </a>
                </div>
                <div class="card-right">
                    <button class="btn" type="button" id="editBtn">Edit</button>
                    <button class="btn" type="button" id="cancelBtn" style="display:none;">Cancel</button>
                </div>
            </div>

            <div class="toast-area">
                <?php if($statusMsg): ?>
                    <div id="serverMsg" class="toast show <?php echo ($statusType === "error") ? "toast-error" : "toast-success"; ?>">
                        <?php echo htmlspecialchars($statusMsg); ?>
                    </div>
                <?php endif; ?>
                <div id="clientMsg" class="toast" style="display:none;"></div>
            </div>

            <form method="post" action="..\controller\editSkillPanelValidation.php" id="editOfferForm" enctype="multipart/form-data">

                <input type="hidden" name="offering_id" value="<?php echo (int)$selectedOfferingId; ?>">

                <table class="form-table">

                    <tr>
                        <td>Skill Title</td>
                        <td>
                            <input class="input lockable" type="text" name="skill_title" id="skill_title"
                                   value="<?php echo htmlspecialchars($off["skill_title"] ?? ""); ?>" disabled required>
                        </td>
                    </tr>

                    <tr>
                        <td>Skill Code</td>
                        <td>
                            <input class="input lockable" type="text" name="skill_code" id="skill_code"
                                   value="<?php echo htmlspecialchars($off["skill_code"] ?? ""); ?>" disabled required>
                            <small class="hint">Must be unique for your offerings</small>
                        </td>
                    </tr>

                    <tr>
                        <td>Skill Category</td>
                        <td>
                            <select class="select lockable" name="category_id" id="category_id" disabled required>
                                <option value="">Select category</option>
                                <?php foreach($selectedCategories as $c): ?>
                                    <option value="<?php echo (int)$c["category_id"]; ?>"
                                        <?php echo ((int)$c["category_id"] === (int)($off["category_id"] ?? 0)) ? "selected" : ""; ?>>
                                        <?php echo htmlspecialchars($c["category_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td>Difficulty</td>
                        <td>
                            <select class="select lockable" name="difficulty" id="difficulty" disabled required>
                                <option value="">Select difficulty</option>
                                <option value="beginner" <?php echo (($off["difficulty"] ?? "") === "beginner") ? "selected" : ""; ?>>Beginner</option>
                                <option value="intermediate" <?php echo (($off["difficulty"] ?? "") === "intermediate") ? "selected" : ""; ?>>Intermediate</option>
                                <option value="advanced" <?php echo (($off["difficulty"] ?? "") === "advanced") ? "selected" : ""; ?>>Advanced</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td>Prerequisites</td>
                        <td>
                            <input class="input lockable" type="text" name="prerequisites" id="prerequisites"
                                   value="<?php echo htmlspecialchars($off["prerequisites"] ?? ""); ?>" disabled>
                        </td>
                    </tr>

                    <tr>
                        <td>Price (BDT)</td>
                        <td>
                            <input class="input lockable" type="number" name="price" id="price"
                                   min="<?php echo (int)$minPrice; ?>"
                                   max="<?php echo (int)$maxPrice; ?>"
                                   value="<?php echo htmlspecialchars((string)($off["price"] ?? 0)); ?>" disabled required>
                            <small class="hint">Allowed range: <?php echo htmlspecialchars($priceRangeEnum); ?></small>
                        </td>
                    </tr>

                    <tr>
                        <td>Offered For (hours)</td>
                        <td>
                            <input class="input lockable" type="number" name="offered_for" id="offered_for"
                                   min="1" max="720" value="<?php echo (int)($off["offered_for"] ?? 1); ?>" disabled required>
                            <small class="hint">Visibility uptime (from created time)</small>
                        </td>
                    </tr>

                    <tr>
                        <td>Offered Time Slots</td>
                        <td>
                            <textarea class="textarea lockable" name="time_slots" id="time_slots" disabled required><?php echo htmlspecialchars($timeSlotsText); ?></textarea>
                            <small class="hint">Comma separated. Example: Monday 15:00-18:00, Tuesday 10:00-12:00</small>
                        </td>
                    </tr>

                    <tr>
                        <td>Session Duration (minutes)</td>
                        <td>
                            <input class="input lockable" type="number" name="duration_minutes" id="duration_minutes"
                                   min="15" max="300" value="<?php echo (int)$durationMinutes; ?>" disabled required>
                        </td>
                    </tr>

                    <tr>
                        <td>Description</td>
                        <td>
                            <textarea class="textarea lockable" name="description" id="description" disabled><?php echo htmlspecialchars($off["description"] ?? ""); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <td>Status</td>
                        <td>
                            <select class="select lockable" name="current_status" id="current_status" disabled required>
                                <option value="available" <?php echo (($off["current_status"] ?? "") === "available") ? "selected" : ""; ?>>available</option>
                                <option value="active" <?php echo (($off["current_status"] ?? "") === "active") ? "selected" : ""; ?>>active</option>
                                <option value="booked" <?php echo (($off["current_status"] ?? "") === "booked") ? "selected" : ""; ?>>booked</option>
                                <option value="completed" <?php echo (($off["current_status"] ?? "") === "completed") ? "selected" : ""; ?>>completed</option>
                                <option value="expired" <?php echo (($off["current_status"] ?? "") === "expired") ? "selected" : ""; ?>>expired</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td>Offering Picture</td>
                        <td>
                            <input class="input lockable" type="file" name="offering_picture" id="offering_picture"
                                   accept="image/png, image/jpeg" disabled>
                            <small class="hint">Upload only if you want to replace the image (PNG/ JPG/JPEG)</small>
                        </td>
                    </tr>

                </table>

                <div class="card-footer">
                    <button class="btn" type="submit" id="saveBtn" style="display:none;">Save</button>
                </div>

            </form>

        </div>
    </div>

</div>

<footer class="footer-bar">
    <span>Copyright © 2026 SkillSwap. All rights reserved.</span>
</footer>

</body>
</html>
