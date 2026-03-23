"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    var modal = document.getElementById("admin-modal");
    var form = document.getElementById("admin-form");
    var errorEl = document.getElementById("admin-error");
    var closeBtn = modal ? modal.querySelector(".modal-close") : null;
    var cancelBtn = document.getElementById("admin-cancel");
    var addBtn = document.getElementById("btn-add-admin");
    var addBtnEmpty = document.getElementById("btn-add-admin-empty");
    var usernameInput = document.getElementById("admin-username");
    function openModal() {
        if (!modal)
            return;
        if (form)
            form.reset();
        if (errorEl) {
            errorEl.hidden = true;
            errorEl.textContent = "";
        }
        modal.classList.add("open");
        if (usernameInput)
            usernameInput.focus();
    }
    function closeModal() {
        if (!modal)
            return;
        modal.classList.remove("open");
    }
    if (addBtn)
        addBtn.addEventListener("click", openModal);
    if (addBtnEmpty)
        addBtnEmpty.addEventListener("click", openModal);
    var closeElements = [closeBtn, cancelBtn];
    closeElements.forEach(function (el) {
        if (!el)
            return;
        el.addEventListener("click", function (e) {
            e.preventDefault();
            closeModal();
        });
    });
    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal)
                closeModal();
        });
    }
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape")
            closeModal();
    });
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            if (errorEl) {
                errorEl.hidden = true;
                errorEl.textContent = "";
            }
            var submitBtn = form.querySelector("button[type='submit']");
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Létrehozás...";
            }
            var formData = new FormData(form);
            fetch("admins.php", {
                method: "POST",
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                if (!data.ok) {
                    if (errorEl) {
                        errorEl.hidden = false;
                        errorEl.textContent = data.error || "Ismeretlen hiba.";
                    }
                    else {
                        alert(data.error || "Ismeretlen hiba.");
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = "Létrehozás";
                    }
                    return;
                }
                window.location.reload();
            })
                .catch(function () {
                if (errorEl) {
                    errorEl.hidden = false;
                    errorEl.textContent = "Hálózati hiba történt.";
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = "Létrehozás";
                }
            });
        });
    }
    document.querySelectorAll(".toggle-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var id = btn.dataset.id;
            if (!id)
                return;
            var current = btn.dataset.visible === "1";
            var next = current ? 0 : 1;
            var formData = new FormData();
            formData.append("action", "toggle_active");
            formData.append("id", id);
            formData.append("is_active", String(next));
            fetch("admins.php", {
                method: "POST",
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                if (!data.ok) {
                    alert(data.error || "Hiba az állapot módosításakor.");
                    return;
                }
                btn.dataset.visible = String(next);
                btn.classList.toggle("active", !!next);
                var tr = btn.closest("tr");
                if (tr)
                    tr.dataset.is_active = String(next);
            })
                .catch(function () { return alert("Hálózati hiba történt."); });
        });
    });
    document.querySelectorAll(".btn-reset-pw").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var tr = btn.closest("tr");
            if (!tr)
                return;
            var id = tr.dataset.id;
            if (!id)
                return;
            var username = tr.dataset.username || id;
            var newPw = window.prompt("Új jelszó beállítása ehhez: " + username);
            if (!newPw)
                return;
            if (newPw.length < 4) {
                alert("A jelszó legyen legalább 4 karakter!");
                return;
            }
            var formData = new FormData();
            formData.append("action", "reset_password");
            formData.append("id", id);
            formData.append("password", newPw);
            fetch("admins.php", {
                method: "POST",
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                if (!data.ok) {
                    alert(data.error || "Hiba a jelszó csere során.");
                    return;
                }
                alert("Jelszó sikeresen módosítva!");
            })
                .catch(function () { return alert("Hálózati hiba a jelszó módosításakor."); });
        });
    });
});
