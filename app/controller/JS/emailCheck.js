function checkEmail(email) {
	var statusEl = document.getElementById("emailStatus");
	if (!statusEl) {
		return;
	}

	if (!email || email.trim().length == 0) {
		statusEl.innerHTML = "";
		return;
	}

	var xhr = new XMLHttpRequest();
	xhr.open(
		"GET",
		"..\\..\\app\\controller\\checkEmail.php?email=" +
			encodeURIComponent(email),
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
