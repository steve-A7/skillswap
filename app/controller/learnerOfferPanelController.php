<?php
include "../Model/DatabaseConnection.php";
include "../Model/OfferingTimeSlot.php";
include "../Model/OfferingDurationOption.php";
include "../Model/BookingRequest.php";

session_start();
header("Content-Type: application/json; charset=UTF-8");

$manualPay = $_POST["manual_payment_value"] ?? "";
$manualLast4 = $_POST["manual_card_last4"] ?? "";

function jsonOut($arr) {
	echo json_encode($arr);
	exit();
}

function safePath($p) {
	$p = $p ?? "";
	$p = str_replace("\\", "/", $p);
	return trim($p);
}

function makeUrl($p) {
	$p = safePath($p);
	if ($p === "") return "../../public/assets/preloads/logo.png";
	return "../../" . ltrim($p, "/");
}

function expireAvailableOfferings($conn) {
	$sql = "
		UPDATE mentor_skill_offerings
		SET current_status='expired'
		WHERE current_status='available'
		  AND DATE_ADD(created_at, INTERVAL offered_for HOUR) <= NOW()
	";
	$conn->query($sql);
}

function getLearnerId($conn, $userId) {
	$learnerId = 0;

	$stmt = $conn->prepare("SELECT learner_id FROM learner_profiles WHERE user_id = ? LIMIT 1");
	if ($stmt) {
		$uid = (int)$userId;
		$stmt->bind_param("i", $uid);
		$stmt->execute();
		$stmt->bind_result($lid);
		if ($stmt->fetch()) $learnerId = (int)$lid;
		$stmt->close();
	}

	return $learnerId;
}

function getLearnerPayment($conn, $learnerId) {
	$stmt = $conn->prepare("
		SELECT preferred_payment_method, paypal_email, bkash_number, nagad_number, card_last4, preferred_way_to_learn
		FROM learner_profiles
		WHERE learner_id = ?
		LIMIT 1
	");
	if (!$stmt) return null;

	$lid = (int)$learnerId;
	$stmt->bind_param("i", $lid);
	$stmt->execute();
	$stmt->store_result();

	if ($stmt->num_rows < 1) {
		$stmt->close();
		return null;
	}

	$method = null;
	$paypal = null;
	$bkash = null;
	$nagad = null;
	$last4 = null;
	$learnPref = null;

	$stmt->bind_result($method, $paypal, $bkash, $nagad, $last4, $learnPref);
	$stmt->fetch();
	$stmt->close();

	return [
		"preferred_payment_method" => $method,
		"paypal_email" => $paypal,
		"bkash_number" => $bkash,
		"nagad_number" => $nagad,
		"card_last4" => $last4,
		"preferred_way_to_learn" => $learnPref
	];
}

function getOfferingFull($conn, $offeringId) {
	$sql = "
		SELECT
			mso.offering_id,
			mso.mentor_id,
			mso.category_id,
			mso.skill_code,
			mso.skill_title,
			mso.price,
			mso.description,
			mso.offering_picture_path,
			mso.current_status,
			mso.created_at,
			mso.offered_for,
			sc.category_name,
			u.username AS mentor_username,
			mp.profile_picture_path AS mentor_picture_path,
			DATE_ADD(mso.created_at, INTERVAL mso.offered_for HOUR) AS expires_at
		FROM mentor_skill_offerings mso
		JOIN skill_categories sc ON sc.category_id = mso.category_id
		JOIN mentor_profiles mp ON mp.mentor_id = mso.mentor_id
		JOIN users u ON u.user_id = mp.user_id
		WHERE mso.offering_id = ?
		LIMIT 1
	";

	$stmt = $conn->prepare($sql);
	if (!$stmt) return null;

	$oid = (int)$offeringId;
	$stmt->bind_param("i", $oid);
	$stmt->execute();
	$stmt->store_result();

	if ($stmt->num_rows < 1) {
		$stmt->close();
		return null;
	}

	$offering_id = null;
	$mentor_id = null;
	$category_id = null;
	$skill_code = null;
	$skill_title = null;
	$price = null;
	$description = null;
	$pic = null;
	$status = null;
	$created_at = null;
	$offered_for = null;
	$category_name = null;
	$mentor_username = null;
	$mentor_pic = null;
	$expires_at = null;

	$stmt->bind_result(
		$offering_id,
		$mentor_id,
		$category_id,
		$skill_code,
		$skill_title,
		$price,
		$description,
		$pic,
		$status,
		$created_at,
		$offered_for,
		$category_name,
		$mentor_username,
		$mentor_pic,
		$expires_at
	);

	$stmt->fetch();
	$stmt->close();

	return [
		"offering_id" => (int)$offering_id,
		"mentor_id" => (int)$mentor_id,
		"category_id" => (int)$category_id,
		"skill_code" => $skill_code,
		"skill_title" => $skill_title,
		"price" => $price,
		"description" => $description,
		"offering_picture_path" => makeUrl($pic),
		"current_status" => $status,
		"created_at" => $created_at,
		"offered_for" => (int)$offered_for,
		"expires_at" => $expires_at,
		"category_name" => $category_name,
		"mentor_username" => $mentor_username,
		"mentor_picture_path" => makeUrl($mentor_pic)
	];
}

$isLoggedIn = $_SESSION["isLoggedIn"] ?? false;
if (!$isLoggedIn || (($_SESSION["Role"] ?? "") !== "learner")) {
	jsonOut(["ok" => false, "msg" => "Not logged in"]);
}

$db = new DatabaseConnection();
$conn = $db->getConnection();

$userId = (int)($_SESSION["user_id"] ?? $_SESSION["UserId"] ?? 0);
if ($userId < 1) {
	jsonOut(["ok" => false, "msg" => "Invalid user"]);
}

$learnerId = getLearnerId($conn, $userId);
if ($learnerId < 1) {
	jsonOut(["ok" => false, "msg" => "Learner profile missing"]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	header("Content-Type: text/html; charset=UTF-8");

	$offeringId = isset($_POST["offering_id"]) ? (int)$_POST["offering_id"] : 0;
	$slotId = isset($_POST["slot_id"]) ? (int)$_POST["slot_id"] : 0;
	$paymentMethod = $_POST["payment_method"] ?? "";

	if ($offeringId < 1 || $slotId < 1 || trim($paymentMethod) === "") {
		header("Location: ../view/learnerOfferPanel.php?offering_id=" . urlencode($offeringId));
		exit();
	}

	expireAvailableOfferings($conn);

	$offering = getOfferingFull($conn, $offeringId);
	if (!$offering) {
		header("Location: ../view/learnerBrowse.php");
		exit();
	}

	if (($offering["current_status"] ?? "") !== "available") {
		header("Location: ../view/learnerBrowse.php");
		exit();
	}

	$stmt = $conn->prepare("SELECT slot_id, day_of_week, start_time, end_time FROM offering_time_slots WHERE slot_id = ? AND offering_id = ? LIMIT 1");
	if (!$stmt) {
		header("Location: ../view/learnerOfferPanel.php?offering_id=" . urlencode($offeringId));
		exit();
	}

	$sid = (int)$slotId;
	$oid = (int)$offeringId;
	$stmt->bind_param("ii", $sid, $oid);
	$stmt->execute();
	$stmt->store_result();

	if ($stmt->num_rows < 1) {
		$stmt->close();
		header("Location: ../view/learnerOfferPanel.php?offering_id=" . urlencode($offeringId));
		exit();
	}

	$meetingMode = $_POST["meeting_mode"] ?? "";
	$meetingMode = trim($meetingMode);

	$allowedMeet = ["audio", "video", "both"];
	if (!in_array($meetingMode, $allowedMeet, true)) {
  $meetingMode = $payment["preferred_way_to_learn"] ?? "both";
	}


	$slot_id = null;
	$dow = null;
	$start = null;
	$end = null;

	$stmt->bind_result($slot_id, $dow, $start, $end);
	$stmt->fetch();
	$stmt->close();

	$durationModel = new OfferingDurationOption($db);
	$durations = $durationModel->listByOffering($offeringId);

	$durMin = null;
	if ($durations && count($durations) > 0) {
		$durMin = (int)$durations[0]["duration_minutes"];
	}

	$payment = getLearnerPayment($conn, $learnerId);

	$payMsg = "Payment Method: " . $paymentMethod;


	$savedMethod = $payment["preferred_payment_method"] ?? "";

$usingSaved = false;
if ($payment && $savedMethod === $paymentMethod) {
	if ($paymentMethod === "paypal" && !empty($payment["paypal_email"])) $usingSaved = true;
	if ($paymentMethod === "bkash" && !empty($payment["bkash_number"])) $usingSaved = true;
	if ($paymentMethod === "nagad" && !empty($payment["nagad_number"])) $usingSaved = true;
	if (($paymentMethod === "credit_card" || $paymentMethod === "debit_card") && !empty($payment["card_last4"])) $usingSaved = true;
}

if ($usingSaved) {
	if ($paymentMethod === "paypal") $payMsg .= " (" . $payment["paypal_email"] . ")";
	if ($paymentMethod === "bkash") $payMsg .= " (" . $payment["bkash_number"] . ")";
	if ($paymentMethod === "nagad") $payMsg .= " (" . $payment["nagad_number"] . ")";
	if ($paymentMethod === "credit_card" || $paymentMethod === "debit_card") $payMsg .= " (****" . $payment["card_last4"] . ")";
}
else {
	if (($paymentMethod === "credit_card" || $paymentMethod === "debit_card") && trim($manualLast4) !== "") {
		$payMsg .= " (entered: ****" . $manualLast4 . ")";
	}
	else if (trim($manualPay) !== "") {
		$payMsg .= " (entered: " . $manualPay . ")";
	}
}

	$payload = [
  		"learner_pref" => $meetingMode,
		"requested_slot_id" => (int)$slot_id,
		"booked_day_of_week" => $dow,
		"booked_start_time" => $start,
		"booked_end_time" => $end,
		"booked_duration_minutes" => $durMin,
		"message" => $payMsg,
		"preferred_time" => null,
		"request_type" => "initial",
		"previous_session_id" => null,
		"request_status" => "pending"
	];

	$bookingModel = new BookingRequest($db);
	$newId = $bookingModel->create($offeringId, $learnerId, $payload);

	if ($newId > 0) {
		header("Location: ../view/learnerMySkills.php");
		exit();
	}

	header("Location: ../view/learnerOfferPanel.php?offering_id=" . urlencode($offeringId));
	exit();
}

$offeringId = isset($_REQUEST["offering_id"]) ? (int)$_REQUEST["offering_id"] : 0;
if ($offeringId < 1) {
	jsonOut(["ok" => false, "msg" => "Missing offering_id"]);
}

expireAvailableOfferings($conn);

$offering = getOfferingFull($conn, $offeringId);
if (!$offering) {
	jsonOut(["ok" => false, "msg" => "Offering not found"]);
}

if (($offering["current_status"] ?? "") !== "available") {
	jsonOut(["ok" => false, "msg" => "Offering expired"]);
}

$slotModel = new OfferingTimeSlot($db);
$slots = $slotModel->listByOffering($offeringId);

$durationModel = new OfferingDurationOption($db);
$durations = $durationModel->listByOffering($offeringId);

$payment = getLearnerPayment($conn, $learnerId);

jsonOut([
	"ok" => true,
	"offering" => $offering,
	"slots" => $slots,
	"durations" => $durations,
	"learnerPayment" => $payment
]);
