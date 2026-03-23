"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    var modal = document.getElementById("log-modal");
    var rows = document.querySelectorAll(".log-row");
    var uaContainer = document.getElementById("log-ua");
    var ctxContainer = document.getElementById("log-context");
    var closeBtn = modal === null || modal === void 0 ? void 0 : modal.querySelector(".modal-close");
    var closeBtnFooter = document.getElementById("log-close-btn");
    if (!modal || !uaContainer || !ctxContainer)
        return;
    rows.forEach(function (row) {
        row.addEventListener("click", function () {
            var ua = row.getAttribute("data-ua") || "Nincs adat";
            var context = row.getAttribute("data-context") || "{}";
            uaContainer.textContent = ua;
            try {
                var parsed = JSON.parse(context);
                ctxContainer.textContent = JSON.stringify(parsed, null, 4);
            }
            catch (e) {
                ctxContainer.textContent = context;
            }
            modal.classList.add("open");
        });
    });
    var closeModal = function () { return modal.classList.remove("open"); };
    closeBtn === null || closeBtn === void 0 ? void 0 : closeBtn.addEventListener("click", closeModal);
    closeBtnFooter === null || closeBtnFooter === void 0 ? void 0 : closeBtnFooter.addEventListener("click", closeModal);
    modal.addEventListener("click", function (e) {
        if (e.target === modal)
            closeModal();
    });
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape")
            closeModal();
    });
});
