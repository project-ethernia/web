// /admin/login.js

document.addEventListener("DOMContentLoaded", () => {
  const pwdInput = document.getElementById("password");
  const toggleBtn = document.getElementById("btn-toggle-password");

  if (toggleBtn && pwdInput) {
    toggleBtn.addEventListener("click", () => {
      const isPassword = pwdInput.type === "password";
      pwdInput.type = isPassword ? "text" : "password";
    });
  }

  // fókusz a username-re
  const userInput = document.getElementById("username");
  if (userInput) {
    userInput.focus();
  }
});
