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
    <link rel="stylesheet" href="../../public/css/learnerMySkillsCSS.css" />
   <script src="../controller/JS/learnerMySkills.js" defer></script>
   <script src="../controller/JS/learnerSessionAutoCompleteWatcher.js" defer></script>

</head>

<body>
    <div class="bg-layer"></div>
    <div class="tint-layer"></div>

    <div class="page">
        <div class="topbar">
           <div class="logo-wrap">
            <img class="logo-img" src="../../public/assets/preloads/logo.svg" alt="Logo">
            <span class="logo-text">SkillSwap</span>
           </div>

            <a class="logout-btn" href="../controller/logout.php">Logout</a>
        </div>

        <hr class="hr-line"/>
        
        <div class="content">
            <div class="card">

                <div class="card-top">
                    <div class="card-left">
                        <a class="btn-back" href="learnerDashboard.php">
                            <img class="icon-img" src="../../public/assets/preloads/back.png" alt="Back">
                            <span>Back</span>
                        </a>
                    </div>

                    <div class="card-center">
                        <div class="page-title">ViewMy Skills</div>
                        <div class="page-subtitle">
                            <?php echo htmlspecialchars($userName); ?>, view pending requests, ongoing sessions & completed reviews.
                        </div>
                    </div>
                </div>

                <div class="sections">

                    <div class="section">
                        <div class="section-title">Pending Session Requests</div>

                        <div id="pendingList" class="list"></div>

                        <div id="pendingEmpty" class="empty-state" style="display:none;">
                            No pending session requests found.
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="section">
                        <div class="section-title">Ongoing Sessions</div>

                        <div id="ongoingList" class="list"></div>

                        <div id="ongoingEmpty" class="empty-state" style="display:none;">
                            No ongoing sessions found.
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="section">
                        <div class="section-title">Completed Sessions</div>

                        <div id="completedList" class="list"></div>

                        <div id="completedEmpty" class="empty-state" style="display:none;">
                            No completed sessions found.
                        </div>
                    </div>

                </div>

                <div id="reviewModal" class="modal-wrap" style="display:none;">
                    <div class="modal-card">
                        <div class="modal-title">Give Review</div>

                        <div class="modal-line">
                            <label class="modal-label">Rating (1 - 5)</label>
                            <input id="ratingInput" class="modal-input" type="number" min="1" max="5" placeholder="Enter rating out of 5" />
                        </div>

                        <div class="modal-line">
                            <label class="modal-label">Feedback</label>
                            <textarea id="reviewInput" class="modal-textarea" maxlength="250" placeholder="Enter your feedback..."></textarea>
                        </div>

                        <div class="modal-actions">
                            <button id="btnCancelReview" class="btn-action btn-reject" type="button">Cancel</button>
                            <button id="btnSubmitReview" class="btn-action btn-accept" type="button">Submit</button>
                        </div>

                        <div id="modalMsg" class="modal-msg" style="display:none;"></div>
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
