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

$previousValues = $_SESSION["loginPreviousValues"] ?? [];

$emailErr = $_SESSION["emailErr"] ?? "";
$passErr  = $_SESSION["passErr"] ?? "";
$loginErr = $_SESSION["loginErr"] ?? "";

unset($_SESSION["loginPreviousValues"]);
unset($_SESSION["emailErr"]);
unset($_SESSION["passErr"]);
unset($_SESSION["loginErr"]);

function esc($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SkillSwap - Registration</title>
    <link rel="stylesheet" href="../../public/css/loginCSS.css">
</head>

<body>


    <div class="bg-layer"></div>
    <div class="tint-layer"></div>

    <div class="page">

        <div class="topbar">
            <div class="logo-wrap">
                <img class="logo-img" src="../../public/assets/preloads/logo.svg" alt="SkillSwap Logo">
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
            <div class="hero-card login-card">

                <div class="login-title">Login</div>
                <div class="login-subtitle">Welcome back! Please enter your details.</div>

                <form method="post" action="..\controller\loginValidation.php">
                    <div class="form-grid">

                        <div class="form-row">
                            <label class="form-label" for="email">Email</label>
                            <input class="input" type="text" name="email" id="email"
                                value="<?php echo esc($previousValues["email"] ?? ""); ?>" />
                            <div class="err"><?php echo $emailErr; ?></div>
                        </div>

                        <div class="form-row">
                            <label class="form-label" for="password">Password</label>
                            <input class="input" type="password" name="password" id="password" />
                            <div class="err"><?php echo $passErr; ?></div>
                        </div>

                        <div class="err" style="text-align:center;">
                            <?php echo $loginErr; ?>
                        </div>

                        <div class="action-row">
                            <button class="btn primary" type="submit" name="Login" value="Login">Login</button>
                        </div>

                    </div>
                </form>

                <div class="bottom-link">
                    Don’t have an account?
                    <a href="signup.php">Sign Up</a>
                </div>

            </div>
        </div>


    </div>

    <footer class="footer-bar">
    <span>Copyright © 2026 SkillSwap. All rights reserved.</span>
    </footer>

</body>

</html>
