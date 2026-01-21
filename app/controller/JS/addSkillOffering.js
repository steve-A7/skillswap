(function () {
	const form = document.getElementById("addOfferForm");
	const clientMsg = document.getElementById("clientMsg");

	function showToast(msg, type) {
		if (!clientMsg) return;
		clientMsg.className =
			"toast show " + (type === "error" ? "toast-error" : "toast-success");
		clientMsg.style.display = "block";
		clientMsg.textContent = msg;

		setTimeout(() => {
			clientMsg.classList.remove("show");
			clientMsg.style.display = "none";
		}, 2600);
	}

	const codeInput = document.getElementById("skill_code");
	if (codeInput) {
		codeInput.addEventListener("input", () => {
			codeInput.value = codeInput.value.toUpperCase().replace(/\s+/g, "");
		});
	}

	const priceInput = document.getElementById("price");
	const minPrice = priceInput ? parseInt(priceInput.min || "0", 10) : 0;
	const maxPrice = priceInput
		? parseInt(priceInput.max || "999999", 10)
		: 999999;

	if (form) {
		form.addEventListener("submit", (e) => {
			const title = (
				document.getElementById("skill_title")?.value || ""
			).trim();
			const code = (document.getElementById("skill_code")?.value || "").trim();
			const cat = (document.getElementById("category_id")?.value || "").trim();
			const diff = (document.getElementById("difficulty")?.value || "").trim();
			const offeredFor = parseInt(
				document.getElementById("offered_for")?.value || "0",
				10,
			);
			const price = parseFloat(priceInput?.value || "0");
			const slots = (document.getElementById("time_slots")?.value || "").trim();
			const duration = parseInt(
				document.getElementById("duration_minutes")?.value || "0",
				10,
			);

			if (!title || !code || !cat || !diff) {
				e.preventDefault();
				showToast("Fill all required fields", "error");
				return;
			}

			if (!Number.isFinite(price) || price < minPrice || price > maxPrice) {
				e.preventDefault();
				showToast(
					"Price must be between " + minPrice + " and " + maxPrice,
					"error",
				);
				return;
			}

			if (!Number.isInteger(offeredFor) || offeredFor < 1) {
				e.preventDefault();
				showToast("Offered For must be at least 1 hour", "error");
				return;
			}

			if (!slots) {
				e.preventDefault();
				showToast("Please add at least 1 time slot", "error");
				return;
			}

			if (!Number.isInteger(duration) || duration < 15) {
				e.preventDefault();
				showToast("Session duration must be at least 15 minutes", "error");
				return;
			}

			const picInput = document.getElementById("offering_picture");
			const file = picInput && picInput.files ? picInput.files[0] : null;
			if (!file) {
				e.preventDefault();
				showToast("Offering picture is required", "error");
				return;
			}

			const name = (file.name || "").toLowerCase();
			const ext = name.split(".").pop();
			const okExt = ["png", "jpg", "jpeg"].includes(ext);
			if (!okExt) {
				e.preventDefault();
				showToast("Only PNG / JPG / JPEG images are allowed", "error");
				return;
			}
		});
	}
})();
