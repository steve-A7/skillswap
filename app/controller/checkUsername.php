<?php
include "../Model/DatabaseConnection.php";
include "../Model/User.php";

$uname = trim($_REQUEST["username"] ?? "");

header("Content-Type: application/json");

if(!$uname){
    echo json_encode(["available" => false, "message" => ""]);
    exit();
}

$db = new DatabaseConnection();
$userModel = new User($db);

if($userModel->existsUsername($uname)){
    echo json_encode(["available" => false, "message" => "Username already exists"]);
}else{
    echo json_encode(["available" => true, "message" => "Username available"]);
}
?>
