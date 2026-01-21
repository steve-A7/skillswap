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

$offeringId = isset($_GET["offering_id"]) ? (int)$_GET["offering_id"] : 0;
if ($offeringId < 1) {
	header("Location: learnerBrowse.php");
	exit();
}

$db = new DatabaseConnection();
$learnerModel = new LearnerProfile($db);

$userId = (int)($_SESSION["user_id"] ?? $_SESSION["UserId"] ?? 0);

$profilePicUrl = "";
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
	<link rel="stylesheet" href="../../public/css/learnerOfferPanelCSS.css" />
	<script defer src="../controller/JS/learnerOfferPanel.js"></script>
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

			<div class="topbar-center"></div>

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
							<a class="icon-btn" href="learnerBrowse.php">
								<img class="icon-img" src="../../public/assets/preloads/back.png" alt="Back">
								<span>Back</span>
							</a>
						</div>

						<div class="card-center">
							<div class="page-title">Offering Details</div>
							<div class="page-subtitle">
								<?php echo htmlspecialchars($userName); ?>, review details and confirm your purchase.
							</div>
						</div>

						<div class="card-right">
                    <a class="icon-btn" href="learnerDashboard.php">
                        <img class="icon-img" src="../../public/assets/preloads/home.png" alt="Home">
                        <span>Home</span>
                    </a>
                    </div>
					</div>

					<div id="lopToast" class="toast" style="display:none;"></div>

					<div id="lopLoading" class="lop-loading">Loading offering...</div>
					<div id="lopError" class="lop-error" style="display:none;"></div>

					<div id="lopWrap" style="display:none;">

						<div class="lop-offerGrid">
							<div class="lop-leftBox">
								<img id="lopOfferImg" class="lop-offerImg" src="../../public/assets/preloads/logo.png" alt="Offering">
							</div>

							<div class="lop-rightBox">

								<div class="lop-row"><span class="lop-k">Skill Name:</span> <span id="lopSkillTitle" class="lop-v"></span></div>
								<div class="lop-row"><span class="lop-k">Skill Code:</span> <span id="lopSkillCode" class="lop-v"></span></div>
								<div class="lop-row"><span class="lop-k">Skill Category:</span> <span id="lopSkillCategory" class="lop-v"></span></div>
								<div class="lop-row"><span class="lop-k">Session Duration:</span> <span id="lopDuration" class="lop-v"></span></div>
								<div class="lop-row"><span class="lop-k">Price:</span> <span id="lopPrice" class="lop-v"></span></div>

								<div class="lop-row lop-descRow">
									<span class="lop-k">Description:</span>
									<span id="lopDesc" class="lop-v lop-desc"></span>
								</div>

								<div class="lop-mentorRow">
									<img id="lopMentorImg" class="lop-mentorImg" src="../../public/assets/preloads/logo.png" alt="Mentor">
									<div class="lop-mentorName" id="lopMentorName"></div>
								</div>

								<div class="lop-actions">
									<button id="lopBuyBtn" class="buy-btn" type="button">Buy</button>
								</div>

							</div>
						</div>

						<div id="lopExpand" class="lop-expand">
  							<div class="lop-expandInner">

    							<div class="lop-field">
      						<div class="lop-label">Choose Time</div>
      							<select id="lopSlotSelect" class="lop-select">
        								<option value="">Select a time slot</option>
      							</select>
      						<div id="lopSlotHint" class="lop-hint"></div>
    							</div>

    							<div class="lop-field">
      						<div class="lop-label">Meeting Type</div>
      							<select id="lopMeetSelect" class="lop-select">
        							<option value="">Select meeting type</option>
        							<option value="audio">Audio</option>
        							<option value="video">Video</option>
        							<option value="both">Both</option>
      							</select>
      							<div id="lopMeetHint" class="lop-hint"></div>
    							</div>

    <div class="lop-field">

      <div class="lop-label">Payment Method</div>

      <select id="lopPaySelect" class="lop-select">
        <option value="">Select payment method</option>
        <option value="paypal">PayPal</option>
        <option value="credit_card">Credit Card</option>
        <option value="debit_card">Debit Card</option>
        <option value="bkash">bKash</option>
        <option value="nagad">Nagad</option>
      </select>

      <div id="lopSavedPayInfo" class="lop-savedPay"></div>

      <div id="lopManualPay" class="lop-manualPay" style="display:none;">

        <div id="lopPhoneRow" class="lop-manualRow" style="display:none;">
          <div class="lop-manualLabel" id="lopPhoneLabel">Enter Number</div>
          <input id="lopPhoneInput" class="lop-input" type="text" placeholder="Enter your number">
        </div>

        <div id="lopEmailRow" class="lop-manualRow" style="display:none;">
          <div class="lop-manualLabel">Enter PayPal Email</div>
          <input id="lopEmailInput" class="lop-input" type="text" placeholder="Enter your PayPal email">
        </div>

        <div id="lopCardRow" class="lop-manualRow" style="display:none;">
          <div class="lop-manualLabel">Enter Card Number</div>
          <input id="lopCardInput" class="lop-input" type="text" placeholder="Enter card number">
          <div id="lopCardHint" class="lop-cardHint"></div>
        </div>

      </div>
    </div>

    <form id="lopConfirmForm"
          method="POST"
          action="../controller/learnerOfferPanelController.php"
          class="lop-confirmRow">

      <input type="hidden" name="offering_id" value="<?php echo (int)$offeringId; ?>">

      <input type="hidden" id="lopHiddenSlotId" name="slot_id" value="">
      <input type="hidden" id="lopHiddenMeeting" name="meeting_mode" value="">
      <input type="hidden" id="lopHiddenPayment" name="payment_method" value="">
      <input type="hidden" id="lopHiddenManualPay" name="manual_payment_value" value="">
      <input type="hidden" id="lopHiddenManualLast4" name="manual_card_last4" value="">

      <button class="btn" type="submit">Confirm Purchase</button>
    </form>

  </div>
</div>


			          
	


							</div>
						</div>

					</div>

					<script>
						window.__LEARNER_OFFER_PANEL__ = {
							offeringId: <?php echo (int)$offeringId; ?>
						};
					</script>

				</div>
			</div>
		</div>

	</div>

	<footer class="footer-bar">
    <span>Copyright © 2026 SkillSwap. All rights reserved.</span>
    </footer>
</body>
</html>
