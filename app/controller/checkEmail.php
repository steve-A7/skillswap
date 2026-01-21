<?php
include "../Model/DatabaseConnection.php";
include "../Model/User.php";

header("Content-Type: application/json");

$email = trim($_GET["email"] ?? "");
if(!$email){
    echo json_encode(["exists" => false, "valid" => false, "message" => ""]);
    exit();
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    echo json_encode(["exists" => false, "valid" => false, "message" => "Invalid email format"]);
    exit();
}

$db = new DatabaseConnection();
$userModel = new User($db);

if($userModel->existsEmail($email)){
    echo json_encode(["exists" => true, "valid" => true, "message" => "Email already exists"]);
    exit();
}

echo json_encode(["exists" => false, "valid" => true, "message" => "Email is available"]);
exit();
