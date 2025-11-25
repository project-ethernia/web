/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("login-form") as HTMLFormElement | null;
  if (!form) return;

  let isSubmitting = false;

  form.addEventListener("submit", (e: SubmitEvent) => {
    if (isSubmitting) {
      // ha valamiért mégis újra submitálódna
      e.preventDefault();
      return;
    }

    isSubmitting = true;

    const btn = form.querySelector("button[type='submit']") as HTMLButtonElement | null;
    if (btn) {
      btn.disabled = true;
      btn.textContent = "Beléptetés...";
    }
  });
});
