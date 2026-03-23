"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    var _a, _b, _c, _d;
    // 1. DOM elemek lekérése típusokkal
    var modal = document.getElementById("news-modal");
    var form = document.getElementById("news-form");
    var titleInput = document.getElementById("news-title");
    var shortTextInput = document.getElementById("news-shorttext");
    var contentInput = document.getElementById("news-content");
    var categoryInput = document.getElementById("news-category");
    var visibleInput = document.getElementById("news-visible");
    var actionInput = document.getElementById("news-action");
    var idInput = document.getElementById("news-id");
    var modalTitle = document.getElementById("news-modal-title");
    var submitBtn = document.getElementById("news-submit-btn");
    var errorText = document.getElementById("news-error");
    // Ha valami nagyon hiányzik, ne fusson le hibára
    if (!modal || !form || !titleInput || !shortTextInput || !contentInput || !categoryInput || !visibleInput || !actionInput || !idInput || !modalTitle || !submitBtn || !errorText) {
        console.warn("A hírek kezelőjének egyes DOM elemei nem találhatóak.");
        return;
    }
    // Modal kezelő függvények
    var openModal = function () {
        errorText.hidden = true;
        modal.classList.add("open");
    };
    var closeModal = function () {
        modal.classList.remove("open");
        form.reset();
    };
    // --- Új hír nyitása ---
    (_a = document.getElementById("btn-add-news")) === null || _a === void 0 ? void 0 : _a.addEventListener("click", function () {
        form.reset();
        actionInput.value = "add";
        idInput.value = "";
        modalTitle.textContent = "Új hír írása";
        submitBtn.textContent = "Közzététel";
        openModal();
    });
    // Ha üres az oldal és arra a gombra nyom
    (_b = document.getElementById("btn-add-news-empty")) === null || _b === void 0 ? void 0 : _b.addEventListener("click", function () {
        var _a;
        (_a = document.getElementById("btn-add-news")) === null || _a === void 0 ? void 0 : _a.click();
    });
    // --- Szerkesztés nyitása ---
    document.querySelectorAll(".btn-edit-news").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            var target = e.currentTarget;
            var row = target.closest("tr");
            if (!row)
                return;
            actionInput.value = "edit";
            idInput.value = row.dataset.id || "";
            titleInput.value = row.dataset.title || "";
            shortTextInput.value = row.dataset.shorttext || ""; // Új mező betöltése
            contentInput.value = row.dataset.content || "";
            categoryInput.value = row.dataset.category || "INFO";
            visibleInput.checked = row.dataset.visible === "1";
            modalTitle.textContent = "Hír szerkesztése";
            submitBtn.textContent = "Mentés";
            openModal();
        });
    });
    // --- Bezárás gombok ---
    (_c = document.querySelector(".modal-close")) === null || _c === void 0 ? void 0 : _c.addEventListener("click", closeModal);
    (_d = document.getElementById("news-cancel")) === null || _d === void 0 ? void 0 : _d.addEventListener("click", closeModal);
    // ESC gombra is záródjon be
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && modal.classList.contains("open")) {
            closeModal();
        }
    });
    // --- Form beküldése (Hozzáadás/Szerkesztés) ---
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        var formData = new FormData(form);
        fetch("/admin/news.php", {
            method: "POST",
            body: formData
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
            if (data.ok) {
                location.reload();
            }
            else {
                errorText.textContent = data.error || "Hiba történt a mentés során.";
                errorText.hidden = false;
            }
        })
            .catch(function () {
            errorText.textContent = "Hálózati hiba történt a kommunikáció során.";
            errorText.hidden = false;
        });
    });
    // --- Láthatóság Toggle (A zöld/szürke csúszka) ---
    document.querySelectorAll(".toggle-visibility").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            var target = e.currentTarget;
            var id = target.dataset.id || "0";
            var currentVisible = target.dataset.visible === "1";
            var newVisible = currentVisible ? 0 : 1;
            var formData = new FormData();
            formData.append("action", "toggle_visible");
            formData.append("id", id);
            formData.append("is_visible", newVisible.toString());
            fetch("/admin/news.php", {
                method: "POST",
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                if (data.ok) {
                    target.dataset.visible = newVisible.toString();
                    target.classList.toggle("active", newVisible === 1);
                }
                else {
                    alert(data.error || "Hiba történt a módosítás során!");
                }
            })
                .catch(console.error);
        });
    });
    // --- Törlés ---
    document.querySelectorAll(".btn-delete-news").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            if (!confirm("Biztosan törölni szeretnéd ezt a hírt? Ezt nem lehet visszavonni!"))
                return;
            var target = e.currentTarget;
            var id = target.dataset.id || "0";
            var formData = new FormData();
            formData.append("action", "delete");
            formData.append("id", id);
            fetch("/admin/news.php", {
                method: "POST",
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                if (data.ok) {
                    location.reload();
                }
                else {
                    alert(data.error || "Hiba történt a törlés során!");
                }
            })
                .catch(console.error);
        });
    });
});
