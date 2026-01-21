let ALL_BROWSE_OFFERINGS = [];
let CURRENT_MODE = "your";

function showBrowseEmpty(show) {
	const empty = document.getElementById("browseEmptyState");
	if (!empty) return;
	empty.style.display = show ? "block" : "none";
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

	if (h > 0) return `${h}h ${m}m left`;
	return `${m}m left`;
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

function createBrowseTile(o) {
	const tile = document.createElement("div");
	tile.className = "offering-tile offer-item";

	const expiresAt = getExpiresAt(o);

	const square = document.createElement("div");
	square.className = "offering-square";
	square.addEventListener("click", () => {
		window.location.href =
			"learnerOfferPanel.php?offering_id=" + encodeURIComponent(o.offering_id);
	});

	const img = document.createElement("img");
	img.className = "offering-img";
	img.alt = "Offering";

	img.src = safeImg(o.offering_picture_path);

	img.onerror = function () {
		this.src = "../../public/assets/preloads/logo.png";
	};

	square.appendChild(img);

	const title = document.createElement("div");
	title.className = "offering-title";
	title.textContent = o.skill_title || "Untitled Offering";

	const sub = document.createElement("div");
	sub.className = "offering-sub";
	sub.textContent = "Calculating time...";

	tile.appendChild(square);
	tile.appendChild(title);
	tile.appendChild(sub);

	function tick() {
		if (!expiresAt) {
			sub.textContent = "Time not available";
			return;
		}
		const ms = expiresAt.getTime() - new Date().getTime();
		sub.textContent = formatRemaining(ms);
	}

	tick();
	tile._tick = tick;

	return tile;
}

function renderBrowseOfferings(list) {
	const grid = document.getElementById("browseOfferingsGrid");
	if (!grid) return;

	grid.innerHTML = "";

	if (!list || list.length === 0) {
		showBrowseEmpty(true);
		return;
	}

	showBrowseEmpty(false);

	list.forEach((o) => {
		grid.appendChild(createBrowseTile(o));
	});
}

async function loadBrowseOfferings(mode) {
	CURRENT_MODE = mode;

	const url =
		"../controller/learnerBrowseController.php?mode=" +
		encodeURIComponent(mode || "your");

	try {
		const res = await fetch(url);
		const data = await res.json();

		if (!data || !data.ok) {
			ALL_BROWSE_OFFERINGS = [];
			renderBrowseOfferings([]);
			return;
		}

		ALL_BROWSE_OFFERINGS = data.offerings || [];
		renderBrowseOfferings(ALL_BROWSE_OFFERINGS);
	} catch (e) {
		ALL_BROWSE_OFFERINGS = [];
		renderBrowseOfferings([]);
	}
}

function setActive(btn1, btn2) {
	if (btn1) btn1.classList.add("active");
	if (btn2) btn2.classList.remove("active");
}

document.addEventListener("DOMContentLoaded", () => {
	const btnYour = document.getElementById("filterYourBtn");
	const btnAll = document.getElementById("filterAllBtn");

	if (btnYour) {
		btnYour.addEventListener("click", () => {
			setActive(btnYour, btnAll);
			loadBrowseOfferings("your");
		});
	}

	if (btnAll) {
		btnAll.addEventListener("click", () => {
			setActive(btnAll, btnYour);
			loadBrowseOfferings("all");
		});
	}

	const hasMy = window.__LEARNER_BROWSE__
		? window.__LEARNER_BROWSE__.hasMyCategories
		: false;

	if (hasMy) {
		setActive(btnYour, btnAll);
		loadBrowseOfferings("your");
	} else {
		setActive(btnAll, btnYour);
		loadBrowseOfferings("all");
	}

	setInterval(() => {
		const tiles = document.querySelectorAll("#browseOfferingsGrid .offer-item");
		tiles.forEach((t) => {
			if (typeof t._tick === "function") t._tick();
		});
	}, 1000);
});
