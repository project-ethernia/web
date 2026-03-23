"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("login-form");
    if (loginForm) {
        let isSubmitting = false;
        loginForm.addEventListener("submit", (e) => {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            isSubmitting = true;
            const btn = loginForm.querySelector("button[type='submit']");
            if (btn) {
                btn.disabled = true;
                btn.textContent = "Bejelentkezés folyamatban...";
            }
        });
    }
    const registerForm = document.getElementById("register-form");
    if (registerForm) {
        let isSubmitting = false;
        registerForm.addEventListener("submit", (e) => {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            const passwordInput = document.getElementById("password");
            const confirmInput = document.getElementById("password_confirm");
            if (passwordInput && confirmInput) {
                if (passwordInput.value !== confirmInput.value) {
                    e.preventDefault();
                    alert("A két jelszó nem egyezik!");
                    return;
                }
            }
            isSubmitting = true;
            const btn = registerForm.querySelector("button[type='submit']");
            if (btn) {
                btn.disabled = true;
                btn.textContent = "Fiók létrehozása...";
            }
        });
    }
});
