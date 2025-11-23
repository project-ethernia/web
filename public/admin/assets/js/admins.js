// /admin/admins.js

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("admin-modal");
  const form = document.getElementById("admin-form");
  const errorEl = document.getElementById("admin-error");
  const closeBtn = modal ? modal.querySelector(".modal-close") : null;
  const backdrop = modal ? modal.querySelector(".modal-backdrop") : null;
  const cancelBtn = document.getElementById("admin-cancel");
  const addBtn = document.getElementById("btn-add-admin");
  const addBtnEmpty = document.getElementById("btn-add-admin-empty");

  const usernameInput = document.getElementById("admin-username");
  const passwordInput = document.getElementById("admin-password");
  const roleSelect = document.getElementById("admin-role");

  function openModal() {
    if (!modal) return;
    if (form) form.reset();
    if (errorEl) {
      errorEl.hidden = true;
      errorEl.textContent = "";
    }
    modal.classList.add("open");
    if (usernameInput) usernameInput.focus();
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove("open");
  }

  if (addBtn) addBtn.addEventListener("click", openModal);
  if (addBtnEmpty) addBtnEmpty.addEventListener("click", openModal);

  [closeBtn, backdrop, cancelBtn].forEach((el) => {
    if (el) {
      el.addEventListener("click", (e) => {
        e.preventDefault();
        closeModal();
      });
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeModal();
  });

  // Új admin mentése
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      if (errorEl) {
        errorEl.hidden = true;
        errorEl.textContent = "";
      }

      const formData = new FormData(form);

      fetch("admins.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            if (errorEl) {
              errorEl.hidden = false;
              errorEl.textContent =
                data.error || "Ismeretlen hiba történt az admin létrehozásakor.";
            } else {
              alert(data.error || "Ismeretlen hiba.");
            }
            return;
          }

          // egyszerű megoldás: frissítjük az oldalt
          window.location.reload();
        })
        .catch((err) => {
          console.error(err);
          if (errorEl) {
            errorEl.hidden = false;
            errorEl.textContent = "Hálózati hiba történt a mentés során.";
          } else {
            alert("Hálózati hiba történt.");
          }
        });
    });
  }

  // Aktív / inaktív toggle
  document.querySelectorAll(".visibility-toggle").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      const current = btn.dataset.visible === "1";
      const next = current ? 0 : 1;

      const formData = new FormData();
      formData.append("action", "toggle_active");
      formData.append("id", id);
      formData.append("is_active", String(next));

      fetch("admins.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            alert(data.error || "Hiba az aktiválás/inaktiválás során.");
            return;
          }

          btn.dataset.visible = String(next);
          btn.setAttribute("aria-pressed", next ? "true" : "false");
          btn.classList.toggle("is-on", !!next);
          btn.classList.toggle("is-off", !next);
          btn.title = next
            ? "Aktív – kattints az inaktiváláshoz"
            : "Inaktív – kattints az aktiváláshoz";

          const tr = btn.closest("tr");
          if (tr) tr.dataset.is_active = String(next);
        })
        .catch((err) => {
          console.error(err);
          alert("Hálózati hiba történt az állapot módosítása közben.");
        });
    });
  });

  // Jelszó csere (prompt-tal – egyszerű megoldás)
  document.querySelectorAll(".btn-reset-pw").forEach((btn) => {
    btn.addEventListener("click", () => {
      const tr = btn.closest("tr");
      if (!tr) return;

      const id = tr.dataset.id;
      const username = tr.dataset.username || id;

      const newPw = window.prompt(
        `Új jelszó beállítása ehhez az adminhoz: ${username}\n\nÍrd be az új jelszót:`
      );
      if (!newPw) return; // megszakította

      if (newPw.length < 4) {
        alert("A jelszó legyen legalább 4 karakter (nyilván élesben lehet több).");
        return;
      }

      const formData = new FormData();
      formData.append("action", "reset_password");
      formData.append("id", id);
      formData.append("password", newPw);

      fetch("admins.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            alert(data.error || "Hiba a jelszó csere során.");
            return;
          }
          alert("Jelszó sikeresen módosítva ehhez az adminhoz: " + username);
        })
        .catch((err) => {
          console.error(err);
          alert("Hálózati hiba történt a jelszó módosítása közben.");
        });
    });
  });
});
