document.addEventListener("DOMContentLoaded", () => {
	const body = document.body;

	const editBtn = document.getElementById("editBtn");
	const cancelBtn = document.getElementById("cancelBtn");
	const saveBtn = document.getElementById("saveBtn");

	const serverMsg = document.getElementById("serverMsg");
	const clientMsg = document.getElementById("clientMsg");

	const lockables = document.querySelectorAll(".lockable");
	const form = document.getElementById("editOfferForm");

	const original = {};

	function showClientToast(msg, type = "success") {
		if (!clientMsg) return;

		clientMsg.style.display = "block";
		clientMsg.textContent = msg;

		clientMsg.classList.remove("toast-success", "toast-error", "show");
		clientMsg.classList.add(type === "error" ? "toast-error" : "toast-success");
		clientMsg.classList.add("show");

		setTimeout(() => {
			clientMsg.classList.remove("show");
			setTimeout(() => {
				clientMsg.style.display = "none";
			}, 400);
		}, 2800);
	}

	function setMode(editMode) {
		if (editMode) {
			body.classList.remove("view-mode");
			body.classList.add("edit-mode");

			lockables.forEach((el) => (el.disabled = false));
			saveBtn.style.display = "inline-block";
			cancelBtn.style.display = "inline-block";
			editBtn.textContent = "Editing...";
			editBtn.disabled = true;
		} else {
			body.classList.remove("edit-mode");
			body.classList.add("view-mode");

			lockables.forEach((el) => (el.disabled = true));
			saveBtn.style.display = "none";
			cancelBtn.style.display = "none";
			editBtn.textContent = "Edit";
			editBtn.disabled = false;
		}
	}

	function storeOriginal() {
		lockables.forEach((el) => {
			if (el.type === "file") return;
			original[el.id] = el.value;
		});
	}

	function restoreOriginal() {
		lockables.forEach((el) => {
			if (el.type === "file") {
				el.value = "";
				return;
			}
			if (original.hasOwnProperty(el.id)) {
				el.value = original[el.id];
			}
		});
	}

	storeOriginal();
	setMode(false);

	lockables.forEach((el) => {
		el.addEventListener("mousedown", (e) => {
			if (body.classList.contains("view-mode")) {
				e.preventDefault();
				showClientToast("Please enter edit mode to edit.", "error");
			}
		});
	});

	editBtn.addEventListener("click", () => {
		storeOriginal();
		setMode(true);
		showClientToast("Edit mode enabled", "success");
	});

	cancelBtn.addEventListener("click", () => {
		restoreOriginal();
		setMode(false);
		showClientToast("Changes cancelled", "error");
	});

	form.addEventListener("submit", () => {});
});
