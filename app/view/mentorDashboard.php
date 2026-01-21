<?php
include "../Model/DatabaseConnection.php";
include "../Model/MentorProfile.php";

session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION["UserName"] ?? "";
$profilePicUrl = "";

$db = new DatabaseConnection();
$mentorModel = new MentorProfile($db);

$userId = (int)($_SESSION["user_id"] ?? $_SESSION["UserId"] ?? 0);

if ($userId > 0) {
    $mentor = $mentorModel->getByUserId($userId);
    $profilePic = $mentor["profile_picture_path"] ?? "";

    if ($profilePic !== "") {
        $profilePic = str_replace("\\", "/", $profilePic);
        $profilePicUrl = "../../" . ltrim($profilePic, "/");
    }
}
?>

<html>
<head>
	<meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SwillSwap</title>
	<link rel="stylesheet" href="../../public/css/mentorDashboardCSS.css" />
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

		<hr class="hr-line" />

		<div class="profile-card-wrap">
			<div class="profile-card">
				<div class="card-header">
					<div class="card-left"></div>

					<div class="card-center">
						<div class="page-title">Mentor Dashboard</div>
						<div class="page-subtitle">Welcome, <?php echo htmlspecialchars($userName); ?>. Choose what you want to do next.</div>
					</div>

					<div class="card-right"></div>
				</div>

				<div class="action-grid">
					<form class="action-form" method="post" action="mentorProfile.php">
						<button type="submit" class="action-card">
							<img class="action-icon" src="../../public/assets/preloads/Profile.png" alt="Profile" />
							<div class="action-text">
								<div class="action-name">Profile Management</div>
								<div class="action-desc">View and edit your mentor profile details</div>
							</div>
						</button>
					</form>
					<form class="action-form" method="post" action="manageSkills.php">
						<button type="submit" class="action-card">
							<img class="action-icon" src="../../public/assets/preloads/Skill_Manage.png" alt="Manage Skills"/>
							<div class="action-text">
								<div class="action-name">Manage Skills</div>
								<div class="action-desc">Add offerings, edit offerings, and track sessions</div>
							</div>
						</button>
					</form>



					<form class="action-form" method="post" action="mentorStatistics.php">
						<button type="submit" class="action-card">
							<img class="action-icon" src="../../public/assets/preloads/Statistics.png" alt="Statistics"/>
							<div class="action-text">
								<div class="action-name">Statistics</div>
								<div class="action-desc">See ratings, feedback, and performance overview</div>
							</div>
						</button>
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
