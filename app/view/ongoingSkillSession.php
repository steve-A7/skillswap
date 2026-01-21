<?php
session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION["UserName"] ?? "";
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SwillSwap</title>
    <link rel="stylesheet" href="../../public/css/mentorSessionsCSS.css" />
    <script src="../controller/JS/mentorSessions.js" defer></script>
</head>

<body>
    <div class="bg-layer"></div>
    <div class="tint-layer"></div>

    <div class="page">
        <div class="topbar">
            <div class="logo-wrap">
                <div class="logo-text">SkillSwap</div>
            </div>

            <a class="logout-btn" href="../Controller/logout.php">Logout</a>
        </div>

        <hr class="hr-line"/>

        <div class="content">
            <div class="card">

                <div class="card-top">
                    <div class="card-left">
                        <a class="btn-back" href="manageSkills.php">
                            <img class="icon-img" src="../../public/assets/preloads/back.png" alt="Back">
                            <span>Back</span>
                        </a>
                    </div>

                    <div class="card-center">
                        <div class="page-title">Ongoing Session</div>
                        <div class="page-subtitle">
                            <?php echo htmlspecialchars($userName); ?>, manage booking requests and ongoing sessions.
                        </div>
                    </div>

                    <div class="card-right">
                    <a class="btn-home" href="mentorDashboard.php">
                        <img class="icon-img" src="../../public/assets/preloads/home.png" alt="Home">
                        <span>Home</span>
                    </a>
                    </div>
                </div>

                <div class="sections">

                    <div class="section">
                        <div class="section-title">Session Booking Requests</div>

                        <div id="requestsList" class="list"></div>

                        <div id="requestsEmpty" class="empty-state" style="display:none;">
                            No booking requests found.
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="section">
                        <div class="section-title">Ongoing Sessions</div>

                        <div id="sessionsList" class="list"></div>

                        <div id="sessionsEmpty" class="empty-state" style="display:none;">
                            No ongoing sessions found.
                        </div>
                    </div>

                </div>

                <div id="toast" class="toast" style="display:none;"></div>
            </div>
        </div>
    </div>

    <footer class="footer-bar">
    <span>Copyright © 2026 SkillSwap. All rights reserved.</span>
    </footer>

</body>
</html>
