"use strict";
document.addEventListener("DOMContentLoaded", function () {
    var pwdInput = document.getElementById("password");
    var toggleBtn = document.getElementById("btn-toggle-password");
    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener("click", function () {
            var isPassword = pwdInput.type === "password";
            pwdInput.type = isPassword ? "text" : "password";
        });
    }
    var userInput = document.getElementById("username");
    if (userInput) {
        userInput.focus();
    }
});
