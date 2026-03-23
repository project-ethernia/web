/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("login-form") as HTMLFormElement | null;
  if (loginForm) {
    let isSubmitting = false;

    loginForm.addEventListener("submit", (e: SubmitEvent) => {
      if (isSubmitting) {
        e.preventDefault();
        return;
      }

      isSubmitting = true;

      const btn = loginForm.querySelector("button[type='submit']") as HTMLButtonElement | null;
      if (btn) {
        btn.disabled = true;
        btn.textContent = "Bejelentkezés folyamatban...";
      }
    });
  }

  const registerForm = document.getElementById("register-form") as HTMLFormElement | null;
  if (registerForm) {
    let isSubmitting = false;

    registerForm.addEventListener("submit", (e: SubmitEvent) => {
      if (isSubmitting) {
        e.preventDefault();
        return;
      }

      const passwordInput = document.getElementById("password") as HTMLInputElement | null;
      const confirmInput = document.getElementById("password_confirm") as HTMLInputElement | null;

      if (passwordInput && confirmInput) {
        if (passwordInput.value !== confirmInput.value) {
          e.preventDefault();
          alert("A két jelszó nem egyezik!");
          return;
        }
      }

      isSubmitting = true;

      const btn = registerForm.querySelector("button[type='submit']") as HTMLButtonElement | null;
      if (btn) {
        btn.disabled = true;
        btn.textContent = "Fiók létrehozása...";
      }
    });
  }
});