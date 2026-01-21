function togglePaymentFields(role) {
	var methodEl = document.getElementById(role + "_payment_method");
	if (!methodEl) return;

	var method = methodEl.value;

	var phoneRow = document.getElementById(role + "PayPhoneRow");
	var emailRow = document.getElementById(role + "PayEmailRow");
	var cardRow = document.getElementById(role + "PayCardRow");

	if (!phoneRow || !emailRow || !cardRow) return;

	phoneRow.style.display = "none";
	emailRow.style.display = "none";
	cardRow.style.display = "none";

	if (method === "bkash" || method === "nagad" || method === "rocket") {
		phoneRow.style.display = "table-row";
	} else if (method === "paypal") {
		emailRow.style.display = "table-row";
	} else if (method === "credit_card" || method === "debit_card") {
		cardRow.style.display = "table-row";
	}
}
