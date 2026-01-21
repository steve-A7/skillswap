document.addEventListener("DOMContentLoaded", () => {
	const searchPill = document.getElementById("searchPill");
	const searchTrigger = document.getElementById("searchTrigger");

	const searchOverlay = document.getElementById("searchOverlay");
	const closeSearchBtn = document.getElementById("closeSearchBtn");
	const clearBtn = document.getElementById("clearBtn");

	const searchInput = document.getElementById("searchInput");

	const grid = document.getElementById("offeringsGrid");
	const emptyState = document.getElementById("emptyState");
	const toast = document.getElementById("toast");

	let debounceTimer = null;
	let lastQuery = "";

	if (!searchOverlay || !searchInput || !grid || !emptyState) return;

	function showToast(msg, ok = true) {
		if (!toast) return;

		toast.style.display = "block";
		toast.classList.remove("toast-ok", "toast-bad");
		toast.classList.add(ok ? "toast-ok" : "toast-bad");
		toast.textContent = msg;

		setTimeout(() => {
			toast.style.display = "none";
		}, 1500);
	}

	function openSearch() {
		document.body.classList.add("search-open");
		searchOverlay.classList.add("active");
		searchOverlay.setAttribute("aria-hidden", "false");

		grid.innerHTML = "";
		emptyState.style.display = "block";
		emptyState.textContent = "Start typing to search offerings...";
		lastQuery = "";
		searchInput.value = "";

		setTimeout(() => {
			searchInput.focus();
		}, 80);
	}

	function closeSearch() {
		document.body.classList.remove("search-open");
		searchOverlay.classList.remove("active");
		searchOverlay.setAttribute("aria-hidden", "true");

		searchInput.value = "";
		lastQuery = "";
		grid.innerHTML = "";
		emptyState.style.display = "block";
		emptyState.textContent = "Start typing to search offerings...";
	}

	function safeImg(path) {
		if (!path || String(path).trim() === "") {
			return "../../public/assets/preloads/logo.png";
		}
		return path;
	}

	function parseDate(str) {
		if (!str) return null;

		let clean = String(str).trim();
		if (clean.includes(".")) clean = clean.split(".")[0];
		clean = clean.replace(" ", "T");

		const d = new Date(clean);
		if (isNaN(d.getTime())) return null;
		return d;
	}

	function formatRemaining(ms) {
		if (ms <= 0) return "Expired";

		const totalSec = Math.floor(ms / 1000);
		const h = Math.floor(totalSec / 3600);
		const m = Math.floor((totalSec % 3600) / 60);
		const s = totalSec % 60;

		if (h > 0) return `${h}h ${m}m left`;
		if (m > 0) return `${m}m ${s}s left`;
		return `${s}s left`;
	}

	function getExpiresAt(o) {
		if (o.expires_at) {
			const exp = parseDate(o.expires_at);
			if (exp) return exp;
		}

		const created = parseDate(o.created_at);
		const hours = parseFloat(o.offered_for || 0);
		if (!created || !isFinite(hours) || hours <= 0) return null;

		return new Date(created.getTime() + hours * 3600 * 1000);
	}

	function createOfferingTile(o) {
		const tile = document.createElement("div");
		tile.className = "offering-tile offer-item";

		const expiresAt = getExpiresAt(o);

		const squareBtn = document.createElement("button");
		squareBtn.type = "button";
		squareBtn.className = "offering-square";

		const img = document.createElement("img");
		img.className = "offering-img";
		img.src = safeImg(o.offering_picture_path);
		img.alt = "Offering";

		img.onerror = () => {
			img.src = "../../public/assets/preloads/logo.png";
		};

		squareBtn.appendChild(img);

		const name = document.createElement("div");
		name.className = "offering-title";
		name.textContent = o.skill_title || "Untitled Offering";

		const sub = document.createElement("div");
		sub.className = "offering-sub";
		sub.textContent = "Calculating time...";

		function goToOfferPanel() {
			const now = new Date();
			if (expiresAt && expiresAt.getTime() <= now.getTime()) {
				showToast("This offering is expired", false);
				return;
			}

			window.location.href =
				"learnerOfferPanel.php?offering_id=" +
				encodeURIComponent(o.offering_id);
		}

		squareBtn.addEventListener("click", goToOfferPanel);
		name.addEventListener("click", goToOfferPanel);

		tile.appendChild(squareBtn);
		tile.appendChild(name);
		tile.appendChild(sub);

		function tick() {
			const now = new Date();

			if (!expiresAt) {
				sub.textContent = "Time not available";
				return;
			}

			const ms = expiresAt.getTime() - now.getTime();
			sub.textContent = formatRemaining(ms);
		}

		tick();
		tile._tick = tick;

		return tile;
	}

	function renderOfferings(list) {
		grid.innerHTML = "";

		if (!list || list.length === 0) {
			emptyState.style.display = "block";
			emptyState.textContent = "No offerings found.";
			return;
		}

		emptyState.style.display = "none";

		list.forEach((o) => {
			grid.appendChild(createOfferingTile(o));
		});
	}

	async function syncOfferExpiry() {
		try {
			await fetch("../controller/updateOfferExpiry.php", {
				method: "POST",
				headers: { "Content-Type": "application/x-www-form-urlencoded" },
				body: "ping=1",
			});
		} catch (err) {}
	}

	async function fetchOfferings(q) {
		const url = `../controller/searchAsynchronously.php?action=list&q=${encodeURIComponent(
			q || "",
		)}`;

		const res = await fetch(url, {
			method: "GET",
			headers: { Accept: "application/json" },
		});

		if (!res.ok) return { ok: false, data: [] };

		const json = await res.json();
		return json;
	}

	async function loadOfferings(q) {
		try {
			await syncOfferExpiry();

			const data = await fetchOfferings(q);
			if (data && data.ok) renderOfferings(data.data || []);
			else renderOfferings([]);
		} catch (err) {
			renderOfferings([]);
		}
	}

	function handleInput() {
		const q = (searchInput.value || "").trim();

		if (q === lastQuery) return;
		lastQuery = q;

		if (debounceTimer) clearTimeout(debounceTimer);

		debounceTimer = setTimeout(() => {
			if (q.length === 0) {
				grid.innerHTML = "";
				emptyState.style.display = "block";
				emptyState.textContent = "Start typing to search offerings...";
				return;
			}
			loadOfferings(q);
		}, 240);
	}

	if (searchPill) searchPill.addEventListener("click", openSearch);
	if (searchTrigger) searchTrigger.addEventListener("click", openSearch);

	if (closeSearchBtn) closeSearchBtn.addEventListener("click", closeSearch);

	if (clearBtn) {
		clearBtn.addEventListener("click", function () {
			searchInput.value = "";
			lastQuery = "";
			grid.innerHTML = "";
			emptyState.style.display = "block";
			emptyState.textContent = "Start typing to search offerings...";
			searchInput.focus();
		});
	}

	searchOverlay.addEventListener("click", function (e) {
		if (e.target === searchOverlay) closeSearch();
	});

	document.addEventListener("keydown", function (e) {
		if (e.key === "Escape" && searchOverlay.classList.contains("active")) {
			closeSearch();
		}
	});

	searchInput.addEventListener("input", handleInput);

	setInterval(() => {
		const tiles = grid.querySelectorAll(".offering-tile");
		tiles.forEach((t) => {
			if (typeof t._tick === "function") t._tick();
		});
	}, 1000);
});
