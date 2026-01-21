(function () {
	const pendingList = document.getElementById("pendingList");
	const ongoingList = document.getElementById("ongoingList");
	const completedList = document.getElementById("completedList");

	const pendingEmpty = document.getElementById("pendingEmpty");
	const ongoingEmpty = document.getElementById("ongoingEmpty");
	const completedEmpty = document.getElementById("completedEmpty");

	const toast = document.getElementById("toast");

	const reviewModal = document.getElementById("reviewModal");
	const ratingInput = document.getElementById("ratingInput");
	const reviewInput = document.getElementById("reviewInput");
	const btnCancelReview = document.getElementById("btnCancelReview");
	const btnSubmitReview = document.getElementById("btnSubmitReview");
	const modalMsg = document.getElementById("modalMsg");

	let currentSessionIdForReview = 0;

	function showToast(msg, ok = true) {
		toast.className = "toast " + (ok ? "toast-ok" : "toast-bad");
		toast.innerText = msg;
		toast.style.display = "block";
		setTimeout(() => {
			toast.style.display = "none";
		}, 1800);
	}

	function esc(s) {
		return String(s ?? "")
			.replaceAll("&", "&amp;")
			.replaceAll("<", "&lt;")
			.replaceAll(">", "&gt;")
			.replaceAll('"', "&quot;")
			.replaceAll("'", "&#039;");
	}

	function starsText(n) {
		let out = "";
		for (let i = 1; i <= 5; i++) out += i <= n ? "★" : "☆";
		return out;
	}

	function renderPending(items) {
		pendingList.innerHTML = "";

		if (!items || items.length === 0) {
			pendingEmpty.style.display = "block";
			return;
		}

		pendingEmpty.style.display = "none";

		items.forEach((r) => {
			const card = document.createElement("div");
			card.className = "row-card";

			card.innerHTML = `
				<div class="left-offering">
					<div class="offering-square">
						<img class="offering-img" src="${esc(r.offering_picture_path)}" alt="Offering">
					</div>
					<div class="offering-title">${esc(r.skill_title)}</div>
				</div>

				<div class="right-info">
					<div class="learner-line">
						<img class="learner-mini" src="${esc(r.mentor_picture)}" alt="Mentor">
						<div class="learner-name">${esc(r.mentor_username)}</div>
					</div>

					<div class="info-lines">
						<div class="info-line">Session Duration: ${esc(r.booked_duration_minutes)} minutes</div>
						<div class="info-line">Meeting Mode: ${esc(r.meeting_mode || "both")}</div>
						<div class="info-line">Selected Time: ${esc(r.booked_day_of_week)} (${esc(r.booked_start_time)} - ${esc(r.booked_end_time)})</div>
					</div>
				</div>
			`;

			pendingList.appendChild(card);
		});
	}

	function renderOngoing(items) {
		ongoingList.innerHTML = "";

		if (!items || items.length === 0) {
			ongoingEmpty.style.display = "block";
			return;
		}

		ongoingEmpty.style.display = "none";

		items.forEach((s) => {
			const card = document.createElement("div");
			card.className = "row-card";

			card.innerHTML = `
				<div class="left-offering">
					<div class="offering-square">
						<img class="offering-img" src="${esc(s.offering_picture_path)}" alt="Offering">
					</div>
					<div class="offering-title">${esc(s.skill_title)}</div>
				</div>

				<div class="right-info">
					<div class="learner-line">
						<img class="learner-mini" src="${esc(s.mentor_picture)}" alt="Mentor">
						<div class="learner-name">${esc(s.mentor_username)}</div>
					</div>

					<div class="info-lines">
						<div class="info-line">Session Duration: ${esc(s.duration_minutes)} minutes</div>
						<div class="info-line">Meeting Way: ${esc(s.meeting_mode || "both")}</div>
						<div class="info-line">
							Meeting Link:
							<a class="meet-link" href="${esc(s.meeting_link)}" target="_blank">${esc(s.meeting_link)}</a>
						</div>
					</div>
				</div>
			`;

			ongoingList.appendChild(card);
		});
	}

	function renderCompleted(items) {
		completedList.innerHTML = "";

		if (!items || items.length === 0) {
			completedEmpty.style.display = "block";
			return;
		}

		completedEmpty.style.display = "none";

		items.forEach((c) => {
			const card = document.createElement("div");
			card.className = "row-card";

			let bottomHtml = "";

			if (!c.has_review) {
				bottomHtml = `
					<div class="actions">
						<button class="btn-action btn-accept btnGiveReview" data-session="${c.session_id}">
							Give Review
						</button>
					</div>
				`;
			} else {
				bottomHtml = `
					<div class="stars-wrap">
						<div class="stars">${starsText(Number(c.rating_value || 0))}</div>
						<div class="rating-number">(${esc(c.rating_value)}/5)</div>
					</div>
					<div class="review-text">${esc(c.review_text)}</div>
				`;
			}

			card.innerHTML = `
				<div class="left-offering">
					<div class="offering-square">
						<img class="offering-img" src="${esc(c.offering_picture_path)}" alt="Offering">
					</div>
					<div class="offering-title">${esc(c.skill_title)}</div>
				</div>

				<div class="right-info">
					<div class="learner-line">
						<img class="learner-mini" src="${esc(c.mentor_picture)}" alt="Mentor">
						<div class="learner-name">${esc(c.mentor_username)}</div>
					</div>

					<div class="info-lines">
						<div class="info-line">Session Duration: ${esc(c.duration_minutes)} minutes</div>
						<div class="info-line">Meeting Way: ${esc(c.meeting_mode || "both")}</div>
					</div>

					${bottomHtml}
				</div>
			`;

			completedList.appendChild(card);
		});

		document.querySelectorAll(".btnGiveReview").forEach((b) => {
			b.addEventListener("click", () => {
				const sid = Number(b.dataset.session || 0);
				openReviewModal(sid);
			});
		});
	}

	async function loadAll() {
		try {
			const res = await fetch(
				"../controller/learnerMySkillsController.php?action=list",
			);
			const data = await res.json();

			if (!data.ok) {
				showToast(data.message || "Failed to load", false);
				return;
			}

			renderPending(data.pending || []);
			renderOngoing(data.ongoing || []);
			renderCompleted(data.completed || []);
		} catch (e) {
			showToast("Network error", false);
		}
	}

	function openReviewModal(sessionId) {
		currentSessionIdForReview = sessionId;
		ratingInput.value = "";
		reviewInput.value = "";
		modalMsg.style.display = "none";
		reviewModal.style.display = "flex";
	}

	function closeReviewModal() {
		reviewModal.style.display = "none";
		currentSessionIdForReview = 0;
	}

	btnCancelReview.addEventListener("click", () => {
		closeReviewModal();
	});

	reviewModal.addEventListener("click", (e) => {
		if (e.target === reviewModal) closeReviewModal();
	});

	btnSubmitReview.addEventListener("click", async () => {
		const ratingVal = Number(ratingInput.value || 0);
		const reviewTxt = String(reviewInput.value || "").trim();

		if (!currentSessionIdForReview) return;

		if (ratingVal < 1 || ratingVal > 5) {
			modalMsg.innerText = "Enter rating between 1 and 5.";
			modalMsg.style.display = "block";
			return;
		}

		if (!reviewTxt) {
			modalMsg.innerText = "Enter feedback/review text.";
			modalMsg.style.display = "block";
			return;
		}

		try {
			const form = new FormData();
			form.append("session_id", String(currentSessionIdForReview));
			form.append("rating_value", String(ratingVal));
			form.append("review_text", reviewTxt);

			const res = await fetch(
				"../controller/learnerMySkillsController.php?action=review",
				{
					method: "POST",
					body: form,
				},
			);

			const data = await res.json();

			if (!data.ok) {
				modalMsg.innerText = data.message || "Failed to submit review";
				modalMsg.style.display = "block";
				return;
			}

			closeReviewModal();
			showToast(data.message || "Review submitted", true);
			loadAll();
		} catch (e) {
			modalMsg.innerText = "Network error";
			modalMsg.style.display = "block";
		}
	});

	loadAll();
	setInterval(loadAll, 5000);
})();
