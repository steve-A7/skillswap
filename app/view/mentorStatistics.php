<?php
include "../Model/DatabaseConnection.php";

session_start();

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$userId = (int)($_SESSION["user_id"] ?? ($_SESSION["UserId"] ?? 0));
$userName = $_SESSION["UserName"] ?? "Mentor";

$db = new DatabaseConnection();
$conn = $db->getConnection();

$mentorId = 0;

$sqlMentor = "
    SELECT mp.mentor_id, u.username
    FROM mentor_profiles mp
    JOIN users u ON mp.user_id = u.user_id
    WHERE mp.user_id = ?
    LIMIT 1
";
$stmtMentor = $conn->prepare($sqlMentor);
if ($stmtMentor) {
    $stmtMentor->bind_param("i", $userId);
    $stmtMentor->execute();
    $stmtMentor->bind_result($mentorIdDb, $mentorUsernameDb);
    if ($stmtMentor->fetch()) {
        $mentorId = (int)$mentorIdDb;
        $userName = $mentorUsernameDb ?? $userName;
    }
    $stmtMentor->close();
}

if ($mentorId <= 0) {
    header("Location: mentorDashboard.php");
    exit();
}

$avgRating = 0;
$totalReviews = 0;

$sqlAvg = "
    SELECT AVG(r.rating) AS avg_rating, COUNT(*) AS total_reviews
    FROM rating r
    JOIN sessions s ON r.session_id = s.session_id
    WHERE r.mentor_id = ?
      AND s.session_status = 'completed'
";
$stmtAvg = $conn->prepare($sqlAvg);
if ($stmtAvg) {
    $stmtAvg->bind_param("i", $mentorId);
    $stmtAvg->execute();
    $stmtAvg->bind_result($avgDb, $countDb);
    if ($stmtAvg->fetch()) {
        $avgRating = ($avgDb !== null) ? (float)$avgDb : 0;
        $totalReviews = (int)$countDb;
    }
    $stmtAvg->close();
}

$avgDisplay = number_format((float)$avgRating, 2);
$avgStars = (int)round($avgRating);
if ($avgStars < 0) $avgStars = 0;
if ($avgStars > 5) $avgStars = 5;

$reviews = [];

$sqlReviews = "
    SELECT
        r.rating_id,
        r.rating,
        r.review,
        r.created_at,

        mso.offering_id,
        mso.skill_title,

        lp.profile_picture_path AS learner_pic,
        u.username AS learner_username

    FROM rating r
    JOIN sessions s ON r.session_id = s.session_id
    JOIN mentor_skill_offerings mso ON r.offering_id = mso.offering_id
    JOIN learner_profiles lp ON r.rated_by_learner_id = lp.learner_id
    JOIN users u ON lp.user_id = u.user_id

    WHERE r.mentor_id = ?
      AND s.session_status = 'completed'

    ORDER BY r.created_at DESC
";
$stmtReviews = $conn->prepare($sqlReviews);
if ($stmtReviews) {
    $stmtReviews->bind_param("i", $mentorId);
    $stmtReviews->execute();
    $result = $stmtReviews->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
    }
    $stmtReviews->close();
}

function getStarsHtml($val, $small = false) {
    $val = (int)$val;
    if ($val < 0) $val = 0;
    if ($val > 5) $val = 5;

    $cls = $small ? "stars small" : "stars";

    $html = '<div class="'.$cls.'">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $val) {
            $html .= '<span class="star filled">★</span>';
        } else {
            $html .= '<span class="star empty">★</span>';
        }
    }
    $html .= "</div>";
    return $html;
}

function safePicPath($path) {
    if (!$path) return "";
    $path = trim($path);
    if ($path === "") return "";
    $path = str_replace("\\", "/", $path);

    if (strpos($path, "http://") === 0 || strpos($path, "https://") === 0) {
        return $path;
    }

    if (strpos($path, "public/") === 0) {
        return "../../" . ltrim($path, "/");
    }

    return "../../public/assets/uploads/" . ltrim($path, "/");
}

function initials($name) {
    $name = trim($name);
    if ($name === "") return "U";
    $parts = preg_split("/\s+/", $name);
    $out = "";
    foreach ($parts as $p) {
        $out .= strtoupper(substr($p, 0, 1));
        if (strlen($out) >= 2) break;
    }
    return $out;
}
?>

<html>
<head>
	<meta charset="UTF-8" />
    <link rel="icon" type="image/svg" href="../../public/assets/preloads/logo.svg">
    <title>SwillSwap</title>
	<link rel="stylesheet" href="../../public/css/mentorStatisticsCSS.css" />
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

			<form method="post" action="../Controller/logout.php">
				<button class="btn" type="submit">Logout</button>
			</form>
		</div>

		<hr class="hr-line" />

		<div class="content-card">
			<div class="content-header">
            <a class="back-btn" href="mentorDashboard.php">
                  <img class="icon-img" src="../../public/assets/preloads/back.png" alt="Back">
                  <span>Back</span>
            </a>
				<div class="title"><?php echo htmlspecialchars($userName); ?>'s Statistics</div>
				<div class="spacer"></div>
			</div>

			<div class="avg-card">
				<div class="avg-label">Avg Rating</div>

				<div class="avg-row">
					<?php echo getStarsHtml($avgStars, false); ?>
					<div class="avg-number"><?php echo $avgDisplay; ?></div>
				</div>

				<div class="avg-sub">
					Based on <?php echo (int)$totalReviews; ?> completed review(s)
				</div>
			</div>

			<div class="review-section">
				<div class="review-title">Reviews</div>

				<?php if (count($reviews) <= 0) { ?>
					<div class="empty-box">No feedback yet.</div>
				<?php } else { ?>

					<div class="review-list">
						<?php foreach ($reviews as $r) { ?>

							<?php
								$skillTitle = $r["skill_title"] ?? "Offering";
								$offeringId = (int)($r["offering_id"] ?? 0);

								$learnerName = $r["learner_username"] ?? "Learner";
								$learnerPic = safePicPath($r["learner_pic"] ?? "");

								$ratingVal = (int)($r["rating"] ?? 0);
								$reviewText = $r["review"] ?? "";

								$dateRaw = $r["created_at"] ?? "";
								$dateStr = $dateRaw ? date("d M Y", strtotime($dateRaw)) : "";
							?>

							<div class="review-card">
								<div class="left-side">
									<?php if ($learnerPic !== "") { ?>
										<img class="learner-pic" src="<?php echo htmlspecialchars($learnerPic); ?>" alt="Learner" />
									<?php } else { ?>
										<div class="learner-fallback"><?php echo htmlspecialchars(initials($learnerName)); ?></div>
									<?php } ?>
								</div>

								<div class="right-side">
									<div class="top-row">
										<div class="course-title">
											<?php echo htmlspecialchars($skillTitle); ?>
											<span class="course-id">#<?php echo $offeringId; ?></span>
										</div>

										<div class="rate-box">
											<?php echo getStarsHtml($ratingVal, true); ?>
											<div class="rate-num"><?php echo $ratingVal; ?></div>
										</div>
									</div>

									<div class="learner-line">
										Learner: <span class="learner-bold"><?php echo htmlspecialchars($learnerName); ?></span>
									</div>

									<?php if (trim($reviewText) !== "") { ?>
										<div class="review-text"><?php echo nl2br(htmlspecialchars($reviewText)); ?></div>
									<?php } else { ?>
										<div class="review-text muted">No written review.</div>
									<?php } ?>

									<?php if ($dateStr !== "") { ?>
										<div class="date-line"><?php echo htmlspecialchars($dateStr); ?></div>
									<?php } ?>
								</div>
							</div>

						<?php } ?>
					</div>

				<?php } ?>
			</div>
		</div>
	</div>

    <footer class="footer-bar">
    <span>Copyright © 2026 SkillSwap. All rights reserved.</span>
    </footer>

</body>
</html>
