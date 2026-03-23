"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    var modal = document.getElementById("log-modal");
    var uaContainer = document.getElementById("log-ua");
    var ctxContainer = document.getElementById("log-context");
    var closeBtns = document.querySelectorAll(".modal-close, #log-close-btn");
    var logRows = document.querySelectorAll(".log-row");
    if (!modal || !uaContainer || !ctxContainer)
        return;
    logRows.forEach(function (row) {
        row.addEventListener("click", function () {
            var ua = row.getAttribute("data-ua") || "Ismeretlen böngésző";
            var contextRaw = row.getAttribute("data-context") || "{}";
            uaContainer.textContent = ua;
            try {
                // Megpróbáljuk szépen formázni a JSON-t
                var parsed = JSON.parse(contextRaw);
                ctxContainer.textContent = JSON.stringify(parsed, null, 4);
            }
            catch (e) {
                ctxContainer.textContent = contextRaw;
            }
            modal.classList.add("open");
        });
    });
    var closeModal = function () {
        modal.classList.remove("open");
    };
    closeBtns.forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            closeModal();
        });
    });
    // Ha a sötét háttérre kattint
    modal.addEventListener("click", function (e) {
        if (e.target === modal)
            closeModal();
    });
    // ESC gombra záródjon
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape")
            closeModal();
    });
});
