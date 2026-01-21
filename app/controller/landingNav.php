<?php
session_start();

$nav = $_REQUEST["nav"] ?? "";

if ($nav == "AboutUs") {
    Header("Location: ..\View\aboutus.php");
}
else if ($nav == "Home") {
    Header("Location: ..\View\landing.php");
}
else if ($nav == "SignUp") {
    Header("Location: ..\View\signup.php");
}
else if ($nav == "Login") {
    Header("Location: ..\View\login.php");
}
else {
    $_SESSION["navErr"] = "Invalid navigation request";
    Header("Location: ..\View\landing.php");
}
?>
