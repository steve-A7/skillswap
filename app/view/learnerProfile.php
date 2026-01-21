<?php
session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    Header("Location: landing.php");
    exit();
}

include "..\\Model\\DatabaseConnection.php";
include "..\\Model\\User.php";
include "..\\Model\\LearnerProfile.php";
include "..\\Model\\Skill.php";

$db = new DatabaseConnection();
$conn = $db->getConnection();

$userModel = new User($db);
$learnerModel = new LearnerProfile($db);
$skillModel = new Skill($db);

$userId = (int)($_SESSION["user_id"] ?? 0);

$user = $userModel->findById($userId);
$learner = $learnerModel->getByUserId($userId);

$statusMsg = $_SESSION["editStatus"] ?? "";
$statusType = $_SESSION["editStatusType"] ?? "";
unset($_SESSION["editStatus"]);
unset($_SESSION["editStatusType"]);

$learnerInterestIds = [];

if ($learner && isset($learner["learner_id"])) {
    $sql = "SELECT category_id FROM learner_interests WHERE learner_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $lid = (int)$learner["learner_id"];
        $stmt->bind_param("i", $lid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $learnerInterestIds[] = (int)$row["category_id"];
            }
        }
        $stmt->close();
    }
}

$allCategories = $skillModel->listAllCategories(300);

$profilePic = $learner["profile_picture_path"] ?? "";
$profilePic = str_replace("\\", "/", $profilePic);

$profilePicUrl = "";
if ($profilePic !== "") {
    $profilePicUrl = "../../" . ltrim($profilePic, "/");
}

$usernameTitle = $user["username"] ?? "Learner";

$paymentDetailVal = "";
$pm = $learner["preferred_payment_method"] ?? "";
if ($pm === "paypal") {
    $paymentDetailVal = $learner["paypal_email"] ?? "";
} else if ($pm === "bkash") {
    $paymentDetailVal = $learner["bkash_number"] ?? "";
} else if ($pm === "nagad") {
    $paymentDetailVal = $learner["nagad_number"] ?? "";
} else if ($pm === "credit_card" || $pm === "debit_card") {
    $paymentDetailVal = $learner["card_last4"] ?? "";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SwillSwap</title>
    <link rel="stylesheet" href="../../public/css/learnerProfileCSS.css">
    <script src="..\controller\JS\editLearner.js" defer></script>
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

            <form id="deleteForm" method="post" action="..\controller\deleteLearnerAccount.php" style="display:none;"></form>

            <form method="post" action="..\controller\editLearnerValidation.php" enctype="multipart/form-data"
                id="editLearnerForm">

                <div class="profile-card">

                    <div class="card-header">

                        <div class="card-left">
                            <a class="icon-btn" href="learnerDashboard.php">
                                <img class="icon-img" src="../../public/assets/preloads/back.png" alt="Back">
                                <span>Back</span>
                            </a>
                        </div>

                        <div class="card-center">
                            <?php if ($profilePic): ?>
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

                            <button class="btn" type="button" id="editBtn">Edit</button>
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
                                <select class="select" name="learner_sex" id="learner_sex" disabled>
                                    <option value="">Select</option>
                                    <option value="male" <?php echo (($learner["sex"] ?? "") == "male") ? "selected" : ""; ?>>Male</option>
                                    <option value="female" <?php echo (($learner["sex"] ?? "") == "female") ? "selected" : ""; ?>>Female</option>
                                    <option value="other" <?php echo (($learner["sex"] ?? "") == "other") ? "selected" : ""; ?>>Other</option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td>Age</td>
                            <td>
                                <input class="input" type="number" name="learner_age" id="learner_age"
                                    value="<?php echo htmlspecialchars($learner["age"] ?? ""); ?>" readonly />
                                <small class="hint">Optional</small>
                            </td>
                        </tr>

                        <tr>
                            <td>Educational qualification</td>
                            <td>
                                <input class="input" type="text" name="learner_edu" id="learner_edu"
                                    value="<?php echo htmlspecialchars($learner["educational_qualification"] ?? ""); ?>" readonly />
                                <small class="hint">Example: BSc in CSE</small>
                            </td>
                        </tr>

                        <tr>
                            <td>Preferred way to learn</td>
                            <td>
                                <select class="select" name="learner_preferred" id="learner_preferred" disabled>
                                    <option value="">Select</option>
                                    <option value="audio" <?php echo (($learner["preferred_way_to_learn"] ?? "") == "audio") ? "selected" : ""; ?>>Audio</option>
                                    <option value="video" <?php echo (($learner["preferred_way_to_learn"] ?? "") == "video") ? "selected" : ""; ?>>Video</option>
                                    <option value="both" <?php echo (($learner["preferred_way_to_learn"] ?? "") == "both") ? "selected" : ""; ?>>Both</option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td>Preferred payment method</td>
                            <td>
                                <select class="select" name="learner_payment_method" id="learner_payment_method" disabled>
                                    <option value="">Select</option>
                                    <option value="paypal" <?php echo (($learner["preferred_payment_method"] ?? "") == "paypal") ? "selected" : ""; ?>>Paypal</option>
                                    <option value="bkash" <?php echo (($learner["preferred_payment_method"] ?? "") == "bkash") ? "selected" : ""; ?>>Bkash</option>
                                    <option value="nagad" <?php echo (($learner["preferred_payment_method"] ?? "") == "nagad") ? "selected" : ""; ?>>Nagad</option>
                                    <option value="credit_card" <?php echo (($learner["preferred_payment_method"] ?? "") == "credit_card") ? "selected" : ""; ?>>Credit Card</option>
                                    <option value="debit_card" <?php echo (($learner["preferred_payment_method"] ?? "") == "debit_card") ? "selected" : ""; ?>>Debit Card</option>
                                </select>

                                <div style="margin-top:10px;">
                                    <input class="input" type="text" name="learner_payment_detail" id="learner_payment_detail"
                                        value="<?php echo htmlspecialchars($paymentDetailVal); ?>" readonly />
                                    <small class="hint">Paypal email / bkash / nagad / last 4 digits of card</small>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>Bio</td>
                            <td>
                                <textarea class="textarea" name="learner_bio" id="learner_bio" maxlength="150"
                                    readonly><?php echo htmlspecialchars($learner["bio"] ?? ""); ?></textarea>
                                <small class="hint">Max 150 characters</small>
                            </td>
                        </tr>

                        <tr>
                            <td>Profile picture</td>
                            <td>
                                <input class="input" type="file" name="profile_picture" id="profile_picture"
                                    accept=".jpg,.jpeg,.png" disabled />
                                <small class="hint">Leave empty to keep current photo</small>
                            </td>
                        </tr>

                        <tr>
                            <td>Interested categories</td>
                            <td>

                                <div id="learnerInterestsBox">
                                    <?php foreach ($allCategories as $cat): ?>
                                        <?php
                                        $cid = (int)$cat["category_id"];
                                        $checked = in_array($cid, $learnerInterestIds) ? "checked" : "";
                                        ?>
                                        <label class="cat-pill">
                                            <input type="checkbox" name="learner_interest_ids[]" value="<?php echo $cid; ?>"
                                                <?php echo $checked; ?> disabled>
                                            <span><?php echo htmlspecialchars($cat["category_name"]); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <div style="margin-top:10px;">
                                    <input class="input" type="text" name="learner_new_interests" id="learner_new_interests"
                                        placeholder="Add new interests (comma separated)" readonly />
                                    <small class="hint">Example: Web Development, UI/UX</small>
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
                                <input class="input" type="password" name="confirm_password" id="confirm_password" readonly
                                    disabled />
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

                    <?php if ($statusMsg): ?>
                        <div class="bottom-status">
                            <div id="serverBottomMsg"
                                class="status-box show <?php echo ($statusType === "error") ? "status-error" : "status-success"; ?>">
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
