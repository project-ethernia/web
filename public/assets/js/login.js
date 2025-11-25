"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("login-form");
    if (!form)
        return;
    let isSubmitting = false;
    form.addEventListener("submit", (e) => {
        if (isSubmitting) {
            // ha valamiért mégis újra submitálódna
            e.preventDefault();
            return;
        }
        isSubmitting = true;
        const btn = form.querySelector("button[type='submit']");
        if (btn) {
            btn.disabled = true;
            btn.textContent = "Beléptetés...";
        }
    });
});
