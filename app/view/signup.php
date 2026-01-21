<?php
session_start();
include "../Model/DatabaseConnection.php";
include "../Model/Skill.php";

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if ($isLoggedIn) {
    if(($_SESSION["Role"] ?? "") == "learner"){
        Header("Location: ..\\View\\learnerDashboard.php");
        exit();
    }else if(($_SESSION["Role"] ?? "") == "mentor"){
        Header("Location: ..\\View\\mentorDashboard.php");
        exit();
    }else{
        exit();
    }
}

$db = new DatabaseConnection();
$skillModel = new Skill($db);

$previousValues = $_SESSION["signupPreviousValues"] ?? [];

$roleVal = $previousValues["role"] ?? "learner";

/* -------- helper -------- */
function _as_int_array($arr){
    if(!is_array($arr)) return [];
    $out = [];
    foreach($arr as $v){
        $out[] = (int)$v;
    }
    return $out;
}

function buildCategoryList($skillModel, $selectedIds, $fallbackList, $limit = 10){
    $out = [];
    $used = [];

    foreach($selectedIds as $sid){
        $cat = $skillModel->getCategoryById($sid);
        if($cat){
            $out[] = $cat;
            $used[(int)$cat["category_id"]] = true;
            if(count($out) >= $limit) return $out;
        }
    }

    foreach($fallbackList as $c){
        $cid = (int)$c["category_id"];
        if(isset($used[$cid])) continue;
        $out[] = $c;
        $used[$cid] = true;
        if(count($out) >= $limit) break;
    }

    return $out;
}

$randomCats = $skillModel->listRandomCategories(10);
if(count($randomCats) == 0){
    $randomCats = $skillModel->listAllCategories(10);
}

$selectedLearnerCategoryIds = _as_int_array($previousValues["learner_category_ids"] ?? []);
$selectedMentorCategoryIds  = _as_int_array($previousValues["mentor_category_ids"] ?? []);

$learnerCategories = buildCategoryList($skillModel, $selectedLearnerCategoryIds, $randomCats, 10);
$mentorCategories  = buildCategoryList($skillModel, $selectedMentorCategoryIds,  $randomCats, 10);

/* -------- errors -------- */
$roleErr = $_SESSION["roleErr"] ?? "";
$usernameErr = $_SESSION["usernameErr"] ?? "";
$emailErr = $_SESSION["emailErr"] ?? "";
$passwordErr = $_SESSION["passwordErr"] ?? "";
$confirmPasswordErr = $_SESSION["confirmPasswordErr"] ?? "";
$imageErr = $_SESSION["imageErr"] ?? "";

$mentorCategoriesErr = $_SESSION["mentorCategoriesErr"] ?? "";
$mentorSexErr = $_SESSION["mentorSexErr"] ?? "";
$mentorAgeErr = $_SESSION["mentorAgeErr"] ?? "";
$mentorQualificationErr = $_SESSION["mentorQualificationErr"] ?? "";
$mentorExperienceErr = $_SESSION["mentorExperienceErr"] ?? "";
$mentorLanguageErr = $_SESSION["mentorLanguageErr"] ?? "";
$mentorPriceErr = $_SESSION["mentorPriceErr"] ?? "";
$mentorPreferredErr = $_SESSION["mentorPreferredErr"] ?? "";
$mentorPaymentMethodErr = $_SESSION["mentorPaymentMethodErr"] ?? "";
$mentorPaymentDetailErr = $_SESSION["mentorPaymentDetailErr"] ?? "";

$learnerCategoriesErr = $_SESSION["learnerCategoriesErr"] ?? "";
$learnerSexErr = $_SESSION["learnerSexErr"] ?? "";
$learnerAgeErr = $_SESSION["learnerAgeErr"] ?? "";
$learnerEduErr = $_SESSION["learnerEduErr"] ?? "";
$learnerPreferredErr = $_SESSION["learnerPreferredErr"] ?? "";
$learnerPaymentMethodErr = $_SESSION["learnerPaymentMethodErr"] ?? "";
$learnerPaymentDetailErr = $_SESSION["learnerPaymentDetailErr"] ?? "";
$learnerBioErr = $_SESSION["learnerBioErr"] ?? "";

/* cleanup */
unset($_SESSION["roleErr"], $_SESSION["usernameErr"], $_SESSION["emailErr"], $_SESSION["passwordErr"], $_SESSION["confirmPasswordErr"], $_SESSION["imageErr"]);
unset($_SESSION["mentorCategoriesErr"], $_SESSION["mentorSexErr"], $_SESSION["mentorAgeErr"], $_SESSION["mentorQualificationErr"], $_SESSION["mentorExperienceErr"], $_SESSION["mentorLanguageErr"], $_SESSION["mentorPriceErr"], $_SESSION["mentorPreferredErr"], $_SESSION["mentorPaymentMethodErr"], $_SESSION["mentorPaymentDetailErr"]);
unset($_SESSION["learnerCategoriesErr"], $_SESSION["learnerSexErr"], $_SESSION["learnerAgeErr"], $_SESSION["learnerEduErr"], $_SESSION["learnerPreferredErr"], $_SESSION["learnerPaymentMethodErr"], $_SESSION["learnerPaymentDetailErr"], $_SESSION["learnerBioErr"]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SkillSwap - Registration</title>

    <link rel="stylesheet" href="../../public/css/signupCSS.css">
    <script src="..\\controller\\JS\\usernameCheck.js"></script>
    <script src="..\\controller\\JS\\signupToggle.js"></script>
    <script src="..\\controller\\JS\\paymentToggle.js"></script>
    <script src="..\\controller\\JS\\emailCheck.js"></script>
    <script src="..\\controller\\JS\\passwordMatch.js"></script>
    <script src="..\\controller\\JS\\interestSelect.js"></script>
</head>

<body>

    <div class="bg-layer"></div>
    <div class="tint-layer"></div>

    <div class="page">

        <div class="topbar">
            <div class="logo-wrap">
                <img class="logo-img" src="../../public/assets/preloads/logo.svg" alt="Logo">
                <span class="logo-text">SkillSwap</span>
            </div>

            <div class="nav-right">
                <form class="action-form" method="post" action="..\controller\landingNav.php">
                    <input type="hidden" name="nav" value="Home" />
                    <button class="btn" type="submit">Home</button>
                </form>

                 <form class="action-form" method="post" action="..\controller\landingNav.php">
                    <input type="hidden" name="nav" value="AboutUs" />
                    <button class="btn" type="submit">About Us</button>
                </form>

                 <form class="action-form" method="post" action="..\controller\landingNav.php">
                    <input type="hidden" name="nav" value="SignUp" />
                    <button class="btn" type="submit">Sign Up</button>
                </form>

                <form class="action-form" method="post" action="..\controller\landingNav.php">
                    <input type="hidden" name="nav" value="Login" />
                    <button class="btn" type="submit">Login</button>
                </form>
            </div>
        </div>

        <hr class="hr-line">

        <div class="hero-wrap">
            <div class="hero-card signup-card">

                <div class="signup-title">Registration</div>
                <div class="signup-subtitle">Create your SkillSwap account</div>

                <form method="post" action="..\controller\signupValidation.php" enctype="multipart/form-data">

                    <div class="form-grid">

                        <div class="form-row full">
                            <div class="form-label">Register as</div>
                            <div class="pill-row">
                                <label class="role-pill">
                                    <input type="radio" name="role" value="learner"
                                        <?php echo ($roleVal == "learner") ? "checked" : ""; ?>
                                        onclick="toggleRoleSections('learner'); togglePaymentFields('learner');">
                                    Learner
                                </label>

                                <label class="role-pill">
                                    <input type="radio" name="role" value="mentor"
                                        <?php echo ($roleVal == "mentor") ? "checked" : ""; ?>
                                        onclick="toggleRoleSections('mentor'); togglePaymentFields('mentor');">
                                    Mentor
                                </label>
                            </div>
                            <div class="err"><?php echo $roleErr; ?></div>
                        </div>

                        <div class="form-row">
                            <div class="form-label">Username</div>
                            <input class="input" type="text" name="username" id="username"
                                value="<?php echo $previousValues["username"] ?? '' ?>"
                                onkeyup="checkUsername(this.value)" />
                            <div id="usernameStatus" class="inline-status"></div>
                            <div class="err"><?php echo $usernameErr; ?></div>
                        </div>

                        <div class="form-row">
                            <div class="form-label">Email</div>
                            <input class="input" type="text" name="email" id="email"
                                value="<?php echo $previousValues["email"] ?? '' ?>"
                                onkeyup="checkEmail(this.value)" />
                            <div id="emailStatus" class="inline-status"></div>
                            <div class="err"><?php echo $emailErr; ?></div>
                        </div>

                        <div class="form-row">
                            <div class="form-label">Password</div>
                            <input class="input" type="password" name="password" id="password"
                                value="<?php echo $previousValues["password"] ?? '' ?>"
                                onkeyup="checkPasswordMatch()" />
                            <div class="err"><?php echo $passwordErr; ?></div>
                        </div>

                        <div class="form-row">
                            <div class="form-label">Retype Password</div>
                            <input class="input" type="password" name="confirm_password" id="confirm_password"
                                value="<?php echo $previousValues["confirm_password"] ?? '' ?>"
                                onkeyup="checkPasswordMatch()" />
                            <div id="passwordMatchStatus" class="inline-status"></div>
                            <div class="err"><?php echo $confirmPasswordErr; ?></div>
                        </div>

                        <div class="form-row full">
                            <div class="form-label">Profile Picture</div>
                            <input class="input" type="file" name="profile_picture" id="profile_picture" accept=".jpg,.jpeg,.png" />
                            <div class="hint">Only jpg / jpeg / png allowed</div>
                            <div class="err"><?php echo $imageErr; ?></div>
                        </div>

                        <div class="section-wrap">

                            <div id="mentorSection">

                                <div class="form-row full">
                                    <div class="form-label">Mentor Categories</div>
                                    <div id="mentorCategoriesBox" class="cat-box">
                                        <?php foreach ($mentorCategories as $cat): ?>
                                            <?php
                                            $cid = (int)$cat["category_id"];
                                            $checked = in_array($cid, $selectedMentorCategoryIds) ? "checked" : "";
                                            ?>
                                            <label class="cat-pill">
                                                <input type="checkbox" name="mentor_category_ids[]" value="<?php echo $cid; ?>" <?php echo $checked; ?>>
                                                <span><?php echo htmlspecialchars($cat["category_name"]); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="err"><?php echo $mentorCategoriesErr; ?></div>
                                </div>
                                <div class="form-row full">
                                
                                <div class="form-label">Add New Categories (comma separated)</div>

                                    <input class="input" type="text"
                                            name="mentor_new_categories"
                                            id="mentor_new_categories"
                                            placeholder="Example: Web Development, UI/UX, Data Science"
                                            value="<?php echo htmlspecialchars($previousValues["mentor_new_categories"] ?? ""); ?>" />
                                            <div class="err"><?php echo $mentorCategoriesErr; ?></div>
                                </div>


                                <div class="form-row">
                                    <div class="form-label">Sex</div>
                                    <select class="select" name="mentor_sex" id="mentor_sex">
                                        <option value="">Select</option>
                                        <option value="male" <?php echo (($previousValues["mentor_sex"] ?? "")=="male")?"selected":""; ?>>Male</option>
                                        <option value="female" <?php echo (($previousValues["mentor_sex"] ?? "")=="female")?"selected":""; ?>>Female</option>
                                        <option value="other" <?php echo (($previousValues["mentor_sex"] ?? "")=="other")?"selected":""; ?>>Other</option>
                                    </select>
                                    <div class="err"><?php echo $mentorSexErr; ?></div>
                                </div>

                                <div class="form-row">
                                    <div class="form-label">Age</div>
                                    <input class="input" type="text" name="mentor_age" id="mentor_age"
                                        value="<?php echo $previousValues["mentor_age"] ?? '' ?>">
                                    <div class="err"><?php echo $mentorAgeErr; ?></div>
                                </div>

                                <div class="form-row full">
                                    <div class="form-label">Qualification / Experience</div>
                                    <input class="input" type="text" name="mentor_qualification" id="mentor_qualification"
                                        value="<?php echo $previousValues["mentor_qualification"] ?? '' ?>">
                                    <div class="err"><?php echo $mentorQualificationErr; ?></div>
                                </div>

                                <div class="form-row full">
                                    <div class="form-label">Experience Details</div>
                                    <textarea class="textarea" name="mentor_experience" id="mentor_experience"><?php echo $previousValues["mentor_experience"] ?? '' ?></textarea>
                                    <div class="err"><?php echo $mentorExperienceErr; ?></div>
                                </div>

                                <div class="form-row full">
                                    <div class="form-label">Language Proficiency</div>
                                    <input class="input" type="text" name="mentor_language" id="mentor_language"
                                        value="<?php echo $previousValues["mentor_language"] ?? '' ?>">
                                    <div class="err"><?php echo $mentorLanguageErr; ?></div>
                                </div>

                                <div class="form-row">
                                    <div class="form-label">Available for (Price Range)</div>
                                    <select class="select" name="mentor_price_range" id="mentor_price_range">
                                        <option value="">Select</option>
                                        <option value="500-999" <?php echo (($previousValues["mentor_price_range"] ?? "")=="500-999")?"selected":""; ?>>500-999</option>
                                        <option value="1000-1999" <?php echo (($previousValues["mentor_price_range"] ?? "")=="1000-1999")?"selected":""; ?>>1000-1999</option>
                                        <option value="2000-2999" <?php echo (($previousValues["mentor_price_range"] ?? "")=="2000-2999")?"selected":""; ?>>2000-2999</option>
                                        <option value="3000-4999" <?php echo (($previousValues["mentor_price_range"] ?? "")=="3000-4999")?"selected":""; ?>>3000-4999</option>
                                        <option value="5000+" <?php echo (($previousValues["mentor_price_range"] ?? "")=="5000+")?"selected":""; ?>>5000+</option>
                                    </select>
                                    <div class="err"><?php echo $mentorPriceErr; ?></div>
                                </div>

                                <div class="form-row">
                                    <div class="form-label">Preferred mentoring</div>
                                    <select class="select" name="mentor_preferred" id="mentor_preferred">
                                        <option value="">Select</option>
                                        <option value="audio" <?php echo (($previousValues["mentor_preferred"] ?? "")=="audio")?"selected":""; ?>>Audio</option>
                                        <option value="video" <?php echo (($previousValues["mentor_preferred"] ?? "")=="video")?"selected":""; ?>>Video</option>
                                        <option value="both" <?php echo (($previousValues["mentor_preferred"] ?? "")=="both")?"selected":""; ?>>Both</option>
                                    </select>
                                    <div class="err"><?php echo $mentorPreferredErr; ?></div>
                                </div>

                                <div class="form-row full">
                                    <div class="form-label">Payment Method</div>
                                    <select class="select" name="mentor_payment_method" id="mentor_payment_method"
                                        onchange="togglePaymentFields('mentor')">
                                        <option value="">Select</option>
                                        <option value="paypal" <?php echo (($previousValues["mentor_payment_method"] ?? "")=="paypal")?"selected":""; ?>>Paypal</option>
                                        <option value="bkash" <?php echo (($previousValues["mentor_payment_method"] ?? "")=="bkash")?"selected":""; ?>>Bkash</option>
                                        <option value="nagad" <?php echo (($previousValues["mentor_payment_method"] ?? "")=="nagad")?"selected":""; ?>>Nagad</option>
                                        <option value="credit_card" <?php echo (($previousValues["mentor_payment_method"] ?? "")=="credit_card")?"selected":""; ?>>Credit Card</option>
                                        <option value="debit_card" <?php echo (($previousValues["mentor_payment_method"] ?? "")=="debit_card")?"selected":""; ?>>Debit Card</option>
                                    </select>
                                    <div class="err"><?php echo $mentorPaymentMethodErr; ?></div>
                                </div>

                                <div class="form-row full" id="mentorPayPhoneRow">
                                    <div class="form-label">Payment Number</div>
                                    <input class="input" type="text" name="mentor_payment_phone" id="mentor_payment_phone"
                                        value="<?php echo $previousValues["mentor_payment_phone"] ?? '' ?>">
                                    <div class="err"><?php echo $mentorPaymentDetailErr; ?></div>
                                </div>

                                <div class="form-row full" id="mentorPayEmailRow">
                                    <div class="form-label">Paypal Email</div>
                                    <input class="input" type="text" name="mentor_payment_paypal_email" id="mentor_payment_paypal_email"
                                        value="<?php echo $previousValues["mentor_payment_paypal_email"] ?? '' ?>">
                                    <div class="err"><?php echo $mentorPaymentDetailErr; ?></div>
                                </div>

                                <div class="form-row full" id="mentorPayCardRow">
                                    <div class="form-label">Card Last 4 Digits</div>
                                    <input class="input" type="text" name="mentor_payment_card_last4" id="mentor_payment_card_last4"
                                        value="<?php echo $previousValues["mentor_payment_card_last4"] ?? '' ?>">
                                    <div class="err"><?php echo $mentorPaymentDetailErr; ?></div>
                                </div>

                                <div class="form-row full">
                                    <div class="form-label">Bio</div>
                                    <textarea class="textarea" name="mentor_bio" id="mentor_bio" maxlength="150"><?php echo $previousValues["mentor_bio"] ?? '' ?></textarea>
                                    <div class="hint">Max 150 characters</div>
                                </div>
                            </div>

                            <div id="learnerSection">

                                <div class="form-row full">
                                    <div class="form-label">Interested Categories</div>
                                    <div id="learnerCategoriesBox" class="cat-box">
                                        <?php foreach ($learnerCategories as $cat): ?>
                                            <?php
                                            $cid = (int)$cat["category_id"];
                                            $checked = in_array($cid, $selectedLearnerCategoryIds) ? "checked" : "";
                                            ?>
                                            <label class="cat-pill">
                                                <input type="checkbox" name="learner_category_ids[]" value="<?php echo $cid; ?>" <?php echo $checked; ?>>
                                                <span><?php echo htmlspecialchars($cat["category_name"]); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="err"><?php echo $learnerCategoriesErr; ?></div>
                                </div>

                                <div class="form-row">
                                    <div class="form-label">Sex</div>
                                    <select class="select" name="learner_sex" id="learner_sex">
                                        <option value="">Select</option>
                                        <option value="male" <?php echo (($previousValues["learner_sex"] ?? "")=="male")?"selected":""; ?>>Male</option>
                                        <option value="female" <?php echo (($previousValues["learner_sex"] ?? "")=="female")?"selected":""; ?>>Female</option>
                                        <option value="other" <?php echo (($previousValues["learner_sex"] ?? "")=="other")?"selected":""; ?>>Other</option>
                                    </select>
                                    <div class="err"><?php echo $learnerSexErr; ?></div>
                                </div>

                                <div class="form-row">
                                    <div class="form-label">Age</div>
                                    <input class="input" type="text" name="learner_age" id="learner_age"
                                        value="<?php echo $previousValues["learner_age"] ?? '' ?>">
                                    <div class="err"><?php echo $learnerAgeErr; ?></div>
                                </div>

                                <div class="form-row full">
                                    <div class="form-label">Educational Qualification</div>
                                    <input class="input" type="text" name="learner_edu" id="learner_edu"
                                        value="<?php echo $previousValues["learner_edu"] ?? '' ?>">
                                    <div class="err"><?php echo $learnerEduErr; ?></div>
                                </div>

                                <div class="form-row">
                                    <div class="form-label">Preferred way to learn</div>
                                    <select class="select" name="learner_preferred" id="learner_preferred">
                                        <option value="">Select</option>
                                        <option value="audio" <?php echo (($previousValues["learner_preferred"] ?? "")=="audio")?"selected":""; ?>>Audio</option>
                                        <option value="video" <?php echo (($previousValues["learner_preferred"] ?? "")=="video")?"selected":""; ?>>Video</option>
                                        <option value="both" <?php echo (($previousValues["learner_preferred"] ?? "")=="both")?"selected":""; ?>>Both</option>
                                    </select>
                                    <div class="err"><?php echo $learnerPreferredErr; ?></div>
                                </div>

                                <div class="form-row">
                                    <div class="form-label">Preferred payment method</div>
                                    <select class="select" name="learner_payment_method" id="learner_payment_method"
                                        onchange="togglePaymentFields('learner')">
                                        <option value="">Select</option>
                                        <option value="paypal" <?php echo (($previousValues["learner_payment_method"] ?? "")=="paypal")?"selected":""; ?>>Paypal</option>
                                        <option value="bkash" <?php echo (($previousValues["learner_payment_method"] ?? "")=="bkash")?"selected":""; ?>>Bkash</option>
                                        <option value="nagad" <?php echo (($previousValues["learner_payment_method"] ?? "")=="nagad")?"selected":""; ?>>Nagad</option>
                                        <option value="credit_card" <?php echo (($previousValues["learner_payment_method"] ?? "")=="credit_card")?"selected":""; ?>>Credit Card</option>
                                        <option value="debit_card" <?php echo (($previousValues["learner_payment_method"] ?? "")=="debit_card")?"selected":""; ?>>Debit Card</option>
                                    </select>
                                    <div class="err"><?php echo $learnerPaymentMethodErr; ?></div>
                                </div>

                                <div class="form-row full" id="learnerPayPhoneRow">
                                    <div class="form-label">Payment Number</div>
                                    <input class="input" type="text" name="learner_payment_phone" id="learner_payment_phone"
                                        value="<?php echo $previousValues["learner_payment_phone"] ?? '' ?>">
                                    <div class="err"><?php echo $learnerPaymentDetailErr; ?></div>
                                </div>

                                <div class="form-row full" id="learnerPayEmailRow">
                                    <div class="form-label">Paypal Email</div>
                                    <input class="input" type="text" name="learner_payment_paypal_email" id="learner_payment_paypal_email"
                                        value="<?php echo $previousValues["learner_payment_paypal_email"] ?? '' ?>">
                                    <div class="err"><?php echo $learnerPaymentDetailErr; ?></div>
                                </div>

                                <div class="form-row full" id="learnerPayCardRow">
                                    <div class="form-label">Card Last 4 Digits</div>
                                    <input class="input" type="text" name="learner_payment_card_last4" id="learner_payment_card_last4"
                                        value="<?php echo $previousValues["learner_payment_card_last4"] ?? '' ?>">
                                    <div class="err"><?php echo $learnerPaymentDetailErr; ?></div>
                                </div>

                                <div class="form-row full">
                                    <div class="form-label">Bio</div>
                                    <textarea class="textarea" name="learner_bio" id="learner_bio" maxlength="150"><?php echo $previousValues["learner_bio"] ?? '' ?></textarea>
                                    <div class="hint">Max 150 characters</div>
                                    <div class="err"><?php echo $learnerBioErr; ?></div>
                                </div>
                            </div>

                        </div>

                        <div class="action-row">
                            <button class="btn primary" type="submit">Create account</button>
                            <a class="btn" href="login.php" style="text-decoration:none; display:inline-flex; align-items:center;">Login</a>
                        </div>

                    </div>
                </form>

            </div>
        </div>


    </div>

    <footer class="footer-bar">
    <span>Copyright © 2026 SkillSwap. All rights reserved.</span>
    </footer>

    <script>
        toggleRoleSections("<?php echo ($roleVal == "mentor") ? "mentor" : "learner"; ?>");
        togglePaymentFields("<?php echo ($roleVal == "mentor") ? "mentor" : "learner"; ?>");
    </script>

</body>
</html>
