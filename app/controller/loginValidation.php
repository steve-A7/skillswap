<?php
include "../Model/DatabaseConnection.php";
include "../Model/User.php";

session_start();

$email = trim($_REQUEST["email"] ?? "");
$password = $_REQUEST["password"] ?? "";

$errors = [];
$values = [];

if(!$email){
    $errors["email"] = "Email field is required";
}

if(!$password){
    $errors["password"] = "Password field is required";
}

if(count($errors) > 0){

    if($errors["email"] ?? ""){
        $_SESSION["emailErr"] = $errors["email"];
    }else{
        if(isset($_SESSION["emailErr"])) unset($_SESSION["emailErr"]);
    }

    if($errors["password"] ?? ""){
        $_SESSION["passwordErr"] = $errors["password"];
    }else{
        if(isset($_SESSION["passwordErr"])) unset($_SESSION["passwordErr"]);
    }

    $values["email"] = $email;
    $_SESSION["previousValues"] = $values;

    Header("Location: ..\\View\\login.php");
    exit();

}else{

    $db = new DatabaseConnection();
    $userModel = new User($db);

    $user = $userModel->findByEmail($email);

    if($user && (int)$user["is_active"] === 1 && (password_verify($password, $user["password"]) || $password === $user["password"])){

        $_SESSION["isLoggedIn"] = true;
        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["UserName"] = $user["username"];
        $_SESSION["Role"] = $user["role"];

        if($user["role"] == "learner"){
        Header("Location: ..\\View\\learnerDashboard.php");
        exit();
        }else if($user["role"] == "mentor"){
        Header("Location: ..\\View\\mentorDashboard.php");
        exit();
        }else{
        
        exit();
        }

    }else{
        $_SESSION["LoginErr"] = "Email or password is incorrect";
        unset($_SESSION["emailErr"]);
        unset($_SESSION["passwordErr"]);
        Header("Location: ..\\View\\login.php");
        exit();
    }
}
?>
