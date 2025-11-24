document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("login-form");
  if (!form) return;

  let isSubmitting = false;

  form.addEventListener("submit", () => {
    if (isSubmitting) return;
    isSubmitting = true;

    const btn = form.querySelector("button[type=submit]");
    if (btn) {
      btn.disabled = true;
      btn.textContent = "Beléptetés...";
    }
  });
});
