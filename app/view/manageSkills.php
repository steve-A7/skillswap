<?php
session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    Header("Location: login.php");
    exit();
}

$userName = $_SESSION["UserName"] ?? "";
?>

<html>
<head>
	<meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SwillSwap</title>
	<link rel="stylesheet" href="../../public/css/manageSkillsCSS.css" />
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
                        <a class="icon-btn" href="mentorDashboard.php">
                            <img class="icon-img" src="../../public/assets/preloads/back.png" alt="Back">
                            <span>Back</span>
                        </a>
                    </div>

					<div class="card-center">
						<div class="page-title">Manage Skills</div>
						<div class="page-subtitle"><?php echo htmlspecialchars($userName); ?>, choose from skill management options.</div>
					</div>

					<div class="card-right"></div>
				</div>

				<div class="action-grid">
					<form class="action-form" method="post" action="addSkillOffering.php">
						<button type="submit" class="action-card">
							<img class="action-icon" src="../../public/assets/preloads/Add_Skill_Offerings.png" alt="Add" />
							<div class="action-text">
								<div class="action-name">Add Skill Offering</div>
								<div class="action-desc">Create a new offering learners can buy</div>
							</div>
						</button>
					</form>

					<form class="action-form" method="post" action="ongoingSkillSession.php">
						<button type="submit" class="action-card">
							<img class="action-icon" src="../../public/assets/preloads/View_Ongoing_Session.png" alt="Ongoing" />
							<div class="action-text">
								<div class="action-name">Ongoing Skill Session</div>
								<div class="action-desc">View active sessions and ongoing progress</div>
							</div>
						</button>
					</form>

					<form class="action-form" method="post" action="editSkillOffering.php">
						<button type="submit" class="action-card">
							<img class="action-icon" src="../../public/assets/preloads/Edit_Skill_Offerings.png" alt="Edit" />
							<div class="action-text">
								<div class="action-name">Edit Skill Offering</div>
								<div class="action-desc">Update or remove previously created offerings</div>
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
