function checkPasswordMatch() {
	var passEl = document.getElementById("password");
	var confirmEl = document.getElementById("confirm_password");
	var statusEl = document.getElementById("passwordMatchStatus");

	if (!passEl || !confirmEl || !statusEl) return;

	var p1 = passEl.value || "";
	var p2 = confirmEl.value || "";

	if (p2.length == 0) {
		statusEl.innerHTML = "";
		return;
	}

	if (p1 === p2) {
		statusEl.innerHTML = "Matched";
	} else {
		statusEl.innerHTML = "Passwords do not match";
	}
}
