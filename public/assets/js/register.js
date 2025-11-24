document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("register-form");
  if (!form) return;

  const usernameInput = document.getElementById("username");
  const emailInput = document.getElementById("email");
  const passInput = document.getElementById("password");
  const pass2Input = document.getElementById("password_confirm");
  const submitBtn = form.querySelector("button[type='submit']");

  function clearFieldError(group) {
    if (!group) return;
    group.classList.remove("has-error");
    const errEl = group.querySelector(".form-error-inline");
    if (errEl) errEl.remove();
  }

  function setFieldError(inputEl, message) {
    const group = inputEl.closest(".form-group");
    if (!group) return;
    clearFieldError(group);
    group.classList.add("has-error");
    const p = document.createElement("div");
    p.className = "form-error-inline";
    p.textContent = message;
    group.appendChild(p);
  }

  form.addEventListener("submit", (e) => {
    let hasError = false;

    // előző hibák törlése
    form.querySelectorAll(".form-group").forEach((g) => clearFieldError(g));

    const username = usernameInput.value.trim();
    const email = emailInput.value.trim();
    const pass = passInput.value;
    const pass2 = pass2Input.value;

    // Felhasználónév
    if (username.length < 3 || username.length > 32) {
      setFieldError(
        usernameInput,
        "A felhasználónév 3–32 karakter hosszú legyen."
      );
      hasError = true;
    }

    // Email
    if (!email) {
      setFieldError(emailInput, "Az e-mail cím megadása kötelező.");
      hasError = true;
    } else {
      const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRe.test(email)) {
        setFieldError(emailInput, "Adj meg érvényes e-mail címet.");
        hasError = true;
      }
    }

    // Jelszó
    if (pass.length < 8) {
      setFieldError(passInput, "A jelszó legalább 8 karakter legyen.");
      hasError = true;
    }

    // Jelszó megerősítés
    if (pass !== pass2) {
      setFieldError(
        pass2Input,
        "A jelszó és a megerősítés nem egyezik."
      );
      hasError = true;
    }

    if (hasError) {
      e.preventDefault();
      return;
    }

    // opcionális: "loading" állapot
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = "Regisztráció...";
    }
  });
});
