<?php
session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;

if ($isLoggedIn) {
    if (($_SESSION["Role"] ?? "") == "learner") {
        Header("Location: ..\\View\\learnerDashboard.php");
        exit();
    } else if (($_SESSION["Role"] ?? "") == "mentor") {
        Header("Location: ..\\View\\mentorDashboard.php");
        exit();
    } else {
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SkillSwap</title>

    <link rel="stylesheet" href="../../public/css/landingCSS.css">
</head>

<body>

    <div class="bg-layer"></div>
    <div class="tint-layer"></div>

    <div class="page">

        <div class="topbar">
            <div class="logo-wrap">
                <img class="logo-img" src="../../public/assets/preloads/logo.svg" alt="Logo" onerror="this.style.display='none';" />
				<div class="logo-text">SkillSwap</div>
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
            <div class="hero-card">
                
                <img class="hero-img" src="../../public/assets/preloads/logo.svg" alt="Logo" onerror="this.style.display='none';" />
                <div class="hero-title">SkillSwap</div>
                <div class="hero-subtitle">Peer learning &amp; mentorship platform</div>

                <div class="hero-actions">

                    <form class="action-form" method="post" action="..\controller\landingNav.php">
                        <input type="hidden" name="nav" value="SignUp" />
                        <button class="btn primary" type="submit">Get started</button>
                    </form>

                    <form class="action-form" method="post" action="..\controller\landingNav.php">
                        <input type="hidden" name="nav" value="Login" />
                        <button class="btn" type="submit">Login</button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <footer class="footer-bar">
    <span>Copyright © 2026 SkillSwap. All rights reserved.</span>
    </footer>


</body>

</html>
