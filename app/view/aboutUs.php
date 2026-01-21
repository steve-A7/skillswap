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
		<link
			rel="icon"
			type="image/svg"
			href="../../public/assets/preloads/logo.svg"
		/>
		<title>SkillSwap - About Us</title>

		<link rel="stylesheet" href="../../public/css/aboutUsCSS.css" />
	</head>

	<body>
		<div class="bg-layer"></div>
		<div class="tint-layer"></div>

		<div class="page">
			<div class="topbar">
				<div class="logo-wrap">
					<img
						class="logo-img"
						src="../../public/assets/preloads/logo.svg"
						alt="SkillSwap Logo"
						onerror="this.style.display = 'none'"
					/>
					<div class="logo-text">SkillSwap</div>
				</div>

				<div class="nav-right">
					<form
						class="action-form"
						method="post"
						action="..\controller\landingNav.php"
					>
						<input type="hidden" name="nav" value="Home" />
						<button class="btn" type="submit">Home</button>
					</form>

					<form
						class="action-form"
						method="post"
						action="..\controller\landingNav.php"
					>
						<input type="hidden" name="nav" value="AboutUs" />
						<button class="btn" type="submit">About Us</button>
					</form>

					<form
						class="action-form"
						method="post"
						action="..\controller\landingNav.php"
					>
						<input type="hidden" name="nav" value="SignUp" />
						<button class="btn" type="submit">Sign Up</button>
					</form>

					<form
						class="action-form"
						method="post"
						action="..\controller\landingNav.php"
					>
						<input type="hidden" name="nav" value="Login" />
						<button class="btn" type="submit">Login</button>
					</form>
				</div>
			</div>

			<hr class="hr-line" />

			<div class="hero-wrap">
				<div class="hero-card about-card">
					<div class="about-title">About SkillSwap</div>
					<div class="about-subtitle">
						This is a peer learning and mentoring platform started in
						<strong>2026</strong>. SkillSwap connects learners and mentors
						through skill offerings, booking requests, learning sessions, and
						feedback-based trust building.
					</div>

					<div class="about-grid">
						<div class="about-box">
							<div class="about-box-title">Learner Features</div>
							<ul class="about-list">
								<li>Browse skill offerings created by mentors</li>
								<li>Request for a booked session</li>
								<li>Join and complete skill learning sessions</li>
								<li>Leave a review after completion</li>
							</ul>
						</div>

						<div class="about-box">
							<div class="about-box-title">Mentor Features</div>
							<ul class="about-list">
								<li>Create skill offerings within selected skill categories</li>
								<li>Handle booking requests (accept / reject)</li>
								<li>View ongoing sessions</li>
								<li>Edit available skill offerings anytime</li>
								<li>View ratings and user feedback from completed sessions</li>
							</ul>
						</div>
					</div>

					<div class="about-bottom">
						<div class="about-bottom-title">Common Features</div>
						<ul class="about-list">
							<li>Both learners and mentors can view their profile</li>
							<li>Both can modify profile information</li>
							<li>Both can delete their account/profile</li>
						</ul>

						<div class="about-note">
							SkillSwap is designed to be simple, clean, and focused on real
							learning outcomes — where every session ends with feedback to help
							users choose better mentors and skills.
						</div>
					</div>
				</div>
			</div>
		</div>

		<footer class="footer-bar">
			<span>Copyright © 2026 SkillSwap. All rights reserved.</span>
		</footer>
	</body>
</html>
