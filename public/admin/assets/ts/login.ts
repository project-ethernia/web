document.addEventListener("DOMContentLoaded", () => {
  const pwdInput = document.getElementById("password") as HTMLInputElement | null;
  const toggleBtn = document.getElementById("btn-toggle-password") as HTMLButtonElement | null;

  if (toggleBtn && pwdInput) {
    toggleBtn.addEventListener("click", () => {
      const isPassword = pwdInput.type === "password";
      pwdInput.type = isPassword ? "text" : "password";
    });
  }

  const userInput = document.getElementById("username") as HTMLInputElement | null;
  if (userInput) {
    userInput.focus();
  }
});
