function toggleRoleSections(role) {
	var mentor = document.getElementById("mentorSection");
	var learner = document.getElementById("learnerSection");

	if (!mentor || !learner) {
		return;
	}

	if (role === "learner") {
		mentor.style.display = "none";
		learner.style.display = "table-row-group";
	} else {
		learner.style.display = "none";
		mentor.style.display = "table-row-group";
	}
}
