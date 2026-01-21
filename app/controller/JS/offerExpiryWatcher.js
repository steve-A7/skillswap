(function () {
	function parseDate(str) {
		if (!str) return null;
		const d = new Date(str.replace(" ", "T"));
		if (isNaN(d.getTime())) return null;
		return d;
	}

	function updateDomExpiry() {
		const items = document.querySelectorAll(".offer-item");
		const now = new Date();

		items.forEach((el) => {
			const status = (el.getAttribute("data-status") || "").toLowerCase();
			if (status === "expired") return;

			const expStr = el.getAttribute("data-expires-at") || "";
			const exp = parseDate(expStr);
			if (!exp) return;

			if (exp.getTime() <= now.getTime()) {
				el.setAttribute("data-status", "expired");
				const badge = el.querySelector(".badge");
				if (badge) {
					badge.textContent = "expired";
					badge.classList.remove("badge-live");
					badge.classList.add("badge-expired");
				}
			}
		});
	}

	async function syncServerExpiry() {
		try {
			const res = await fetch("../Controller/updateOfferExpiry.php", {
				method: "POST",
				headers: { "Content-Type": "application/x-www-form-urlencoded" },
				body: "ping=1",
			});

			const data = await res.json();
			if (data && data.updated && parseInt(data.updated, 10) > 0) {
				updateDomExpiry();
			}
		} catch (err) {}
	}

	updateDomExpiry();
	syncServerExpiry();

	setInterval(() => {
		updateDomExpiry();
		syncServerExpiry();
	}, 30000);
})();
