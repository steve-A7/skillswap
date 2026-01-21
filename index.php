<?php
session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;

if ($isLoggedIn) {
    Header("Location: app/View/dashboard.php");
    exit();
}

Header("Location: app/View/landing.php");
exit();
?>
