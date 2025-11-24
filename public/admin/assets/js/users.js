// users.js – felhasználók kezelése (e‑mail, jelszó, törlés)

document.addEventListener("DOMContentLoaded", () => {
  const rows = document.querySelectorAll(".users-table tbody tr");
  if (!rows.length) return;

  // --- MODAL helper ---
  function openModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.add("open");
  }
  function closeModal(el) {
    const m = el.closest(".modal");
    if (m) m.classList.remove("open");
  }
  document.querySelectorAll("[data-modal-close]").forEach((btn) => {
    btn.addEventListener("click", () => closeModal(btn));
  });
  document.querySelectorAll(".modal-backdrop").forEach((bd) => {
    bd.addEventListener("click", () => closeModal(bd));
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      document
        .querySelectorAll(".modal.open")
        .forEach((m) => m.classList.remove("open"));
    }
  });

  // --- E‑MAIL MÓDOSÍTÁS ---
  const emailModal = document.getElementById("modal-change-email");
  const emailForm = document.getElementById("form-change-email");
  const emailUserId = document.getElementById("email-user-id");
  const emailUsername = document.getElementById("email-username");
  const emailInput = document.getElementById("email-new");
  const emailError = document.getElementById("email-error");

  // --- JELSZÓ CSERE ---
  const pwModal = document.getElementById("modal-change-password");
  const pwForm = document.getElementById("form-change-password");
  const pwUserId = document.getElementById("pw-user-id");
  const pwUsername = document.getElementById("pw-username");
  const pwNew = document.getElementById("pw-new");
  const pwNew2 = document.getElementById("pw-new2");
  const pwError = document.getElementById("pw-error");

  // --- TÖRLÉS ---
  const delModal = document.getElementById("modal-delete-user");
  const delForm = document.getElementById("form-delete-user");
  const delUserId = document.getElementById("del-user-id");
  const delUsername = document.getElementById("del-username");
  const delEmail = document.getElementById("del-email");
  const delError = document.getElementById("del-error");

  rows.forEach((row) => {
    const id = row.dataset.id;
    const username = row.dataset.username;
    const email = row.dataset.email;

    const btnEmail = row.querySelector(".js-change-email");
    const btnPw = row.querySelector(".js-change-password");
    const btnDel = row.querySelector(".js-delete-user");

    if (btnEmail) {
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

    if (btnPw) {
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

    if (btnDel) {
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

  function postUsers(data) {
    return fetch("/admin/users.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams(data),
    }).then(async (res) => {
      const json = await res.json().catch(() => ({}));
      if (!res.ok || !json.ok) {
        const msg =
          (json && json.error) ||
          `Ismeretlen hiba (HTTP ${res.status})`;
        throw new Error(msg);
      }
      return json;
    });
  }

  // --- e‑mail form submit ---
  if (emailForm) {
    emailForm.addEventListener("submit", (e) => {
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
        email: newEmail,
      })
        .then((json) => {
          // update sorban
          const row = document.querySelector(
            `.users-table tbody tr[data-id="${json.id}"]`
          );
          if (row) {
            row.dataset.email = json.email;
            const cell = row.querySelector(".cell-email");
            if (cell) cell.textContent = json.email;
          }
          closeModal(emailForm);
        })
        .catch((err) => {
          emailError.textContent = err.message;
          emailError.hidden = false;
        });
    });
  }

  // --- jelszó form submit ---
  if (pwForm) {
    pwForm.addEventListener("submit", (e) => {
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
        password: p1,
      })
        .then(() => {
          closeModal(pwForm);
        })
        .catch((err) => {
          pwError.textContent = err.message;
          pwError.hidden = false;
        });
    });
  }

  // --- törlés form submit ---
  if (delForm) {
    delForm.addEventListener("submit", (e) => {
      e.preventDefault();
      delError.hidden = true;
      delError.textContent = "";

      const id = delUserId.value;

      postUsers({
        action: "delete_user",
        id,
      })
        .then((json) => {
          const row = document.querySelector(
            `.users-table tbody tr[data-id="${json.id}"]`
          );
          if (row && row.parentElement) {
            row.parentElement.removeChild(row);
          }
          closeModal(delForm);
        })
        .catch((err) => {
          delError.textContent = err.message;
          delError.hidden = false;
        });
    });
  }
});
