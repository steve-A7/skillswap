<?php
include "../Model/DatabaseConnection.php";
include "../Model/LearnerProfile.php";
session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

if (($_SESSION["Role"] ?? "") !== "learner") {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION["UserName"] ?? "Learner";
$profilePicUrl = "";

$db = new DatabaseConnection();
$learnerModel = new LearnerProfile($db);

$userId = (int)($_SESSION["user_id"] ?? $_SESSION["UserId"] ?? 0);

if ($userId > 0) {
    $learner = $learnerModel->getByUserId($userId);
    $profilePic = $learner["profile_picture_path"] ?? "";

    if ($profilePic !== "") {
        $profilePic = str_replace("\\", "/", $profilePic);
        $profilePicUrl = "../../" . ltrim($profilePic, "/");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SwillSwap</title>
    <link rel="stylesheet" href="../../public/css/learnerDashboardCSS.css" />
    <script defer src="../controller/JS/learnerSearch.js"></script>
</head>

<body>
    <div class="bg-layer"></div>
    <div class="tint-layer"></div>

    <div class="page" id="pageRoot">
        <div class="topbar">
            <div class="logo-wrap">
                <img class="logo-img" src="../../public/assets/preloads/logo.svg" alt="Logo">
                <span class="logo-text">SkillSwap</span>
           </div>

            <div class="search-main-wrap">
                <div class="search-pill" id="searchPill">
                    <input id="searchTrigger" class="search-input" type="text" placeholder="Search..." readonly />
                    <div class="search-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M10.5 19a8.5 8.5 0 1 1 6.02-2.48L21 21"
                                  stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;">
				<form method="post" action="mentorProfile.php" style="display:inline;">
					<button type="submit" class="btn" style="padding:6px 10px;display:inline-flex;align-items:center;gap:10px;">
						<?php if ($profilePicUrl): ?>
							<img src="<?php echo htmlspecialchars($profilePicUrl); ?>" alt="Profile" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid rgba(0,0,0,0.18);" />
						<?php else: ?>
							<span style="width:34px;height:34px;border-radius:50%;border:1px solid rgba(0,0,0,0.18);display:inline-flex;align-items:center;justify-content:center;">👤</span>
						<?php endif; ?>
					</button>
				</form>
			
				<form method="post" action="../controller/logout.php" style="display:inline;">
					<button type="submit" class="btn">Logout</button>
				</form>
			</div>
        </div>

        <hr class="hr-line"/>

        <div class="profile-card-wrap">
            <div class="profile-card">
                <div class="card-header">
                    <div class="card-left"></div>

                    <div class="card-center">
                        <div class="page-title">Learner Dashboard</div>
                        <div class="page-subtitle">
                            Welcome, <?php echo htmlspecialchars($userName); ?>. Choose what you want to do next.
                        </div>
                    </div>

                    <div class="card-right"></div>
                </div>

                <div class="action-grid">
                    <form class="action-form" method="post" action="learnerProfile.php">
                        <button type="submit" class="action-card">
                            <img class="action-icon" src="../../public/assets/preloads/Profile.png" alt="Profile"
                                 onerror="this.style.display='none';" />
                            <div class="action-text">
                                <div class="action-name">Profile Management</div>
                                <div class="action-desc">View and edit your learner profile details</div>
                            </div>
                        </button>
                    </form>

                    <form class="action-form" method="post" action="learnerBrowse.php">
                        <button type="submit" class="action-card">
                            <img class="action-icon" src="../../public/assets/preloads/Browse_Skill.png" alt="Browse"
                                 onerror="this.style.display='none';" />
                            <div class="action-text">
                                <div class="action-name">Browse Skills</div>
                                <div class="action-desc">Search and explore available offerings</div>
                            </div>
                        </button>
                    </form>

                    <form class="action-form" method="post" action="learnerMySkills.php">
                        <button type="submit" class="action-card">
                            <img class="action-icon" src="../../public/assets/preloads/Learner_View_Skill.png" alt="My Skills"
                                 onerror="this.style.display='none';" />
                            <div class="action-text">
                                <div class="action-name">View My Skills</div>
                                <div class="action-desc">See purchased sessions, status, and feedback</div>
                            </div>
                        </button>
                    </form>
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

    <footer class="footer-bar">
    <span>Copyright © 2026 SkillSwap. All rights reserved.</span>
    </footer>

</body>
</html>
