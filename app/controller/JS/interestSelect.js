function toggleSkillTag(cb) {
	if (!cb) return;

	var label = cb.closest("label");
	if (!label) return;

	if (cb.checked) {
		label.classList.add("selected");
		label.setAttribute("aria-pressed", "true");
	} else {
		label.classList.remove("selected");
		label.setAttribute("aria-pressed", "false");
	}
}

window.onload = function () {
	if (!document.getElementById("categoryTagStyle")) {
		var style = document.createElement("style");
		style.id = "categoryTagStyle";
		style.textContent = `
			label.skillTag{
				display:inline-flex;
				align-items:center;
				gap:8px;
				padding:6px 12px;
				border:1px solid rgba(0,0,0,0.18);
				border-radius:999px;
				cursor:pointer;
				user-select:none;
				margin:4px 6px 4px 0;
			}
			label.skillTag.selected{
				background: rgba(0,0,0,0.08);
				border-color: rgba(0,0,0,0.45);
			}
		`;
		document.head.appendChild(style);
	}

	var list = document.querySelectorAll(
		'#mentorCategoriesBox input[type="checkbox"], #learnerCategoriesBox input[type="checkbox"]',
	);

	for (var i = 0; i < list.length; i++) {
		toggleSkillTag(list[i]);

		list[i].addEventListener("change", function () {
			toggleSkillTag(this);
		});
	}
};
