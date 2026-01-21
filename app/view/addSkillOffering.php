<?php
session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    Header("Location: landing.php");
    exit();
}

include "..\\Model\\DatabaseConnection.php";
include "..\\Model\\User.php";
include "..\\Model\\MentorProfile.php";
include "..\\Model\\Skill.php";

$db = new DatabaseConnection();
$conn = $db->getConnection();

$userModel = new User($db);
$mentorModel = new MentorProfile($db);
$skillModel = new Skill($db);

$userId = (int)($_SESSION["user_id"] ?? 0);
$user = $userModel->findById($userId);
$mentor = $mentorModel->getByUserId($userId);

if (!$user || !$mentor) {
    $_SESSION["offerStatus"] = "Something went wrong";
    $_SESSION["offerStatusType"] = "error";
    Header("Location: mentorDashboard.php");
    exit();
}

$statusMsg = $_SESSION["offerStatus"] ?? "";
$statusType = $_SESSION["offerStatusType"] ?? "";
unset($_SESSION["offerStatus"]);
unset($_SESSION["offerStatusType"]);

$mentorId = (int)($mentor["mentor_id"] ?? 0);

$usernameTitle = $user["username"] ?? "Mentor";


$mentorCategoryIds = [];
$sql = "SELECT category_id FROM mentor_categories WHERE mentor_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $mentorId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $mentorCategoryIds[] = (int)$row["category_id"];
        }
    }
    $stmt->close();
}

$selectedCategories = [];
if (count($mentorCategoryIds) > 0 && method_exists($skillModel, "getCategoriesByIds")) {
    $selectedCategories = $skillModel->getCategoriesByIds($mentorCategoryIds);
}

$priceRangeEnum = $mentor["available_for_price_range"] ?? "1000-1999";
$priceRangeEnum = trim((string)$priceRangeEnum);

if ($priceRangeEnum === "") {
    $priceRangeEnum = "1000-1999";
}

$minPrice = 0;
$maxPrice = 999999;

if (strpos($priceRangeEnum, "+") !== false) {
    $minPrice = (int)str_replace("+", "", $priceRangeEnum);
    $maxPrice = 999999;
} else {
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
    <link rel="stylesheet" href="../../public/css/addSkillOfferingCSS.css">
    <script src="..\controller\JS\addSkillOffering.js" defer></script>
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
                    <a class="icon-btn" href="manageSkills.php">
                        <img class="icon-img" src="../../public/assets/preloads/back.png" alt="Back">
                        <span>Back</span>
                    </a>
                </div>

                <div class="card-center">
                    <div class="profile-title">Add Skill Offering</div>
                    <div class="profile-subtitle">
                        <?php echo htmlspecialchars($usernameTitle); ?>, create a new offering learners can buy.
                    </div>
                </div>

                <div class="card-right">
                    <a class="icon-btn" href="mentorDashboard.php">
                        <img class="icon-img" src="../../public/assets/preloads/home.png" alt="Home">
                        <span>Home</span>
                    </a>
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

            <?php if(count($selectedCategories) < 1): ?>
                <div class="empty-note">
                    You have no selected categories in your profile. Please add at least 1 category in your Mentor Profile, then come back.
                </div>
            <?php endif; ?>

            <form method="post" action="..\controller\addSkillOfferingValidation.php" id="addOfferForm" enctype="multipart/form-data">

                <table class="form-table">

                    <tr>
                        <td>Skill Title</td>
                        <td>
                            <input class="input" type="text" name="skill_title" id="skill_title"
                                   placeholder="e.g., Web Development Basics" required>
                        </td>
                    </tr>

                    <tr>
                        <td>Skill Code</td>
                        <td>
                            <input class="input" type="text" name="skill_code" id="skill_code"
                                   placeholder="e.g., WD101" required>
                            <small class="hint">Unique short code for learners</small>
                        </td>
                    </tr>

                    <tr>
                        <td>Skill Category</td>
                        <td>
                            <select class="select" name="category_id" id="category_id"
                                    <?php echo (count($selectedCategories) < 1) ? "disabled" : ""; ?> required>
                                <option value="">Select category</option>
                                <?php foreach($selectedCategories as $c): ?>
                                    <option value="<?php echo (int)$c["category_id"]; ?>">
                                        <?php echo htmlspecialchars($c["category_name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td>Difficulty</td>
                        <td>
                            <select class="select" name="difficulty" id="difficulty"
                                    <?php echo (count($selectedCategories) < 1) ? "disabled" : ""; ?> required>
                                <option value="">Select difficulty</option>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td>Prerequisites</td>
                        <td>
                            <input class="input" type="text" name="prerequisites" id="prerequisites"
                                   placeholder="Optional (leave empty for none)">
                        </td>
                    </tr>

                    <tr>
                        <td>Price (BDT)</td>
                        <td>
                            <input class="input" type="number" name="price" id="price"
                                   min="<?php echo (int)$minPrice; ?>"
                                   max="<?php echo (int)$maxPrice; ?>"
                                   <?php echo (count($selectedCategories) < 1) ? "disabled" : ""; ?> required>
                            <small class="hint">Allowed range: <?php echo htmlspecialchars($priceRangeEnum); ?></small>
                        </td>
                    </tr>

                    <tr>
                        <td>Offered For (hours)</td>
                        <td>
                            <input class="input" type="number" name="offered_for" id="offered_for"
                                   min="1" max="720" placeholder="e.g., 48"
                                   <?php echo (count($selectedCategories) < 1) ? "disabled" : ""; ?> required>
                            <small class="hint">How long this offering is visible to learners (from created time)</small>
                        </td>
                    </tr>

                    <tr>
                        <td>Offered Time Slots</td>
                        <td>
                            <textarea class="textarea" name="time_slots" id="time_slots"
                                      placeholder="Example: Monday 3pm-6pm, Tuesday 4pm-5pm"
                                      <?php echo (count($selectedCategories) < 1) ? "disabled" : ""; ?> required></textarea>
                            <small class="hint">Comma separated. Example: Monday 3pm-6pm, Tuesday 4pm-5pm</small>
                        </td>
                    </tr>

                    <tr>
                        <td>Session Duration (minutes)</td>
                        <td>
                            <input class="input" type="number" name="duration_minutes" id="duration_minutes"
                                   min="15" max="300" placeholder="e.g., 50"
                                   <?php echo (count($selectedCategories) < 1) ? "disabled" : ""; ?> required>
                        </td>
                    </tr>

                    <tr>
                        <td>Description</td>
                        <td>
                            <textarea class="textarea" name="description" id="description" placeholder="Optional"></textarea>
                        </td>
                    </tr>

                    <tr>
                        <td>Offering Picture</td>
                        <td>
                            <input class="input" type="file" name="offering_picture" id="offering_picture"
                                   accept="image/png, image/jpeg" required>
                            <small class="hint">Only PNG / JPG / JPEG allowed</small>
                        </td>
                    </tr>

                </table>

                <div class="card-footer">
                    <button class="btn" type="submit" id="createBtn"
                            <?php echo (count($selectedCategories) < 1) ? "disabled" : ""; ?>>
                        Create Offering
                    </button>
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
