<?php
include "../Model/DatabaseConnection.php";
include "../Model/Skill.php";
include "../Model/LearnerInterest.php";

session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION["UserName"] ?? "";

$db = new DatabaseConnection();
$conn = $db->getConnection();

$userId = (int)($_SESSION["user_id"] ?? $_SESSION["UserId"] ?? 0);

$learnerId = 0;
$stmt = $conn->prepare("SELECT learner_id FROM learner_profiles WHERE user_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($lid);
    if ($stmt->fetch()) {
        $learnerId = (int)$lid;
    }
    $stmt->close();
}

$skillModel = new Skill($db);
$interestModel = new LearnerInterest($db);

$myCategoryIds = [];
$myCategories = [];
$allCategories = [];

if ($learnerId > 0) {
    $myCategoryIds = $interestModel->listCategoryIdsByLearner($learnerId);
    if (count($myCategoryIds) > 0) {
        $myCategories = $skillModel->getCategoriesByIds($myCategoryIds);
    }
}

$allCategories = $skillModel->listCategoriesFromMentorOfferings(300);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SwillSwap</title>
    <link rel="stylesheet" href="../../public/css/learnerBrowseCSS.css" />
    <script src="../controller/JS/learnerBrowse.js" defer></script>
    <script src="../controller/JS/learnerSearch.js" defer></script>
</head>

<body>
    <div class="bg-layer"></div>
    <div class="tint-layer"></div>

    <div class="page">

        <div class="topbar">
            <div class="topbar-left">
                <div class="logo-wrap">
                    <img class="logo-img" src="../../public/assets/preloads/logo.svg" alt="Logo">
                    <span class="logo-text">SkillSwap</span>
                </div>
            </div>

            <div class="search-pill" id="searchPill">
                <input id="searchTrigger" class="search-input" type="text" placeholder="Search..." readonly />
                <div class="search-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="M10.5 19a8.5 8.5 0 1 1 6.02-2.48L21 21"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
            <div class="topbar-right">
                <form method="post" action="../Controller/logout.php" style="display:inline;">
                    <button type="submit" class="btn">Logout</button>
                </form>
            </div>
        </div>

        <hr class="hr-line" />

        <div class="content">
            <div class="profile-card-wrap">
                <div class="profile-card">

                    <div class="card-header">
                        <div class="card-left">
                            <a class="icon-btn" href="learnerDashboard.php">
                                <img class="icon-img" src="../../public/assets/preloads/back.png" alt="Back">
                                <span>Back</span>
                            </a>
                        </div>

                        <div class="card-center">
                            <div class="page-title">Browse Skill Offerings</div>
                            <div class="page-subtitle"><?php echo htmlspecialchars($userName); ?>, explore available offerings.</div>
                        </div>

                        <div class="card-right">
                            <div class="filters-wrap">
                                <button type="button" id="filterYourBtn" class="filter-btn active">Your Categories</button>
                                <button type="button" id="filterAllBtn" class="filter-btn">All Categories</button>
                            </div>
                        </div>
                    </div>

                    <div id="browseOfferingsGrid" class="offerings-grid"></div>
                        <div id="browseEmptyState" class="empty-state" style="display:none;">
                            No available offerings found.
                        </div>


                </div>
            </div>
        </div>

        <div class="search-overlay" id="searchOverlay" aria-hidden="true">
            <div class="search-modal">
                <div class="search-modal-top">
                    <div class="search-pill search-pill-live">
                        <input id="searchInput" class="search-input" type="text" placeholder="Search offerings..." autocomplete="off" />
                        <button class="search-x" id="clearBtn" type="button" title="Clear">✕</button>
                    </div>

                    <button class="btn" id="closeSearchBtn" type="button">Close</button>
            </div>

            <div class="browse-wrap">
                    <div id="toast" class="toast" style="display:none;"></div>

                <div id="emptyState" class="search-hint" style="display:block;">
                Start typing to search offerings...
                 </div>

                <div id="offeringsGrid" class="offerings-grid"></div>
            </div>
        </div>
</div>


    </div>

    <footer class="footer-bar">
    <span>Copyright © 2026 SkillSwap. All rights reserved.</span>
    </footer>

    <script>
        window.__LEARNER_BROWSE__ = {
            hasMyCategories: <?php echo (count($myCategoryIds) > 0) ? "true" : "false"; ?>
        };
    </script>
</body>
</html>
