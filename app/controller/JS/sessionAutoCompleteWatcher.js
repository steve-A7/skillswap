(function () {
	async function autoCompleteSessions() {
		try {
			await fetch(
				"../Controller/mentorSessionsController.php?action=autocomplete",
				{
					method: "GET",
				},
			);
		} catch (e) {}
	}

	autoCompleteSessions();

	setInterval(() => {
		autoCompleteSessions();
	}, 20000);
})();
