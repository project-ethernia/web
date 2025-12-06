document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("admin-modal") as HTMLElement | null;
  const form = document.getElementById("admin-form") as HTMLFormElement | null;
  const errorEl = document.getElementById("admin-error") as HTMLElement | null;
  const closeBtn = modal?.querySelector<HTMLElement>(".modal-close") ?? null;
  const backdrop = modal?.querySelector<HTMLElement>(".modal-backdrop") ?? null;
  const cancelBtn = document.getElementById("admin-cancel") as HTMLElement | null;
  const addBtn = document.getElementById("btn-add-admin") as HTMLElement | null;
  const addBtnEmpty = document.getElementById("btn-add-admin-empty") as HTMLElement | null;

  const usernameInput = document.getElementById("admin-username") as HTMLInputElement | null;

  function openModal(): void {
    if (!modal) return;
    if (form) form.reset();
    if (errorEl) {
      errorEl.hidden = true;
      errorEl.textContent = "";
    }
    modal.classList.add("open");
    if (usernameInput) usernameInput.focus();
  }

  function closeModal(): void {
    if (!modal) return;
    modal.classList.remove("open");
  }

  if (addBtn) addBtn.addEventListener("click", openModal);
  if (addBtnEmpty) addBtnEmpty.addEventListener("click", openModal);

  [closeBtn, backdrop, cancelBtn].forEach((el) => {
    if (!el) return;
    el.addEventListener("click", (e) => {
      e.preventDefault();
      closeModal();
    });
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeModal();
  });

  function showError(message: string): void {
    if (errorEl) {
      errorEl.hidden = false;
      errorEl.textContent = message;
    } else {
      alert(message);
    }
  }

  if (form) {
    form.addEventListener("submit", (e: SubmitEvent) => {
      e.preventDefault();
      if (errorEl) {
        errorEl.hidden = true;
        errorEl.textContent = "";
      }

      const submitBtn = form.querySelector("button[type=submit]") as HTMLButtonElement | null;
      if (submitBtn) submitBtn.disabled = true;

      const formData = new FormData(form);

      fetch("/admin/admins.php", {
        method: "POST",
        body: formData
      })
        .then(async (res) => {
          let data: any;
          try {
            data = await res.json();
          } catch {
            throw new Error("Hibás válasz érkezett a szervertől.");
          }
          if (!data.ok) {
            throw new Error(data.error || "Ismeretlen hiba történt az admin létrehozásakor.");
          }
          window.location.reload();
        })
        .catch((err: any) => {
          console.error(err);
          showError(err?.message || "Hálózati hiba történt a mentés során.");
        })
        .finally(() => {
          if (submitBtn) submitBtn.disabled = false;
        });
    });
  }

  document.querySelectorAll<HTMLButtonElement>(".visibility-toggle").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      if (!id) return;

      const currentVisible = btn.dataset.visible === "1";
      const next = currentVisible ? 0 : 1;

      btn.disabled = true;

      const formData = new FormData();
      formData.append("action", "toggle_active");
      formData.append("id", id);
      formData.append("is_active", String(next));

      fetch("/admin/admins.php", {
        method: "POST",
        body: formData
      })
        .then(async (res) => {
          let data: any;
          try {
            data = await res.json();
          } catch {
            throw new Error("Hibás válasz érkezett a szervertől.");
          }
          if (!data.ok) {
            throw new Error(data.error || "Hiba az aktiválás/inaktiválás során.");
          }

          btn.dataset.visible = String(next);
          btn.setAttribute("aria-pressed", next ? "true" : "false");
          btn.classList.toggle("is-on", !!next);
          btn.classList.toggle("is-off", !next);
          btn.title = next
            ? "Aktív – kattints az inaktiváláshoz"
            : "Inaktív – kattints az aktiváláshoz";

          const tr = btn.closest("tr") as HTMLTableRowElement | null;
          if (tr) {
            tr.dataset.is_active = String(next);
          }
        })
        .catch((err: any) => {
          console.error(err);
          alert(err?.message || "Hálózati hiba történt az állapot módosítása közben.");
        })
        .finally(() => {
          btn.disabled = false;
        });
    });
  });

  document.querySelectorAll<HTMLButtonElement>(".btn-reset-pw").forEach((btn) => {
    btn.addEventListener("click", () => {
      const tr = btn.closest("tr") as HTMLTableRowElement | null;
      if (!tr) return;

      const id = tr.dataset.id;
      if (!id) return;

      const username = tr.dataset.username || id;

      const newPw = window.prompt(
        `Új jelszó beállítása ehhez az adminhoz: ${username}\n\nÍrd be az új jelszót:`
      );
      if (!newPw) return;

      if (newPw.length < 4) {
        alert("A jelszó legyen legalább 4 karakter.");
        return;
      }

      const formData = new FormData();
      formData.append("action", "reset_password");
      formData.append("id", id);
      formData.append("password", newPw);

      btn.disabled = true;

      fetch("/admin/admins.php", {
        method: "POST",
        body: formData
      })
        .then(async (res) => {
          let data: any;
          try {
            data = await res.json();
          } catch {
            throw new Error("Hibás válasz érkezett a szervertől.");
          }
          if (!data.ok) {
            throw new Error(data.error || "Hiba a jelszó csere során.");
          }
          alert("Jelszó sikeresen módosítva ehhez az adminhoz: " + username);
        })
        .catch((err: any) => {
          console.error(err);
          alert(err?.message || "Hálózati hiba történt a jelszó módosítása közben.");
        })
        .finally(() => {
          btn.disabled = false;
        });
    });
  });
});