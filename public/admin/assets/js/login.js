"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("admin-login-form");
    if (form) {
        var isSubmitting_1 = false;
        form.addEventListener("submit", function (e) {
            if (isSubmitting_1) {
                e.preventDefault();
                return;
            }
            isSubmitting_1 = true;
            var btn = form.querySelector("button[type='submit']");
            if (btn) {
                btn.disabled = true;
                btn.textContent = "Hitelesítés folyamatban...";
            }
        });
    }
});
