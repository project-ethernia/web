/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("admin-login-form") as HTMLFormElement | null;
  
  if (form) {
    let isSubmitting = false;

    form.addEventListener("submit", (e: SubmitEvent) => {
      if (isSubmitting) {
        e.preventDefault();
        return;
      }

      isSubmitting = true;

      const btn = form.querySelector("button[type='submit']") as HTMLButtonElement | null;
      if (btn) {
        btn.disabled = true;
        btn.textContent = "Hitelesítés folyamatban...";
      }
    });
  }
});