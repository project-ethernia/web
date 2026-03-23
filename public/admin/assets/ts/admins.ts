/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("admin-modal") as HTMLElement | null;
  const form = document.getElementById("admin-form") as HTMLFormElement | null;
  const errorEl = document.getElementById("admin-error") as HTMLElement | null;
  const closeBtn = modal ? (modal.querySelector(".modal-close") as HTMLElement | null) : null;
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

  const closeElements: (HTMLElement | null)[] = [closeBtn, cancelBtn];
  closeElements.forEach((el) => {
    if (!el) return;
    el.addEventListener("click", (e: Event) => {
      e.preventDefault();
      closeModal();
    });
  });

  if (modal) {
    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModal();
    });
  }

  document.addEventListener("keydown", (e: KeyboardEvent) => {
    if (e.key === "Escape") closeModal();
  });

  if (form) {
    form.addEventListener("submit", (e: Event) => {
      e.preventDefault();
      
      if (errorEl) {
        errorEl.hidden = true;
        errorEl.textContent = "";
      }

      const submitBtn = form.querySelector("button[type='submit']") as HTMLButtonElement | null;
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = "Létrehozás...";
      }

      const formData = new FormData(form);

      fetch("admins.php", {
        method: "POST",
        body: formData
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            if (errorEl) {
              errorEl.hidden = false;
              errorEl.textContent = data.error || "Ismeretlen hiba.";
            } else {
              alert(data.error || "Ismeretlen hiba.");
            }
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.textContent = "Létrehozás";
            }
            return;
          }
          window.location.reload();
        })
        .catch(() => {
          if (errorEl) {
            errorEl.hidden = false;
            errorEl.textContent = "Hálózati hiba történt.";
          }
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = "Létrehozás";
          }
        });
    });
  }

  document.querySelectorAll<HTMLButtonElement>(".toggle-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      if (!id) return;

      const current = btn.dataset.visible === "1";
      const next = current ? 0 : 1;

      const formData = new FormData();
      formData.append("action", "toggle_active");
      formData.append("id", id);
      formData.append("is_active", String(next));

      fetch("admins.php", {
        method: "POST",
        body: formData
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            alert(data.error || "Hiba az állapot módosításakor.");
            return;
          }

          btn.dataset.visible = String(next);
          btn.classList.toggle("active", !!next);

          const tr = btn.closest("tr") as HTMLTableRowElement | null;
          if (tr) tr.dataset.is_active = String(next);
        })
        .catch(() => alert("Hálózati hiba történt."));
    });
  });

  document.querySelectorAll<HTMLButtonElement>(".btn-reset-pw").forEach((btn) => {
    btn.addEventListener("click", () => {
      const tr = btn.closest("tr") as HTMLTableRowElement | null;
      if (!tr) return;

      const id = tr.dataset.id;
      if (!id) return;

      const username = tr.dataset.username || id;

      const newPw = window.prompt("Új jelszó beállítása ehhez: " + username);
      if (!newPw) return;

      if (newPw.length < 4) {
        alert("A jelszó legyen legalább 4 karakter!");
        return;
      }

      const formData = new FormData();
      formData.append("action", "reset_password");
      formData.append("id", id);
      formData.append("password", newPw);

      fetch("admins.php", {
        method: "POST",
        body: formData
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            alert(data.error || "Hiba a jelszó csere során.");
            return;
          }
          alert("Jelszó sikeresen módosítva!");
        })
        .catch(() => alert("Hálózati hiba a jelszó módosításakor."));
    });
  });
});