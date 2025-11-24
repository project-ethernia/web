document.addEventListener("DOMContentLoaded", () => {
  const table = document.querySelector(".admin-table tbody");
  if (!table) return;

  const emailModal = document.getElementById("user-email-modal");
  const passModal = document.getElementById("user-password-modal");
  const delModal = document.getElementById("user-delete-modal");

  // -------- helper függvények --------
  function openModal(modal) {
    if (!modal) return;
    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
  }

  document.querySelectorAll(".modal-backdrop, .modal-close, [data-close]")
    .forEach((el) => {
      el.addEventListener("click", (e) => {
        const target = e.currentTarget;
        const id = target.dataset.close || target.closest(".modal")?.id;
        if (!id) return;
        const m = document.getElementById(id);
        closeModal(m);
      });
    });

  // esc-re zárás
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      [emailModal, passModal, delModal].forEach(closeModal);
    }
  });

  // -------- gomb események a táblában --------
  table.addEventListener("click", (e) => {
    const row = e.target.closest("tr");
    if (!row) return;

    const id = row.dataset.id;
    const username = row.dataset.username;
    const email = row.dataset.email;

    if (e.target.classList.contains("btn-user-email")) {
      // email modal
      document.getElementById("user-email-id").value = id;
      document.getElementById("user-email-name").textContent = username;
      const emailInput = document.getElementById("user-email-new");
      emailInput.value = email || "";
      document.getElementById("user-email-error").hidden = true;
      openModal(emailModal);
    }

    if (e.target.classList.contains("btn-user-password")) {
      document.getElementById("user-password-id").value = id;
      document.getElementById("user-password-name").textContent = username;
      document.getElementById("user-password-new").value = "";
      document.getElementById("user-password-confirm").value = "";
      document.getElementById("user-password-error").hidden = true;
      openModal(passModal);
    }

    if (e.target.classList.contains("btn-user-delete")) {
      document.getElementById("user-delete-id").value = id;
      document.getElementById("user-delete-name").textContent = username;
      document.getElementById("user-delete-email").textContent = email;
      document.getElementById("user-delete-error").hidden = true;
      openModal(delModal);
    }
  });

  // -------- E‑MAIL FORM --------
  const emailForm = document.getElementById("user-email-form");
  if (emailForm) {
    emailForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const errEl = document.getElementById("user-email-error");
      errEl.hidden = true;
      const group = document.getElementById("group-email");
      group.classList.remove("has-error");

      const formData = new FormData(emailForm);

      try {
        const res = await fetch("/admin/users.php", {
          method: "POST",
          body: formData,
        });

        const data = await res.json();
        if (!data.ok) {
          throw new Error(data.error || "Ismeretlen hiba.");
        }

        const id = formData.get("id");
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row && data.email) {
          row.dataset.email = data.email;
          const cell = row.querySelector(".cell-email");
          if (cell) cell.textContent = data.email;
        }

        closeModal(emailModal);
      } catch (err) {
        group.classList.add("has-error");
        errEl.textContent = err.message;
        errEl.hidden = false;
      }
    });
  }

  // -------- JELSZÓ FORM --------
  const passForm = document.getElementById("user-password-form");
  if (passForm) {
    passForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const errEl = document.getElementById("user-password-error");
      errEl.hidden = true;
      document.getElementById("group-pass1").classList.remove("has-error");
      document.getElementById("group-pass2").classList.remove("has-error");

      const pass1 = document.getElementById("user-password-new").value;
      const pass2 = document.getElementById("user-password-confirm").value;

      if (pass1.length < 8) {
        errEl.textContent = "A jelszó legalább 8 karakter legyen.";
        errEl.hidden = false;
        document.getElementById("group-pass1").classList.add("has-error");
        return;
      }
      if (pass1 !== pass2) {
        errEl.textContent = "A két jelszó nem egyezik.";
        errEl.hidden = false;
        document.getElementById("group-pass2").classList.add("has-error");
        return;
      }

      const formData = new FormData(passForm);
      formData.set("password", pass1);

      try {
        const res = await fetch("/admin/users.php", {
          method: "POST",
          body: formData,
        });
        const data = await res.json();
        if (!data.ok) {
          throw new Error(data.error || "Ismeretlen hiba.");
        }

        closeModal(passModal);
      } catch (err) {
        errEl.textContent = err.message;
        errEl.hidden = false;
      }
    });
  }

  // -------- TÖRLÉS FORM --------
  const delForm = document.getElementById("user-delete-form");
  if (delForm) {
    delForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const errEl = document.getElementById("user-delete-error");
      errEl.hidden = true;

      const formData = new FormData(delForm);

      try {
        const res = await fetch("/admin/users.php", {
          method: "POST",
          body: formData,
        });
        const data = await res.json();
        if (!data.ok) {
          throw new Error(data.error || "Ismeretlen hiba.");
        }

        const id = formData.get("id");
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) row.remove();

        closeModal(delModal);
      } catch (err) {
        errEl.textContent = err.message;
        errEl.hidden = false;
      }
    });
  }
});
