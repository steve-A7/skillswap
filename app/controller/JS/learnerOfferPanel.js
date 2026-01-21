let LOP_DATA = null;

function byId(id) {
	return document.getElementById(id);
}

function showToast(msg, ok = false) {
	const t = byId("lopToast");
	if (!t) return;

	t.style.display = "block";
	t.classList.remove("toast-ok", "toast-bad");
	t.classList.add(ok ? "toast-ok" : "toast-bad");
	t.textContent = msg || "";

	setTimeout(() => {
		t.style.display = "none";
	}, 1600);
}

function safeImg(path) {
	if (!path || String(path).trim() === "") {
		return "../../public/assets/preloads/logo.png";
	}
	return path;
}

function formatSlotLabel(s) {
	const d = (s.day_of_week || "").toUpperCase();
	return `${d} | ${s.start_time} - ${s.end_time}`;
}

function setExpanded(open) {
	const el = byId("lopExpand");
	if (!el) return;
	if (open) el.classList.add("open");
	else el.classList.remove("open");
}

function clearManualFields() {
	if (byId("lopPhoneInput")) byId("lopPhoneInput").value = "";
	if (byId("lopEmailInput")) byId("lopEmailInput").value = "";
	if (byId("lopCardInput")) byId("lopCardInput").value = "";
	if (byId("lopCardHint")) byId("lopCardHint").textContent = "";

	if (byId("lopHiddenManualPay")) byId("lopHiddenManualPay").value = "";
	if (byId("lopHiddenManualLast4")) byId("lopHiddenManualLast4").value = "";
}

function hideAllManualRows() {
	if (byId("lopPhoneRow")) byId("lopPhoneRow").style.display = "none";
	if (byId("lopEmailRow")) byId("lopEmailRow").style.display = "none";
	if (byId("lopCardRow")) byId("lopCardRow").style.display = "none";
}

function hasSavedInfoForMethod(method, pay) {
	if (!pay) return false;

	if (method === "paypal") return !!pay.paypal_email;
	if (method === "bkash") return !!pay.bkash_number;
	if (method === "nagad") return !!pay.nagad_number;
	if (method === "credit_card" || method === "debit_card")
		return !!pay.card_last4;

	return false;
}

function renderSavedPaymentInfo() {
	const box = byId("lopSavedPayInfo");
	if (!box) return;

	box.textContent = "";

	if (!LOP_DATA || !LOP_DATA.learnerPayment) return;

	const pay = LOP_DATA.learnerPayment;
	const selected = byId("lopPaySelect") ? byId("lopPaySelect").value : "";
	const saved = pay.preferred_payment_method || "";

	if (!selected) return;

	if (selected !== saved) return;
	if (!hasSavedInfoForMethod(selected, pay)) return;

	if (selected === "paypal")
		box.textContent = "Saved PayPal: " + pay.paypal_email;
	if (selected === "bkash")
		box.textContent = "Saved bKash: " + pay.bkash_number;
	if (selected === "nagad")
		box.textContent = "Saved Nagad: " + pay.nagad_number;
	if (selected === "credit_card" || selected === "debit_card")
		box.textContent = "Saved Card: ****" + pay.card_last4;
}

function updateManualPaymentUI() {
	const wrap = byId("lopManualPay");
	if (!wrap) return;

	const selected = byId("lopPaySelect") ? byId("lopPaySelect").value : "";
	const pay = LOP_DATA ? LOP_DATA.learnerPayment : null;
	const saved = pay ? pay.preferred_payment_method || "" : "";

	hideAllManualRows();
	clearManualFields();

	wrap.style.display = "none";

	if (!selected) {
		renderSavedPaymentInfo();
		return;
	}

	const usingSaved = selected === saved && hasSavedInfoForMethod(selected, pay);

	if (usingSaved) {
		renderSavedPaymentInfo();
		return;
	}

	wrap.style.display = "block";
	byId("lopSavedPayInfo").textContent = "";

	if (selected === "bkash") {
		byId("lopPhoneRow").style.display = "block";
		byId("lopPhoneLabel").textContent = "Enter bKash Number";
		return;
	}

	if (selected === "nagad") {
		byId("lopPhoneRow").style.display = "block";
		byId("lopPhoneLabel").textContent = "Enter Nagad Number";
		return;
	}

	if (selected === "paypal") {
		byId("lopEmailRow").style.display = "block";
		return;
	}

	if (selected === "credit_card" || selected === "debit_card") {
		byId("lopCardRow").style.display = "block";
		return;
	}
}

function getDigitsOnly(str) {
	return String(str || "").replace(/\D/g, "");
}

function updateCardHint() {
	const card = byId("lopCardInput");
	const hint = byId("lopCardHint");
	if (!card || !hint) return;

	const digits = getDigitsOnly(card.value);
	if (!digits) {
		hint.textContent = "";
		return;
	}

	if (digits.length < 4) {
		hint.textContent = "Enter at least last 4 digits.";
		return;
	}

	hint.textContent = "Last 4 digits: ****" + digits.slice(-4);
}

function renderSlots(slots) {
	const sel = byId("lopSlotSelect");
	const hint = byId("lopSlotHint");
	if (!sel) return;

	sel.innerHTML = `<option value="">Select a time slot</option>`;

	if (!slots || slots.length === 0) {
		if (hint) hint.textContent = "No time slots available for this offering.";
		return;
	}

	if (hint) hint.textContent = "";

	slots.forEach((s) => {
		const opt = document.createElement("option");
		opt.value = s.slot_id;
		opt.textContent = formatSlotLabel(s);
		sel.appendChild(opt);
	});
}

function applyOffering(offering, durations) {
	byId("lopOfferImg").src = safeImg(offering.offering_picture_path);
	byId("lopOfferImg").onerror = function () {
		this.src = "../../public/assets/preloads/logo.png";
	};

	byId("lopSkillTitle").textContent = offering.skill_title || "-";
	byId("lopSkillCode").textContent = offering.skill_code || "-";
	byId("lopSkillCategory").textContent = offering.category_name || "-";
	byId("lopPrice").textContent = offering.price || "0";
	byId("lopDesc").textContent = offering.description || "No description added.";

	let durText = "Not set";
	if (durations && durations.length > 0) {
		durText = `${durations[0].duration_minutes} minutes`;
	}
	byId("lopDuration").textContent = durText;

	byId("lopMentorImg").src = safeImg(offering.mentor_picture_path);
	byId("lopMentorImg").onerror = function () {
		this.src = "../../public/assets/preloads/logo.png";
	};

	byId("lopMentorName").textContent = offering.mentor_username || "Mentor";
}

async function syncExpiryOnce() {
	try {
		await fetch("../Controller/updateOfferExpiry.php", {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded" },
			body: "ping=1",
		});
	} catch (e) {}
}

async function loadPanel(offeringId) {
	const loading = byId("lopLoading");
	const wrap = byId("lopWrap");
	const err = byId("lopError");

	try {
		if (loading) loading.style.display = "block";
		if (wrap) wrap.style.display = "none";
		if (err) err.style.display = "none";

		await syncExpiryOnce();

		const url =
			"../controller/learnerOfferPanelController.php?offering_id=" +
			encodeURIComponent(offeringId);

		const res = await fetch(url);
		const data = await res.json();

		if (!data || !data.ok) {
			if (loading) loading.style.display = "none";
			if (wrap) wrap.style.display = "none";
			if (err) {
				err.style.display = "block";
				err.textContent =
					data && data.msg ? data.msg : "Failed to load offering.";
			}
			return;
		}

		LOP_DATA = data;

		if (loading) loading.style.display = "none";
		if (wrap) wrap.style.display = "block";
		if (err) err.style.display = "none";

		applyOffering(data.offering, data.durations || []);
		renderSlots(data.slots || []);

		const paySel = byId("lopPaySelect");
		if (
			paySel &&
			data.learnerPayment &&
			data.learnerPayment.preferred_payment_method
		) {
			paySel.value = data.learnerPayment.preferred_payment_method;
		}

		const meetSel = byId("lopMeetSelect");
		if (meetSel) {
			const pref =
				data.learnerPayment && data.learnerPayment.preferred_way_to_learn
					? data.learnerPayment.preferred_way_to_learn
					: "both";
			meetSel.value = pref || "both";
		}

		renderSavedPaymentInfo();
		updateManualPaymentUI();
	} catch (e) {
		if (loading) loading.style.display = "none";
		if (wrap) wrap.style.display = "none";
		if (err) {
			err.style.display = "block";
			err.textContent = "Failed to load offering.";
		}
	}
}

document.addEventListener("DOMContentLoaded", () => {
	const offeringId = window.__LEARNER_OFFER_PANEL__
		? window.__LEARNER_OFFER_PANEL__.offeringId
		: 0;

	if (!offeringId) {
		showToast("Invalid offering", false);
		return;
	}

	loadPanel(offeringId);

	const buyBtn = byId("lopBuyBtn");
	if (buyBtn) {
		buyBtn.addEventListener("click", () => {
			const exp = byId("lopExpand");
			const isOpen = exp && exp.classList.contains("open");
			setExpanded(!isOpen);
		});
	}

	const paySel = byId("lopPaySelect");
	if (paySel) {
		paySel.addEventListener("change", () => {
			renderSavedPaymentInfo();
			updateManualPaymentUI();
		});
	}

	const cardInput = byId("lopCardInput");
	if (cardInput) {
		cardInput.addEventListener("input", () => {
			updateCardHint();
		});
	}

	const form = byId("lopConfirmForm");
	if (form) {
		form.addEventListener("submit", (e) => {
			const slot = byId("lopSlotSelect").value;
			const pay = byId("lopPaySelect").value;
			const meet = byId("lopMeetSelect") ? byId("lopMeetSelect").value : "";

			if (!slot) {
				e.preventDefault();
				showToast("Please choose a time slot.", false);
				return;
			}
			if (!pay) {
				e.preventDefault();
				showToast("Please select a payment method.", false);
				return;
			}

			if (!meet) {
				e.preventDefault();
				showToast("Please select a meeting type.", false);
				return;
			}

			byId("lopHiddenSlotId").value = slot;
			byId("lopHiddenPayment").value = pay;
			byId("lopHiddenMeeting").value = meet;

			const lp = LOP_DATA ? LOP_DATA.learnerPayment : null;
			const saved = lp ? lp.preferred_payment_method || "" : "";
			const usingSaved = pay === saved && hasSavedInfoForMethod(pay, lp);

			if (usingSaved) {
				byId("lopHiddenManualPay").value = "";
				byId("lopHiddenManualLast4").value = "";
				return;
			}

			if (pay === "bkash" || pay === "nagad") {
				const phone = byId("lopPhoneInput").value.trim();
				if (!phone) {
					e.preventDefault();
					showToast("Enter your number to confirm purchase.", false);
					return;
				}
				byId("lopHiddenManualPay").value = phone;
				byId("lopHiddenManualLast4").value = "";
				return;
			}

			if (pay === "paypal") {
				const email = byId("lopEmailInput").value.trim();
				if (!email) {
					e.preventDefault();
					showToast("Enter your PayPal email to confirm purchase.", false);
					return;
				}
				byId("lopHiddenManualPay").value = email;
				byId("lopHiddenManualLast4").value = "";
				return;
			}

			if (pay === "credit_card" || pay === "debit_card") {
				const card = byId("lopCardInput").value.trim();
				const digits = getDigitsOnly(card);

				if (!digits || digits.length < 4) {
					e.preventDefault();
					showToast("Enter your card number (at least last 4 digits).", false);
					return;
				}

				byId("lopHiddenManualPay").value = "card";
				byId("lopHiddenManualLast4").value = digits.slice(-4);
				return;
			}
		});
	}
});
