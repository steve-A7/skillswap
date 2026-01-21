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
include "..\\Model\\MentorCategory.php";

$db = new DatabaseConnection();
$conn = $db->getConnection();

$userModel = new User($db);
$mentorModel = new MentorProfile($db);
$skillModel = new Skill($db);
$mentorCatModel = new MentorCategory($db);

$userId = (int)($_SESSION["user_id"] ?? 0);

$user = $userModel->findById($userId);
$mentor = $mentorModel->getByUserId($userId);

$statusMsg = $_SESSION["editStatus"] ?? "";
$statusType = $_SESSION["editStatusType"] ?? "";
unset($_SESSION["editStatus"]);
unset($_SESSION["editStatusType"]);

$mentorCategoryIds = [];
if ($mentor && isset($mentor["mentor_id"])) {
    $sql = "SELECT category_id FROM mentor_categories WHERE mentor_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $mid = (int)$mentor["mentor_id"];
        $stmt->bind_param("i", $mid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $mentorCategoryIds[] = (int)$row["category_id"];
            }
        }
        $stmt->close();
    }
}

$randomCategories = [];
if (method_exists($skillModel, "listRandomCategories")) {
    $randomCategories = $skillModel->listRandomCategories(10);
} else {
    $sql = "SELECT category_id, category_code, category_name FROM skill_categories ORDER BY RAND() LIMIT 10";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $randomCategories[] = [
                "category_id" => (int)$row["category_id"],
                "category_code" => $row["category_code"],
                "category_name" => $row["category_name"],
            ];
        }
    }
}

$selectedCategories = [];
if (count($mentorCategoryIds) > 0) {
    if (method_exists($skillModel, "getCategoriesByIds")) {
        $selectedCategories = $skillModel->getCategoriesByIds($mentorCategoryIds);
    } else {
        $in = implode(",", array_map("intval", $mentorCategoryIds));
        if ($in) {
            $sql = "SELECT category_id, category_code, category_name FROM skill_categories WHERE category_id IN ($in)";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $selectedCategories[] = [
                        "category_id" => (int)$row["category_id"],
                        "category_code" => $row["category_code"],
                        "category_name" => $row["category_name"],
                    ];
                }
            }
        }
    }
}

$catMap = [];
foreach ($selectedCategories as $c) {
    $catMap[(int)$c["category_id"]] = $c;
}
foreach ($randomCategories as $c) {
    $cid = (int)$c["category_id"];
    if (!isset($catMap[$cid])) {
        $catMap[$cid] = $c;
    }
}
$allCategories = array_values($catMap);

$qualExp = $mentor["qualification_experience"] ?? "";
$qual = "";
$exp = "";
if ($qualExp) {
    $parts = array_map("trim", explode("|", $qualExp));
    $qual = $parts[0] ?? "";
    $exp = $parts[1] ?? "";
}

$paymentDetailVal = "";
$pm = $mentor["payment_method"] ?? "";
if ($pm === "paypal") {
    $paymentDetailVal = $mentor["paypal_email"] ?? "";
} else if ($pm === "bkash") {
    $paymentDetailVal = $mentor["bkash_number"] ?? "";
} else if ($pm === "nagad") {
    $paymentDetailVal = $mentor["nagad_number"] ?? "";
} else if ($pm === "credit_card" || $pm === "debit_card") {
    $paymentDetailVal = $mentor["card_last4"] ?? "";
}

$profilePic = $mentor["profile_picture_path"] ?? "";
$profilePic = str_replace("\\", "/", $profilePic);

$profilePicUrl = "";
if ($profilePic !== "") {
    $profilePicUrl = "../../" . ltrim($profilePic, "/");
}

$usernameTitle = $user["username"] ?? "Mentor";
?>

<html>
<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SwillSwap</title>
    <link rel="stylesheet" href="../../public/css/mentorProfileCSS.css">
    <script src="..\controller\JS\editMentor.js" defer></script>
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

        <form method="post" action="..\ controller\logout.php">
            <button class="btn" type="submit">Logout</button>
        </form>
    </div>

    <hr class="hr-line">

    <div class="profile-card-wrap">

        <form id="deleteForm" method="post" action="..\controller\deleteMentorAccount.php" style="display:none;"></form>

        <form method="post" action="..\controller\editMentorValidation.php" enctype="multipart/form-data" id="editMentorForm">

            <div class="profile-card">
                <div class="card-header">

                    <div class="card-left">
                        <a class="icon-btn" href="mentorDashboard.php">
                            <img class="icon-img" src="../../public/assets/preloads/back.png" alt="Back">
                            <span>Back</span>
                        </a>
                    </div>

                    <div class="card-center">
                        <?php if($profilePic): ?>
                            <img class="profile-pic" src="<?php echo htmlspecialchars($profilePicUrl); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <img class="profile-pic" src="../../public/assets/preloads/user.svg" alt="Profile Picture">
                        <?php endif; ?>

                        <div class="profile-title">
                            <?php echo htmlspecialchars($usernameTitle); ?>'s Profile Details
                        </div>
                    </div>

                    <div class="card-right">
                        <button class="icon-btn" type="button" id="deleteBtn" title="Delete Account">
                            <img class="icon-img" src="../../public/assets/preloads/trash.svg" alt="Delete">
                            <span>Delete</span>
                        </button>

                        <button class="btn" type="button" id="editBtn">
                            Edit</button>
                    </div>

                </div>

                <div class="toast-area">
                    <div id="clientMsg" class="toast" style="display:none;"></div>
                </div>

                <table class="form-table">

                    <tr>
                        <td>Username</td>
                        <td>
                            <input class="input" type="text" name="username" id="username"
                                value="<?php echo htmlspecialchars($user["username"] ?? ""); ?>" readonly />
                            <small class="hint">Username can't be modified</small>
                        </td>
                    </tr>

                    <tr>
                        <td>Email</td>
                        <td>
                            <input class="input" type="text" name="email" id="email"
                                value="<?php echo htmlspecialchars($user["email"] ?? ""); ?>" readonly />
                            <small class="hint">Email can't be modified</small>
                        </td>
                    </tr>

                    <tr>
                        <td>Password</td>
                        <td>
                            <input class="input" type="password" id="password_mask" value="........" disabled />
                            <small class="hint">Password is hidden for security (change using New Password below)</small>
                        </td>
                    </tr>

                    <tr>
                        <td>Sex</td>
                        <td>
                            <select class="select" name="mentor_sex" id="mentor_sex" disabled>
                                <option value="">Select</option>
                                <option value="male" <?php echo (($mentor["sex"] ?? "") == "male") ? "selected" : ""; ?>>Male</option>
                                <option value="female" <?php echo (($mentor["sex"] ?? "") == "female") ? "selected" : ""; ?>>Female</option>
                                <option value="other" <?php echo (($mentor["sex"] ?? "") == "other") ? "selected" : ""; ?>>Other</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td>Age</td>
                        <td>
                            <input class="input" type="text" name="mentor_age" id="mentor_age"
                                value="<?php echo htmlspecialchars($mentor["age"] ?? ""); ?>" readonly />
                        </td>
                    </tr>

                    <tr>
                        <td>Qualification</td>
                        <td>
                            <input class="input" type="text" name="mentor_qualification" id="mentor_qualification"
                                value="<?php echo htmlspecialchars($qual); ?>" readonly />
                        </td>
                    </tr>

                    <tr>
                        <td>Experience</td>
                        <td>
                            <input class="input" type="text" name="mentor_experience" id="mentor_experience"
                                value="<?php echo htmlspecialchars($exp); ?>" readonly />
                        </td>
                    </tr>

                    <tr>
                        <td>Language proficiency</td>
                        <td>
                            <input class="input" type="text" name="mentor_language" id="mentor_language"
                                value="<?php echo htmlspecialchars($mentor["language_proficiency"] ?? ""); ?>" readonly />
                        </td>
                    </tr>

                    <tr>
                        <td>Price range</td>
                        <td>
                            <select class="select" name="mentor_price_range" id="mentor_price_range" disabled>
                                <option value="">Select</option>
                                <option value="500-999" <?php echo (($mentor["available_for_price_range"] ?? "") == "500-999") ? "selected" : ""; ?>>500-999</option>
                                <option value="1000-1999" <?php echo (($mentor["available_for_price_range"] ?? "") == "1000-1999") ? "selected" : ""; ?>>1000-1999</option>
                                <option value="2000-2999" <?php echo (($mentor["available_for_price_range"] ?? "") == "2000-2999") ? "selected" : ""; ?>>2000-2999</option>
                                <option value="3000-4999" <?php echo (($mentor["available_for_price_range"] ?? "") == "3000-4999") ? "selected" : ""; ?>>3000-4999</option>
                                <option value="5000+" <?php echo (($mentor["available_for_price_range"] ?? "") == "5000+") ? "selected" : ""; ?>>5000+</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td>Preferred mentoring</td>
                        <td>
                            <select class="select" name="mentor_preferred" id="mentor_preferred" disabled>
                                <option value="">Select</option>
                                <option value="audio" <?php echo (($mentor["preferred_mentoring"] ?? "") == "audio") ? "selected" : ""; ?>>Audio</option>
                                <option value="video" <?php echo (($mentor["preferred_mentoring"] ?? "") == "video") ? "selected" : ""; ?>>Video</option>
                                <option value="both" <?php echo (($mentor["preferred_mentoring"] ?? "") == "both") ? "selected" : ""; ?>>Both</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td>Payment method</td>
                        <td>
                            <select class="select" name="mentor_payment_method" id="mentor_payment_method" disabled>
                                <option value="">Select</option>
                                <option value="paypal" <?php echo (($mentor["payment_method"] ?? "") == "paypal") ? "selected" : ""; ?>>Paypal</option>
                                <option value="bkash" <?php echo (($mentor["payment_method"] ?? "") == "bkash") ? "selected" : ""; ?>>Bkash</option>
                                <option value="nagad" <?php echo (($mentor["payment_method"] ?? "") == "nagad") ? "selected" : ""; ?>>Nagad</option>
                                <option value="credit_card" <?php echo (($mentor["payment_method"] ?? "") == "credit_card") ? "selected" : ""; ?>>Credit Card</option>
                                <option value="debit_card" <?php echo (($mentor["payment_method"] ?? "") == "debit_card") ? "selected" : ""; ?>>Debit Card</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td>Payment detail</td>
                        <td>
                            <input class="input" type="text" name="mentor_payment_detail" id="mentor_payment_detail"
                                value="<?php echo htmlspecialchars($paymentDetailVal); ?>" readonly />
                            <small class="hint">Paypal email / Phone / Last 4 digits</small>
                        </td>
                    </tr>

                    <tr>
                        <td>Bio</td>
                        <td>
                            <textarea class="textarea" name="mentor_bio" id="mentor_bio" readonly><?php echo htmlspecialchars($mentor["bio"] ?? ""); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <td>Profile picture</td>
                        <td>
                            <input class="input" type="file" name="profile_picture" id="profile_picture" accept=".jpg,.jpeg,.png" disabled />
                            <small class="hint">Leave empty to keep current photo</small>
                        </td>
                    </tr>

                    <tr>
                        <td>Skill categories</td>
                        <td>
                            <div id="mentorCategoriesBox">
                                <?php foreach($allCategories as $cat): ?>
                                    <?php
                                        $cid = (int)$cat["category_id"];
                                        $checked = in_array($cid, $mentorCategoryIds) ? "checked" : "";
                                    ?>
                                    <label class="cat-pill">
                                        <input type="checkbox" name="mentor_category_ids[]" value="<?php echo $cid; ?>" <?php echo $checked; ?> disabled>
                                        <span><?php echo htmlspecialchars($cat["category_name"]); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div style="margin-top:10px;">
                                <input class="input" type="text" name="mentor_new_categories" id="mentor_new_categories"
                                    placeholder="Add new categories (comma separated)" readonly />
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td>New Password</td>
                        <td>
                            <input class="input" type="password" name="new_password" id="new_password" readonly disabled />
                            <small class="hint">Leave empty if you do not want to change password</small>
                        </td>
                    </tr>

                    <tr id="confirmRow" style="display:none;">
                        <td>Retype Password</td>
                        <td>
                            <input class="input" type="password" name="confirm_password" id="confirm_password" readonly disabled />
                            <small class="hint" id="retypeHint"></small>
                        </td>
                    </tr>

                </table>

                <div class="card-footer">
                    <button class="btn" type="submit" id="saveBtn" style="display:none;">Save</button>
                </div>

                <div class="bottom-status">
                    <div id="bottomMsg" class="status-box" style="display:none;"></div>
                </div>

                <?php if($statusMsg): ?>
                <div class="bottom-status">
                    <div id="serverBottomMsg" class="status-box show <?php echo ($statusType === "error") ? "status-error" : "status-success"; ?>">
                        <?php echo htmlspecialchars($statusMsg); ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

        </form>

    </div>

</div>

<footer class="footer-bar">
    <span>Copyright © 2026 SkillSwap. All rights reserved.</span>
    </footer>

</body>
</html>
