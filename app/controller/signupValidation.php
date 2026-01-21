<?php
include "../Model/DatabaseConnection.php";
include "../Model/User.php";
include "../Model/MentorProfile.php";
include "../Model/LearnerProfile.php";
include "../Model/Skill.php";
include "../Model/LearnerInterest.php";
include "../Model/MentorCategory.php";

session_start();

$role = trim($_REQUEST["role"] ?? "");
$username = trim($_REQUEST["username"] ?? "");
$email = trim($_REQUEST["email"] ?? "");
$password = $_REQUEST["password"] ?? "";
$confirmPassword = $_REQUEST["confirm_password"] ?? "";

$errors = [];
$values = [];

if($email && !filter_var($email, FILTER_VALIDATE_EMAIL)){
    $errors["emailErr"] = "Invalid email format";
}

if(!$role) $errors["roleErr"] = "Role is required";
if(!$username) $errors["usernameErr"] = "Username is required";
if(!$email) $errors["emailErr"] = "Email is required";
if(!$password) $errors["passwordErr"] = "Password is required";
if(!$confirmPassword) $errors["confirmPasswordErr"] = "Retype password is required";
if($password && $confirmPassword && $password != $confirmPassword) $errors["confirmPasswordErr"] = "Passwords do not match";

$values["role"] = $role;
$values["username"] = $username;
$values["email"] = $email;

foreach($_REQUEST as $k => $v){
    if(!isset($values[$k]) && is_string($v)){
        $values[$k] = $v;
    }
}

if(!isset($_FILES["profile_picture"]) || $_FILES["profile_picture"]["error"] == 4){
    $errors["imageErr"] = "Profile picture is required";
}else{
    $fileErr = $_FILES["profile_picture"]["error"];
    $fileSize = $_FILES["profile_picture"]["size"];
    $fileName = $_FILES["profile_picture"]["name"] ?? "";

    if($fileErr != 0){
        $errors["imageErr"] = "Invalid image upload";
    }else{
        if($fileSize > (10 * 1024 * 1024)){
            $errors["imageErr"] = "Image size must be less than 10MB";
        }else{
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if(!in_array($ext, ["jpg","jpeg","png"])){
                $errors["imageErr"] = "Only JPG or PNG images are allowed";
            }
        }
    }
}

if($role == "mentor"){

    $mentor_sex = trim($_REQUEST["mentor_sex"] ?? "");
    $mentor_age = trim($_REQUEST["mentor_age"] ?? "");
    $mentor_qualification = trim($_REQUEST["mentor_qualification"] ?? "");
    $mentor_experience = trim($_REQUEST["mentor_experience"] ?? "");
    $mentor_language = trim($_REQUEST["mentor_language"] ?? "");
    $mentor_price = trim($_REQUEST["mentor_price_range"] ?? "");
    $mentor_preferred = trim($_REQUEST["mentor_preferred"] ?? "");
    $mentor_pay_method = trim($_REQUEST["mentor_payment_method"] ?? "");

    $mentor_category_ids = $_REQUEST["mentor_category_ids"] ?? [];
    if(!is_array($mentor_category_ids)) $mentor_category_ids = [];
    $mentor_category_ids = array_values(array_unique(array_map("intval", $mentor_category_ids)));

    $mentor_new_categories = trim($_REQUEST["mentor_new_categories"] ?? "");
    $values["mentor_new_categories"] = $mentor_new_categories;

    if(!$mentor_sex) $errors["mentorSexErr"] = "Sex is required";
    if(!$mentor_age) $errors["mentorAgeErr"] = "Age is required";
    if(!$mentor_qualification) $errors["mentorQualificationErr"] = "Qualification is required";
    if(!$mentor_experience) $errors["mentorExperienceErr"] = "Experience is required";
    if(!$mentor_language) $errors["mentorLanguageErr"] = "Language proficiency is required";
    if(!$mentor_price) $errors["mentorPriceErr"] = "Price range is required";
    if(!$mentor_preferred) $errors["mentorPreferredErr"] = "Preferred mentoring is required";
    if(!$mentor_pay_method) $errors["mentorPaymentMethodErr"] = "Payment method is required";

    $values["mentor_sex"] = $mentor_sex;
    $values["mentor_age"] = $mentor_age;
    $values["mentor_qualification"] = $mentor_qualification;
    $values["mentor_experience"] = $mentor_experience;
    $values["mentor_language"] = $mentor_language;
    $values["mentor_price_range"] = $mentor_price;
    $values["mentor_preferred"] = $mentor_preferred;
    $values["mentor_payment_method"] = $mentor_pay_method;
    $values["mentor_bio"] = $_REQUEST["mentor_bio"] ?? "";
    $values["mentor_category_ids"] = $mentor_category_ids;

    if($mentor_pay_method == "bkash" || $mentor_pay_method == "nagad"){
        $phone = trim($_REQUEST["mentor_payment_phone"] ?? "");
        if(!$phone) $errors["mentorPaymentDetailErr"] = "Phone number is required";
        $values["mentor_payment_phone"] = $phone;
    }else if($mentor_pay_method == "paypal"){
        $pemail = trim($_REQUEST["mentor_payment_paypal_email"] ?? "");
        if(!$pemail) $errors["mentorPaymentDetailErr"] = "PayPal email is required";
        $values["mentor_payment_paypal_email"] = $pemail;
    }else if($mentor_pay_method == "credit_card" || $mentor_pay_method == "debit_card"){
        $last4 = trim($_REQUEST["mentor_payment_card_last4"] ?? "");
        if(!$last4){
            $errors["mentorPaymentDetailErr"] = "Last 4 digits is required";
        }else{
            if(!ctype_digit($last4) || strlen($last4) != 4){
                $errors["mentorPaymentDetailErr"] = "Last 4 digits must be exactly 4 numbers";
            }
        }
        $values["mentor_payment_card_last4"] = $last4;
    }

    if(count($mentor_category_ids) < 1 && $mentor_new_categories == ""){
        $errors["mentorCategoriesErr"] = "Select at least 1 category or add one";
    }

}else if($role == "learner"){

    $learner_sex = trim($_REQUEST["learner_sex"] ?? "");
    $learner_age = trim($_REQUEST["learner_age"] ?? "");
    $learner_edu = trim($_REQUEST["learner_edu"] ?? "");
    $learner_preferred = trim($_REQUEST["learner_preferred"] ?? "");
    $learner_pay_method = trim($_REQUEST["learner_payment_method"] ?? "");

    $learner_category_ids = $_REQUEST["learner_category_ids"] ?? [];
    if(!is_array($learner_category_ids)) $learner_category_ids = [];
    $learner_category_ids = array_values(array_unique(array_map("intval", $learner_category_ids)));

    if(count($learner_category_ids) < 1){
        $errors["learnerCategoriesErr"] = "Select at least 1 category";
    }

    if(!$learner_sex) $errors["learnerSexErr"] = "Sex is required";
    if(!$learner_age) $errors["learnerAgeErr"] = "Age is required";
    if(!$learner_edu) $errors["learnerEduErr"] = "Educational qualification is required";
    if(!$learner_preferred) $errors["learnerPreferredErr"] = "Preferred way to learn is required";
    if(!$learner_pay_method) $errors["learnerPaymentMethodErr"] = "Payment method is required";

    $values["learner_sex"] = $learner_sex;
    $values["learner_age"] = $learner_age;
    $values["learner_edu"] = $learner_edu;
    $values["learner_preferred"] = $learner_preferred;
    $values["learner_payment_method"] = $learner_pay_method;
    $values["learner_bio"] = $_REQUEST["learner_bio"] ?? "";
    $values["learner_category_ids"] = $learner_category_ids;

    if($learner_pay_method == "bkash" || $learner_pay_method == "nagad"){
        $phone = trim($_REQUEST["learner_payment_phone"] ?? "");
        if(!$phone) $errors["learnerPaymentDetailErr"] = "Phone number is required";
        $values["learner_payment_phone"] = $phone;
    }else if($learner_pay_method == "paypal"){
        $pemail = trim($_REQUEST["learner_payment_paypal_email"] ?? "");
        if(!$pemail) $errors["learnerPaymentDetailErr"] = "PayPal email is required";
        $values["learner_payment_paypal_email"] = $pemail;
    }else if($learner_pay_method == "credit_card" || $learner_pay_method == "debit_card"){
        $last4 = trim($_REQUEST["learner_payment_card_last4"] ?? "");
        if(!$last4){
            $errors["learnerPaymentDetailErr"] = "Last 4 digits is required";
        }else{
            if(!ctype_digit($last4) || strlen($last4) != 4){
                $errors["learnerPaymentDetailErr"] = "Last 4 digits must be exactly 4 numbers";
            }
        }
        $values["learner_payment_card_last4"] = $last4;
    }

}else{
    if($role) $errors["roleErr"] = "Invalid role selected";
}

if(count($errors) > 0){
    foreach($errors as $sessKey => $msg){
        $_SESSION[$sessKey] = $msg;
    }
    $_SESSION["previousValues"] = $values;
    Header("Location: ..\\View\\signup.php");
    exit();
}

$db = new DatabaseConnection();
$conn = $db->getConnection();

$userModel = new User($db);
$mentorModel = new MentorProfile($db);
$learnerModel = new LearnerProfile($db);
$skillModel = new Skill($db);
$interestModel = new LearnerInterest($db);
$mentorCategoryModel = new MentorCategory($db);

if($userModel->existsUsername($username)){
    $_SESSION["usernameErr"] = "Username already exists";
    unset($values["username"]);
    $_SESSION["previousValues"] = $values;
    Header("Location: ..\\View\\signup.php");
    exit();
}

if($userModel->existsEmail($email)){
    $_SESSION["emailErr"] = "Email already exists";
    unset($values["email"]);
    $_SESSION["previousValues"] = $values;
    Header("Location: ..\\View\\signup.php");
    exit();
}

$ext = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
if($ext == "jpeg") $ext = "jpg";

$uploadDirAbs = dirname(__DIR__, 2) . "\\public\\assets\\uploads\\";
if(!is_dir($uploadDirAbs)){
    mkdir($uploadDirAbs, 0777, true);
}
$finalName = $username . "." . $ext;
$finalAbsPath = $uploadDirAbs . $finalName;
$finalDbPath = "public/assets/uploads/" . $finalName;

$conn->begin_transaction();

$userId = 0;

try{

    $userId = $userModel->create($role, $username, $email, $password);
    if(!$userId){
        $conn->rollback();
        $_SESSION["signupErr"] = "Could not create user";
        $_SESSION["previousValues"] = $values;
        Header("Location: ..\\View\\signup.php");
        exit();
    }

    if(!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $finalAbsPath)){
        $conn->rollback();
        $_SESSION["imageErr"] = "Could not save image";
        $_SESSION["previousValues"] = $values;
        Header("Location: ..\\View\\signup.php");
        exit();
    }

    if($role == "mentor"){

        $profile = [];
        $profile["sex"] = $_REQUEST["mentor_sex"];
        $profile["age"] = $_REQUEST["mentor_age"];
        $profile["qualification_experience"] = $_REQUEST["mentor_qualification"] . " | " . $_REQUEST["mentor_experience"];
        $profile["language_proficiency"] = $_REQUEST["mentor_language"];
        $profile["available_for_price_range"] = $_REQUEST["mentor_price_range"];
        $profile["payment_method"] = $_REQUEST["mentor_payment_method"];
        $profile["preferred_mentoring"] = $_REQUEST["mentor_preferred"];
        $profile["profile_picture_path"] = $finalDbPath;

        $bio = trim($_REQUEST["mentor_bio"] ?? "");
        $profile["bio"] = $bio ? $bio : null;

        $profile["paypal_email"] = null;
        $profile["bkash_number"] = null;
        $profile["nagad_number"] = null;
        $profile["card_last4"] = null;

        if($_REQUEST["mentor_payment_method"] == "paypal"){
            $profile["paypal_email"] = trim($_REQUEST["mentor_payment_paypal_email"] ?? "");
        }else if($_REQUEST["mentor_payment_method"] == "bkash"){
            $profile["bkash_number"] = trim($_REQUEST["mentor_payment_phone"] ?? "");
        }else if($_REQUEST["mentor_payment_method"] == "nagad"){
            $profile["nagad_number"] = trim($_REQUEST["mentor_payment_phone"] ?? "");
        }else if($_REQUEST["mentor_payment_method"] == "credit_card" || $_REQUEST["mentor_payment_method"] == "debit_card"){
            $profile["card_last4"] = trim($_REQUEST["mentor_payment_card_last4"] ?? "");
        }

        $ok = $mentorModel->create($userId, $profile);
        if(!$ok){
            $conn->rollback();
            $_SESSION["signupErr"] = "Could not create mentor profile";
            $_SESSION["previousValues"] = $values;
            Header("Location: ..\\View\\signup.php");
            exit();
        }

        $mentorId = $mentorModel->getMentorIdByUserId($userId);
        if(!$mentorId){
            $conn->rollback();
            $_SESSION["signupErr"] = "Could not fetch mentor id";
            $_SESSION["previousValues"] = $values;
            Header("Location: ..\\View\\signup.php");
            exit();
        }

        $mentorCategoryIds = $_REQUEST["mentor_category_ids"] ?? [];
        if(!is_array($mentorCategoryIds)) $mentorCategoryIds = [];
        $mentorCategoryIds = array_values(array_unique(array_map("intval", $mentorCategoryIds)));

        $typed = trim($_REQUEST["mentor_new_categories"] ?? "");
        if($typed !== ""){
            $parts = array_map("trim", explode(",", $typed));
            $parts = array_values(array_filter($parts, function($v){ return $v !== ""; }));
            $parts = array_values(array_unique($parts));

            foreach($parts as $name){
                $cid = (int)$skillModel->getOrCreateCategory($name);
                if($cid > 0) $mentorCategoryIds[] = $cid;
            }
        }

        $mentorCategoryIds = array_values(array_unique(array_filter(array_map("intval", $mentorCategoryIds), function($v){ return $v > 0; })));

        foreach($mentorCategoryIds as $cid){
            $mentorCategoryModel->add($mentorId, $cid);
        }

    }else{

        $profile = [];
        $profile["sex"] = $_REQUEST["learner_sex"];
        $profile["age"] = $_REQUEST["learner_age"];
        $profile["educational_qualification"] = $_REQUEST["learner_edu"];
        $profile["preferred_way_to_learn"] = $_REQUEST["learner_preferred"];
        $profile["preferred_payment_method"] = $_REQUEST["learner_payment_method"];
        $profile["profile_picture_path"] = $finalDbPath;

        $bio = trim($_REQUEST["learner_bio"] ?? "");
        $profile["bio"] = $bio ? $bio : null;

        $profile["paypal_email"] = null;
        $profile["bkash_number"] = null;
        $profile["nagad_number"] = null;
        $profile["card_last4"] = null;

        if($_REQUEST["learner_payment_method"] == "paypal"){
            $profile["paypal_email"] = trim($_REQUEST["learner_payment_paypal_email"] ?? "");
        }else if($_REQUEST["learner_payment_method"] == "bkash"){
            $profile["bkash_number"] = trim($_REQUEST["learner_payment_phone"] ?? "");
        }else if($_REQUEST["learner_payment_method"] == "nagad"){
            $profile["nagad_number"] = trim($_REQUEST["learner_payment_phone"] ?? "");
        }else if($_REQUEST["learner_payment_method"] == "credit_card" || $_REQUEST["learner_payment_method"] == "debit_card"){
            $profile["card_last4"] = trim($_REQUEST["learner_payment_card_last4"] ?? "");
        }

        $ok = $learnerModel->create($userId, $profile);
        if(!$ok){
            $conn->rollback();
            $_SESSION["signupErr"] = "Could not create learner profile";
            $_SESSION["previousValues"] = $values;
            Header("Location: ..\\View\\signup.php");
            exit();
        }

        $learnerId = $learnerModel->getLearnerIdByUserId($userId);
        if(!$learnerId){
            $conn->rollback();
            $_SESSION["signupErr"] = "Could not fetch learner id";
            $_SESSION["previousValues"] = $values;
            Header("Location: ..\\View\\signup.php");
            exit();
        }

        $interestIds = $_REQUEST["learner_category_ids"] ?? [];
        if(!is_array($interestIds)) $interestIds = [];
        $interestIds = array_values(array_unique(array_map("intval", $interestIds)));

        foreach($interestIds as $cid){
            if($cid > 0){
                $interestModel->add($learnerId, $cid);
            }
        }
    }

    $conn->commit();

    $_SESSION["isLoggedIn"] = true;
    $_SESSION["user_id"] = $userId;
    $_SESSION["email"] = $email;
    $_SESSION["UserName"] = $username;
    $_SESSION["Role"] = $role;

    if($role == "learner"){
        Header("Location: ..\\View\\learnerDashboard.php");
        exit();
    }else if($role == "mentor"){
        Header("Location: ..\\View\\mentorDashboard.php");
        exit();
    }else{
        exit();
    }

}catch(Exception $e){
    $conn->rollback();
    $_SESSION["signupErr"] = "Signup failed";
    $_SESSION["previousValues"] = $values;
    Header("Location: ..\\View\\signup.php");
    exit();
}
?>
