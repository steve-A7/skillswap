document.addEventListener("DOMContentLoaded", function () {
    var editing = false;

    var form = document.getElementById("editMentorForm");
    var editBtn = document.getElementById("editBtn");
    var saveBtn = document.getElementById("saveBtn");
    var deleteBtn = document.getElementById("deleteBtn");
    var deleteForm = document.getElementById("deleteForm");

    var clientMsg = document.getElementById("clientMsg");
    var bottomMsg = document.getElementById("bottomMsg");

    var newPass = document.getElementById("new_password");
    var confirmPass = document.getElementById("confirm_password");
    var confirmRow = document.getElementById("confirmRow");
    var retypeHint = document.getElementById("retypeHint");

    var fileInput = document.getElementById("profile_picture");

    var initialState = null;

    function setMode(isEdit) {
        document.body.classList.remove("view-mode", "edit-mode");
        document.body.classList.add(isEdit ? "edit-mode" : "view-mode");
    }

    function showTopToast(message, type) {
        if (!clientMsg) return;

        clientMsg.style.display = "block";
        clientMsg.textContent = message;

        clientMsg.classList.remove("toast-success", "toast-error", "show");
        clientMsg.classList.add(type === "error" ? "toast-error" : "toast-success");

        void clientMsg.offsetWidth;
        clientMsg.classList.add("show");

        setTimeout(function () {
            clientMsg.style.display = "none";
            clientMsg.textContent = "";
            clientMsg.classList.remove("show");
        }, 3600);
    }

    function showBottomStatus(message, type) {
        if (!bottomMsg) return;

        bottomMsg.style.display = "block";
        bottomMsg.textContent = message;

        bottomMsg.classList.remove("status-success", "status-error", "show");
        bottomMsg.classList.add(type === "error" ? "status-error" : "status-success");

        void bottomMsg.offsetWidth;
        bottomMsg.classList.add("show");

        setTimeout(function () {
            bottomMsg.style.display = "none";
            bottomMsg.textContent = "";
            bottomMsg.classList.remove("show");
        }, 4200);
    }

    function fieldError(el) {
        if (!el) return;
        el.classList.add("field-error");
        setTimeout(function () {
            el.classList.remove("field-error");
        }, 1600);
    }

    function snapshotState() {
        var snap = {
            values: {},
            checks: {}
        };

        var inputs = document.querySelectorAll("#editMentorForm input");
        for (var i = 0; i < inputs.length; i++) {
            var el = inputs[i];
            if (el.type === "file") continue;

            if (el.type === "checkbox") {
                snap.checks[el.name + "::" + el.value] = el.checked;
            } else {
                snap.values[el.id || el.name || ("input_" + i)] = el.value;
            }
        }

        var textareas = document.querySelectorAll("#editMentorForm textarea");
        for (var t = 0; t < textareas.length; t++) {
            var ta = textareas[t];
            snap.values[ta.id || ta.name || ("ta_" + t)] = ta.value;
        }

        var selects = document.querySelectorAll("#editMentorForm select");
        for (var s = 0; s < selects.length; s++) {
            var se = selects[s];
            snap.values[se.id || se.name || ("sel_" + s)] = se.value;
        }

        return snap;
    }

    function restoreState(snap) {
        if (!snap) return;

        var inputs = document.querySelectorAll("#editMentorForm input");
        for (var i = 0; i < inputs.length; i++) {
            var el = inputs[i];
            if (el.type === "file") continue;

            if (el.type === "checkbox") {
                var ck = el.name + "::" + el.value;
                if (snap.checks.hasOwnProperty(ck)) {
                    el.checked = snap.checks[ck];
                }
            } else {
                var key = el.id || el.name || ("input_" + i);
                if (snap.values.hasOwnProperty(key)) {
                    el.value = snap.values[key];
                }
            }
        }

        var textareas = document.querySelectorAll("#editMentorForm textarea");
        for (var t = 0; t < textareas.length; t++) {
            var ta = textareas[t];
            var kta = ta.id || ta.name || ("ta_" + t);
            if (snap.values.hasOwnProperty(kta)) {
                ta.value = snap.values[kta];
            }
        }

        var selects = document.querySelectorAll("#editMentorForm select");
        for (var s = 0; s < selects.length; s++) {
            var se = selects[s];
            var kse = se.id || se.name || ("sel_" + s);
            if (snap.values.hasOwnProperty(kse)) {
                se.value = snap.values[kse];
            }
        }

        if (fileInput) fileInput.value = "";

        updateCategoryPills();
    }

    function updateCategoryPills() {
        var pills = document.querySelectorAll(".cat-pill");
        for (var i = 0; i < pills.length; i++) {
            var label = pills[i];
            var chk = label.querySelector("input[type='checkbox']");
            if (!chk) continue;
            if (chk.checked) label.classList.add("checked");
            else label.classList.remove("checked");
        }
    }

    var catBox = document.getElementById("mentorCategoriesBox");
    if (catBox) {
        catBox.addEventListener("change", function (e) {
            updateCategoryPills();
        });
    }

    function hidePasswordRetype() {
        if (confirmRow) confirmRow.style.display = "none";
        if (confirmPass) {
            confirmPass.value = "";
            confirmPass.disabled = true;
            confirmPass.readOnly = true;
        }
        if (retypeHint) retypeHint.innerText = "";
    }

    function toggleConfirmRow() {
        if (!editing) return;

        var val = newPass && newPass.value ? newPass.value.trim() : "";
        if (val.length > 0) {
            if (confirmRow) confirmRow.style.display = "";
            if (confirmPass) {
                confirmPass.disabled = false;
                confirmPass.readOnly = false;
            }
            if (retypeHint) retypeHint.innerText = "Retype the new password";
        } else {
            hidePasswordRetype();
        }
    }

    function checkMatch() {
        if (!editing) return;
        if (!retypeHint) return;

        var a = newPass && newPass.value ? newPass.value : "";
        var b = confirmPass && confirmPass.value ? confirmPass.value : "";

        if (a.trim().length === 0) {
            retypeHint.innerText = "";
            return;
        }

        if (b.length === 0) {
            retypeHint.innerText = "Retype the new password";
            return;
        }

        if (a === b) {
            retypeHint.innerText = "Password matched";
        } else {
            retypeHint.innerText = "Password does not match";
        }
    }

    if (newPass) {
        newPass.addEventListener("input", function () {
            toggleConfirmRow();
            checkMatch();
        });
    }

    if (confirmPass) {
        confirmPass.addEventListener("input", function () {
            checkMatch();
        });
    }

    function setEditing(on) {
        editing = on;

        setMode(on);

        if (editBtn) editBtn.innerText = on ? "Cancel" : "Edit";
        if (saveBtn) saveBtn.style.display = on ? "inline-block" : "none";

        var els = document.querySelectorAll("#editMentorForm input, #editMentorForm textarea");
        for (var i = 0; i < els.length; i++) {
            var el = els[i];

            if (el.id === "username" || el.id === "email") {
                el.readOnly = true;
                el.disabled = false;
                continue;
            }

            if (el.id === "password_mask") {
                el.disabled = true;
                continue;
            }

            if (el.type === "file") {
                el.disabled = !on;
                continue;
            }

            if (el.type === "checkbox") {
                el.disabled = !on;
                continue;
            }

            if (el.id === "new_password") {
                el.disabled = !on;
                el.readOnly = !on;
                if (!on) el.value = "";
                continue;
            }

            if (el.id === "confirm_password") {
                el.disabled = true;
                el.readOnly = true;
                if (!on) el.value = "";
                continue;
            }

            el.disabled = false;
            el.readOnly = !on;
        }

        var selects = document.querySelectorAll("#editMentorForm select");
        for (var s = 0; s < selects.length; s++) {
            selects[s].disabled = !on;
        }

  
        if (!on) {
            hidePasswordRetype();
        }

        updateCategoryPills();
    }

    if (editBtn) {
        editBtn.addEventListener("click", function () {
            if (!editing) {
                initialState = snapshotState();
                setEditing(true);
                showTopToast("Edit mode enabled", "success");
            } else {
                restoreState(initialState);
                setEditing(false);
                showTopToast("Edit cancelled", "error");
            }
        });
    }


    if (deleteBtn) {
        deleteBtn.addEventListener("click", function () {
            var ok = confirm("Are you sure you want to delete your account? This action cannot be undone.");
            if (!ok) return;

            if (!deleteForm) {
                showTopToast("Delete form missing", "error");
                return;
            }

            deleteForm.submit();
        });
    }

    var guard = document.querySelectorAll("#editMentorForm input, #editMentorForm textarea, #editMentorForm select");
    for (var g = 0; g < guard.length; g++) {
        guard[g].addEventListener("focus", function () {
            if (editing) return;
            if (this.id === "username" || this.id === "email" || this.id === "password_mask") return;

            showTopToast("Click Edit to modify fields", "error");
            fieldError(this);
        });
    }

    if (form) {
        form.addEventListener("submit", function (e) {
            if (!editing) {
                e.preventDefault();
                showTopToast("Enable Edit mode before saving", "error");
                return;
            }

            var a = newPass && newPass.value ? newPass.value.trim() : "";
            var b = confirmPass && confirmPass.value ? confirmPass.value.trim() : "";

            if (a.length > 0) {
                if (b.length === 0) {
                    e.preventDefault();
                    showTopToast("Please retype your new password", "error");
                    fieldError(confirmPass);
                    return;
                }
                if (a !== b) {
                    e.preventDefault();
                    showTopToast("Passwords do not match", "error");
                    fieldError(confirmPass);
                    return;
                }
            }

            showBottomStatus("Saving changes...", "success");
        });
    }

    setEditing(false);
    updateCategoryPills();
});
