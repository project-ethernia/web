"use strict";
document.addEventListener("DOMContentLoaded", function () {
    var _a, _b;
    var modal = document.getElementById("news-modal");
    var form = document.getElementById("news-form");
    var errorEl = document.getElementById("news-error");
    var closeBtn = (_a = modal === null || modal === void 0 ? void 0 : modal.querySelector(".modal-close")) !== null && _a !== void 0 ? _a : null;
    var backdrop = (_b = modal === null || modal === void 0 ? void 0 : modal.querySelector(".modal-backdrop")) !== null && _b !== void 0 ? _b : null;
    var cancelBtn = document.getElementById("news-cancel");
    var addBtn = document.getElementById("btn-add-news");
    var addBtnEmpty = document.getElementById("btn-add-news-empty");
    var modalTitle = document.getElementById("news-modal-title");
    var idInput = document.getElementById("news-id");
    var titleInput = document.getElementById("news-title");
    var tagSelect = document.getElementById("news-tag");
    var shortInput = document.getElementById("news-short");
    var fullInput = document.getElementById("news-full");
    var orderInput = document.getElementById("news-order");
    var visibleInput = document.getElementById("news-visible");
    var metaAuthor = document.getElementById("news-meta-author");
    var metaDate = document.getElementById("news-meta-date");
    function openModal() {
        if (!modal)
            return;
        modal.classList.add("open");
    }
    function closeModal() {
        if (!modal)
            return;
        modal.classList.remove("open");
        if (errorEl) {
            errorEl.hidden = true;
            errorEl.textContent = "";
        }
    }
    function resetForm() {
        if (!form)
            return;
        form.reset();
        if (idInput)
            idInput.value = "";
        if (orderInput)
            orderInput.value = "0";
        if (visibleInput)
            visibleInput.checked = true;
        if (metaAuthor)
            metaAuthor.textContent = "Mentés után";
        if (metaDate)
            metaDate.textContent = "Mentés után";
    }
    function fillFormFromRow(tr) {
        if (idInput)
            idInput.value = tr.dataset.id || "";
        if (titleInput)
            titleInput.value = tr.dataset.title || "";
        if (tagSelect)
            tagSelect.value = tr.dataset.tag || "Info";
        if (shortInput)
            shortInput.value = tr.dataset.short_text || "";
        if (fullInput)
            fullInput.value = tr.dataset.full_text || "";
        if (orderInput)
            orderInput.value = tr.dataset.order_index || "0";
        if (visibleInput)
            visibleInput.checked = tr.dataset.is_visible === "1";
        if (metaAuthor)
            metaAuthor.textContent = tr.dataset.author || "Ismeretlen";
        if (metaDate)
            metaDate.textContent = tr.dataset.date_display || "-";
    }
    function handleNewClick() {
        resetForm();
        if (modalTitle)
            modalTitle.textContent = "Új hír";
        openModal();
    }
    if (addBtn)
        addBtn.addEventListener("click", handleNewClick);
    if (addBtnEmpty)
        addBtnEmpty.addEventListener("click", handleNewClick);
    document.querySelectorAll(".btn-edit").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var tr = btn.closest("tr");
            if (!tr)
                return;
            resetForm();
            fillFormFromRow(tr);
            if (modalTitle)
                modalTitle.textContent = "Hír szerkesztése";
            openModal();
        });
    });
    document.querySelectorAll(".btn-delete").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var tr = btn.closest("tr");
            if (!tr)
                return;
            var id = tr.dataset.id;
            var title = tr.dataset.title || id || "";
            if (!id)
                return;
            if (!confirm("Biztosan törlöd ezt a hírt?\n\n" + title)) {
                return;
            }
            var formData = new FormData();
            formData.append("action", "delete");
            formData.append("id", id);
            fetch("news.php", {
                method: "POST",
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                if (!data.ok) {
                    alert(data.error || "Ismeretlen hiba történt törlés közben.");
                    return;
                }
                tr.remove();
            })
                .catch(function (err) {
                console.error(err);
                alert("Hálózati hiba történt a törlés során.");
            });
        });
    });
    document.querySelectorAll(".visibility-toggle").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var id = btn.dataset.id;
            if (!id)
                return;
            var current = btn.dataset.visible === "1";
            var next = current ? 0 : 1;
            var formData = new FormData();
            formData.append("action", "toggle_visible");
            formData.append("id", id);
            formData.append("is_visible", String(next));
            fetch("news.php", {
                method: "POST",
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                if (!data.ok) {
                    alert(data.error || "Hiba a láthatóság állításakor.");
                    return;
                }
                btn.dataset.visible = String(next);
                btn.setAttribute("aria-pressed", next ? "true" : "false");
                btn.classList.toggle("is-on", !!next);
                btn.classList.toggle("is-off", !next);
                btn.title = next
                    ? "Látható – kattints az elrejtéshez"
                    : "Rejtett – kattints a megjelenítéshez";
                var tr = btn.closest("tr");
                if (tr) {
                    tr.dataset.is_visible = String(next);
                }
            })
                .catch(function (err) {
                console.error(err);
                alert("Hálózati hiba történt a láthatóság állításakor.");
            });
        });
    });
    var closers = [closeBtn, backdrop, cancelBtn];
    closers.forEach(function (el) {
        if (!el)
            return;
        el.addEventListener("click", function (e) {
            e.preventDefault();
            closeModal();
        });
    });
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            closeModal();
        }
    });
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            if (errorEl) {
                errorEl.hidden = true;
                errorEl.textContent = "";
            }
            var formData = new FormData(form);
            fetch("news.php", {
                method: "POST",
                body: formData
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                if (!data.ok) {
                    if (errorEl) {
                        errorEl.textContent =
                            data.error || "Ismeretlen hiba történt mentés közben.";
                        errorEl.hidden = false;
                    }
                    else {
                        alert(data.error || "Ismeretlen hiba történt mentés közben.");
                    }
                    return;
                }
                window.location.reload();
            })
                .catch(function (err) {
                console.error(err);
                if (errorEl) {
                    errorEl.textContent = "Hálózati hiba történt a mentés során.";
                    errorEl.hidden = false;
                }
                else {
                    alert("Hálózati hiba történt a mentés során.");
                }
            });
        });
    }
});
