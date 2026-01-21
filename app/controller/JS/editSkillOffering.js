document.addEventListener("DOMContentLoaded", () => {
	const grid = document.getElementById("offeringsGrid");
	const emptyState = document.getElementById("emptyState");
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

	function safeImg(path) {
		if (!path || path.trim() === "") {
			return "../../public/assets/preloads/Edit_Skill_Offerings.png";
		}
		return path;
	}

	async function syncOfferExpiry() {
		try {
			await fetch("../Controller/updateOfferExpiry.php", {
				method: "POST",
				headers: { "Content-Type": "application/x-www-form-urlencoded" },
				body: "ping=1",
			});
		} catch (err) {}
	}

	function createOfferingTile(o) {
		const tile = document.createElement("div");
		tile.className = "offering-tile";

		const squareBtn = document.createElement("button");
		squareBtn.type = "button";
		squareBtn.className = "offering-square";

		const img = document.createElement("img");
		img.className = "offering-img";
		img.src = safeImg(o.offering_picture_path);
		img.alt = "Offering";

		img.onerror = () => {
			img.src = "../../public/assets/preloads/Edit_Skill_Offerings.png";
		};

		squareBtn.appendChild(img);

		const name = document.createElement("div");
		name.className = "offering-title";
		name.textContent = o.skill_title || "Untitled Offering";

		async function handleSelect() {
			try {
				const fd = new FormData();
				fd.append("action", "select");
				fd.append("offering_id", o.offering_id);

				const res = await fetch(
					"../Controller/editSkillOfferingController.php",
					{
						method: "POST",
						body: fd,
					},
				);

				const data = await res.json();

				if (data.ok) {
					showToast("Opening editor...", true);
					setTimeout(() => {
						window.location.href = "editSkillPanel.php";
					}, 650);
				} else {
					showToast(data.message || "Failed to select offering", false);
				}
			} catch (err) {
				showToast("Something went wrong", false);
			}
		}

		// Click both square + title
		squareBtn.addEventListener("click", handleSelect);
		name.addEventListener("click", handleSelect);

		tile.appendChild(squareBtn);
		tile.appendChild(name);

		return tile;
	}

	function renderOfferings(list) {
		grid.innerHTML = "";

		if (!list || list.length === 0) {
			emptyState.style.display = "block";
			return;
		}

		emptyState.style.display = "none";

		list.forEach((o) => {
			grid.appendChild(createOfferingTile(o));
		});
	}

	async function loadOfferings() {
		try {
			await syncOfferExpiry();

			const res = await fetch(
				"../Controller/editSkillOfferingController.php?action=list",
			);
			const data = await res.json();
			renderOfferings(data.data || []);
		} catch (err) {
			renderOfferings([]);
		}
	}

	loadOfferings();

	setInterval(() => {
		loadOfferings();
	}, 30000);
});
