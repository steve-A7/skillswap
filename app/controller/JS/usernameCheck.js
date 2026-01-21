function checkUsername(username) {
	var statusEl = document.getElementById("usernameStatus");
	if (!statusEl) {
		return;
	}

	if (!username || username.trim().length == 0) {
		statusEl.innerHTML = "";
		return;
	}

	var xhr = new XMLHttpRequest();
	xhr.open(
		"GET",
		"..\\..\\app\\controller\\checkUsername.php?username=" +
			encodeURIComponent(username),
		true
	);

	xhr.onreadystatechange = function () {
		if (xhr.readyState === 4) {
			try {
				var res = JSON.parse(xhr.responseText);
				statusEl.innerHTML = res.message || "";
			} catch (e) {
				statusEl.innerHTML = "";
			}
		}
	};

	xhr.send();
}
