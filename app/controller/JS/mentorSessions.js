document.addEventListener("DOMContentLoaded", () => {
	const requestsList = document.getElementById("requestsList");
	const sessionsList = document.getElementById("sessionsList");

	const requestsEmpty = document.getElementById("requestsEmpty");
	const sessionsEmpty = document.getElementById("sessionsEmpty");

	const toast = document.getElementById("toast");

	function showToast(msg, ok = true) {
		toast.style.display = "block";
		toast.classList.remove("toast-ok", "toast-bad");
		toast.classList.add(ok ? "toast-ok" : "toast-bad");
		toast.textContent = msg;

		setTimeout(() => {
			toast.style.display = "none";
		}, 1500);
	}

	function safeOfferingImg(path) {
		if (!path || path.trim() === "") {
			return "../../public/assets/preloads/Edit_Skill_Offerings.png";
		}
		return path;
	}

	function safeLearnerImg(path) {
		if (!path || path.trim() === "") {
			return "../../public/assets/preloads/user.png";
		}
		return path;
	}

	function niceMode(m) {
		if (!m) return "Both";
		const t = m.toLowerCase();
		if (t === "audio") return "Audio";
		if (t === "video") return "Video";
		return "Both";
	}

	function createOfferingLeft(imgPath, title) {
		const left = document.createElement("div");
		left.className = "left-offering";

		const square = document.createElement("div");
		square.className = "offering-square";

		const img = document.createElement("img");
		img.className = "offering-img";
		img.src = safeOfferingImg(imgPath);
		img.alt = "Offering";

		img.onerror = () => {
			img.src = "../../public/assets/preloads/Edit_Skill_Offerings.png";
		};

		square.appendChild(img);

		const name = document.createElement("div");
		name.className = "offering-title";
		name.textContent = title || "Untitled Offering";

		left.appendChild(square);
		left.appendChild(name);

		return left;
	}

	function createLearnerLine(pic, username) {
		const row = document.createElement("div");
		row.className = "learner-line";

		const img = document.createElement("img");
		img.className = "learner-mini";
		img.src = safeLearnerImg(pic);
		img.alt = "Learner";

		img.onerror = () => {
			img.src = "../../public/assets/preloads/user.png";
		};

		const span = document.createElement("div");
		span.className = "learner-name";
		span.textContent = username || "Learner";

		row.appendChild(img);
		row.appendChild(span);
		return row;
	}

	function createRequestRow(r) {
		const row = document.createElement("div");
		row.className = "row-card";

		const left = createOfferingLeft(r.offering_picture_path, r.skill_title);

		const right = document.createElement("div");
		right.className = "right-info";

		right.appendChild(createLearnerLine(r.learner_picture, r.learner_username));

		const info = document.createElement("div");
		info.className = "info-lines";

		const line1 = document.createElement("div");
		line1.className = "info-line";
		line1.textContent =
			"Preferred Day & Time: " +
			(r.booked_day_of_week || "-") +
			" • " +
			(r.booked_start_time || "-") +
			" → " +
			(r.booked_end_time || "-");

		const line2 = document.createElement("div");
		line2.className = "info-line";
		line2.textContent =
			"Session Duration: " +
			(r.booked_duration_minutes || "-") +
			" minutes  |  Meeting Way: " +
			niceMode(r.learner_preference);

		info.appendChild(line1);
		info.appendChild(line2);

		const actions = document.createElement("div");
		actions.className = "actions";

		const acceptBtn = document.createElement("button");
		acceptBtn.type = "button";
		acceptBtn.className = "btn-action btn-accept";
		acceptBtn.textContent = "Accept";

		const rejectBtn = document.createElement("button");
		rejectBtn.type = "button";
		rejectBtn.className = "btn-action btn-reject";
		rejectBtn.textContent = "Reject";

		acceptBtn.addEventListener("click", async () => {
			try {
				const fd = new FormData();
				fd.append("action", "accept");
				fd.append("booking_id", r.booking_id);

				const res = await fetch("../Controller/mentorSessionsController.php", {
					method: "POST",
					body: fd,
				});

				const data = await res.json();
				if (data.ok) {
					showToast("Accepted & session created", true);
					loadAll();
				} else {
					showToast(data.message || "Failed to accept", false);
				}
			} catch (e) {
				showToast("Something went wrong", false);
			}
		});

		rejectBtn.addEventListener("click", async () => {
			try {
				const fd = new FormData();
				fd.append("action", "reject");
				fd.append("booking_id", r.booking_id);

				const res = await fetch("../Controller/mentorSessionsController.php", {
					method: "POST",
					body: fd,
				});

				const data = await res.json();
				if (data.ok) {
					showToast("Request rejected", true);
					loadAll();
				} else {
					showToast(data.message || "Failed to reject", false);
				}
			} catch (e) {
				showToast("Something went wrong", false);
			}
		});

		actions.appendChild(acceptBtn);
		actions.appendChild(rejectBtn);

		right.appendChild(info);
		right.appendChild(actions);

		row.appendChild(left);
		row.appendChild(right);

		return row;
	}

	function createSessionRow(s) {
		const row = document.createElement("div");
		row.className = "row-card";

		const left = createOfferingLeft(s.offering_picture_path, s.skill_title);

		const right = document.createElement("div");
		right.className = "right-info";

		right.appendChild(createLearnerLine(s.learner_picture, s.learner_username));

		const info = document.createElement("div");
		info.className = "info-lines";

		const line1 = document.createElement("div");
		line1.className = "info-line";
		line1.textContent =
			"Session Duration: " +
			(s.duration_minutes || "-") +
			" minutes  |  Meeting Way: " +
			niceMode(s.meeting_mode);

		const line2 = document.createElement("div");
		line2.className = "info-line";

		const link = document.createElement("a");
		link.className = "meet-link";
		link.href = s.meeting_link || "#";
		link.target = "_blank";
		link.textContent = s.meeting_link ? "Join Google Meet" : "No link";

		line2.appendChild(document.createTextNode("Meeting Link: "));
		line2.appendChild(link);

		info.appendChild(line1);
		info.appendChild(line2);

		right.appendChild(info);

		row.appendChild(left);
		row.appendChild(right);

		return row;
	}

	function renderRequests(list) {
		requestsList.innerHTML = "";

		if (!list || list.length === 0) {
			requestsEmpty.style.display = "block";
			return;
		}

		requestsEmpty.style.display = "none";
		list.forEach((r) => requestsList.appendChild(createRequestRow(r)));
	}

	function renderSessions(list) {
		sessionsList.innerHTML = "";

		if (!list || list.length === 0) {
			sessionsEmpty.style.display = "block";
			return;
		}

		sessionsEmpty.style.display = "none";
		list.forEach((s) => sessionsList.appendChild(createSessionRow(s)));
	}

	async function loadAll() {
		try {
			const res = await fetch(
				"../Controller/mentorSessionsController.php?action=list",
			);
			const data = await res.json();

			if (!data.ok) {
				renderRequests([]);
				renderSessions([]);
				return;
			}

			renderRequests(data.requests || []);
			renderSessions(data.sessions || []);
		} catch (e) {
			renderRequests([]);
			renderSessions([]);
		}
	}

	async function runAutoComplete() {
		try {
			await fetch(
				"../Controller/mentorSessionsController.php?action=autocomplete",
			);
		} catch (e) {}
	}

	loadAll();

	setInterval(async () => {
		await runAutoComplete();
		loadAll();
	}, 20000);
});
