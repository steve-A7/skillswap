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
    <link rel="stylesheet" href="../../public/css/editSkillOfferingCSS.css" />
    <script src="../controller/JS/editSkillOffering.js" defer></script>
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

            <form method="post" action="../Controller/logout.php" style="display:inline;">
                <button type="submit" class="btn">Logout</button>
            </form>
        </div>

        <hr class="hr-line" />

        <div class="profile-card-wrap">
            <div class="profile-card">
                <div class="card-header">
                    <div class="card-left">
                        <a class="icon-btn" href="manageSkills.php">
                            <img class="icon-img" src="../../public/assets/preloads/back.png" alt="Back">
                            <span>Back</span>
                        </a>
                    </div>

                    <div class="card-center">
                        <div class="page-title">Edit Skill Offering</div>
                        <div class="page-subtitle"><?php echo htmlspecialchars($userName); ?>, select an offering to edit.</div>
                    </div>

                    <div class="card-right"></div>
                </div>

                <div class="offerings-wrap">
                    <div id="offeringsGrid" class="offerings-grid"></div>

                    <div id="emptyState" class="empty-state" style="display:none;">
                        No available offerings found.
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
