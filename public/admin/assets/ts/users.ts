// public/admin/assets/ts/users.ts

document.addEventListener("DOMContentLoaded", () => {
  const rows = document.querySelectorAll<HTMLTableRowElement>(".users-table tbody tr");
  if (!rows.length) return;

  function openModal(id: string): void {
    const m = document.getElementById(id);
    if (m) m.classList.add("open");
  }

  function closeModal(el: Element): void {
    const m = el.closest<HTMLElement>(".modal");
    if (m) m.classList.remove("open");
  }

  document.querySelectorAll<HTMLElement>("[data-modal-close]").forEach((btn) => {
    btn.addEventListener("click", () => closeModal(btn));
  });

  document.querySelectorAll<HTMLElement>(".modal-backdrop").forEach((bd) => {
    bd.addEventListener("click", () => closeModal(bd));
  });

  document.addEventListener("keydown", (e: KeyboardEvent) => {
    if (e.key === "Escape") {
      document
        .querySelectorAll<HTMLElement>(".modal.open")
        .forEach((m) => m.classList.remove("open"));
    }
  });

  const emailForm = document.getElementById("form-change-email") as HTMLFormElement | null;
  const emailUserId = document.getElementById("email-user-id") as HTMLInputElement | null;
  const emailUsername = document.getElementById("email-username") as HTMLElement | null;
  const emailInput = document.getElementById("email-new") as HTMLInputElement | null;
  const emailError = document.getElementById("email-error") as HTMLElement | null;

  const pwForm = document.getElementById("form-change-password") as HTMLFormElement | null;
  const pwUserId = document.getElementById("pw-user-id") as HTMLInputElement | null;
  const pwUsername = document.getElementById("pw-username") as HTMLElement | null;
  const pwNew = document.getElementById("pw-new") as HTMLInputElement | null;
  const pwNew2 = document.getElementById("pw-new2") as HTMLInputElement | null;
  const pwError = document.getElementById("pw-error") as HTMLElement | null;

  const delForm = document.getElementById("form-delete-user") as HTMLFormElement | null;
  const delUserId = document.getElementById("del-user-id") as HTMLInputElement | null;
  const delUsername = document.getElementById("del-username") as HTMLElement | null;
  const delEmail = document.getElementById("del-email") as HTMLElement | null;
  const delError = document.getElementById("del-error") as HTMLElement | null;

  rows.forEach((row) => {
    const id = row.dataset.id || "";
    const username = row.dataset.username || "";
    const email = row.dataset.email || "";

    const btnEmail = row.querySelector<HTMLElement>(".js-change-email");
    const btnPw = row.querySelector<HTMLElement>(".js-change-password");
    const btnDel = row.querySelector<HTMLElement>(".js-delete-user");

    if (btnEmail && emailUserId && emailUsername && emailInput && emailError) {
      btnEmail.addEventListener("click", () => {
        emailUserId.value = id;
        emailUsername.textContent = username;
        emailInput.value = email;
        emailError.hidden = true;
        emailError.textContent = "";
        openModal("modal-change-email");
        emailInput.focus();
      });
    }

    if (btnPw && pwUserId && pwUsername && pwNew && pwNew2 && pwError) {
      btnPw.addEventListener("click", () => {
        pwUserId.value = id;
        pwUsername.textContent = username;
        pwNew.value = "";
        pwNew2.value = "";
        pwError.hidden = true;
        pwError.textContent = "";
        openModal("modal-change-password");
        pwNew.focus();
      });
    }

    if (btnDel && delUserId && delUsername && delEmail && delError) {
      btnDel.addEventListener("click", () => {
        delUserId.value = id;
        delUsername.textContent = username;
        delEmail.textContent = email;
        delError.hidden = true;
        delError.textContent = "";
        openModal("modal-delete-user");
      });
    }
  });

  const postUsers = (data: Record<string, string>): Promise<any> => {
    return fetch("/admin/users.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: new URLSearchParams(data)
    }).then(async (res) => {
      const json = await res.json().catch(() => ({} as any));
      if (!res.ok || !json.ok) {
        const msg =
          (json && json.error) ||
          `Ismeretlen hiba (HTTP ${res.status})`;
        throw new Error(msg);
      }
      return json;
    });
  };

  if (emailForm && emailUserId && emailInput && emailError) {
    emailForm.addEventListener("submit", (e: SubmitEvent) => {
      e.preventDefault();
      emailError.hidden = true;
      emailError.textContent = "";

      const id = emailUserId.value;
      const newEmail = emailInput.value.trim();

      if (!newEmail) {
        emailError.textContent = "Adj meg egy e‑mail címet.";
        emailError.hidden = false;
        return;
      }

      postUsers({
        action: "change_email",
        id,
        email: newEmail
      })
        .then((json) => {
          const row = document.querySelector<HTMLTableRowElement>(
            `.users-table tbody tr[data-id="${json.id}"]`
          );
          if (row) {
            row.dataset.email = json.email;
            const cell = row.querySelector<HTMLElement>(".cell-email");
            if (cell) cell.textContent = json.email;
          }
          closeModal(emailForm);
        })
        .catch((err: Error) => {
          emailError.textContent = err.message;
          emailError.hidden = false;
        });
    });
  }

  if (pwForm && pwUserId && pwNew && pwNew2 && pwError) {
    pwForm.addEventListener("submit", (e: SubmitEvent) => {
      e.preventDefault();
      pwError.hidden = true;
      pwError.textContent = "";

      const id = pwUserId.value;
      const p1 = pwNew.value;
      const p2 = pwNew2.value;

      if (!p1 || !p2) {
        pwError.textContent = "Töltsd ki mindkét jelszó mezőt.";
        pwError.hidden = false;
        return;
      }
      if (p1 !== p2) {
        pwError.textContent = "A két jelszó nem egyezik.";
        pwError.hidden = false;
        return;
      }

      postUsers({
        action: "change_password",
        id,
        password: p1
      })
        .then(() => {
          closeModal(pwForm);
        })
        .catch((err: Error) => {
          pwError.textContent = err.message;
          pwError.hidden = false;
        });
    });
  }

  if (delForm && delUserId && delError) {
    delForm.addEventListener("submit", (e: SubmitEvent) => {
      e.preventDefault();
      delError.hidden = true;
      delError.textContent = "";

      const id = delUserId.value;

      postUsers({
        action: "delete_user",
        id
      })
        .then((json) => {
          const row = document.querySelector<HTMLTableRowElement>(
            `.users-table tbody tr[data-id="${json.id}"]`
          );
          if (row && row.parentElement) {
            row.parentElement.removeChild(row);
          }
          closeModal(delForm);
        })
        .catch((err: Error) => {
          delError.textContent = err.message;
          delError.hidden = false;
        });
    });
  }
});
