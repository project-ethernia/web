// /admin/news.js

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("news-modal");
  const form = document.getElementById("news-form");
  const errorEl = document.getElementById("news-error");
  const closeBtn = modal ? modal.querySelector(".modal-close") : null;
  const backdrop = modal ? modal.querySelector(".modal-backdrop") : null;
  const cancelBtn = document.getElementById("news-cancel");
  const addBtn = document.getElementById("btn-add-news");
  const addBtnEmpty = document.getElementById("btn-add-news-empty");
  const modalTitle = document.getElementById("news-modal-title");

  const idInput = document.getElementById("news-id");
  const titleInput = document.getElementById("news-title");
  const tagSelect = document.getElementById("news-tag");
  const shortInput = document.getElementById("news-short");
  const fullInput = document.getElementById("news-full");
  const orderInput = document.getElementById("news-order");
  const visibleInput = document.getElementById("news-visible");
  const metaAuthor = document.getElementById("news-meta-author");
  const metaDate = document.getElementById("news-meta-date");

  function openModal() {
    if (!modal) return;
    modal.classList.add("open");
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove("open");
    if (errorEl) {
      errorEl.hidden = true;
      errorEl.textContent = "";
    }
  }

  function resetForm() {
    if (!form) return;
    form.reset();
    idInput.value = "";
    orderInput.value = "0";
    visibleInput.checked = true;
    if (metaAuthor) metaAuthor.textContent = "Mentés után";
    if (metaDate) metaDate.textContent = "Mentés után";
  }

  function fillFormFromRow(tr) {
    idInput.value = tr.dataset.id || "";
    titleInput.value = tr.dataset.title || "";
    tagSelect.value = tr.dataset.tag || "Info";
    shortInput.value = tr.dataset.short_text || "";
    fullInput.value = tr.dataset.full_text || "";
    orderInput.value = tr.dataset.order_index || "0";
    visibleInput.checked = tr.dataset.is_visible === "1";
    if (metaAuthor) metaAuthor.textContent = tr.dataset.author || "Ismeretlen";
    if (metaDate) metaDate.textContent = tr.dataset.date_display || "-";
  }

  // Új hír gomb(ok)
  function handleNewClick() {
    resetForm();
    if (modalTitle) modalTitle.textContent = "Új hír";
    openModal();
  }

  if (addBtn) addBtn.addEventListener("click", handleNewClick);
  if (addBtnEmpty) addBtnEmpty.addEventListener("click", handleNewClick);

  // Szerkesztés gombok
  document.querySelectorAll(".btn-edit").forEach((btn) => {
    btn.addEventListener("click", () => {
      const tr = btn.closest("tr");
      if (!tr) return;
      resetForm();
      fillFormFromRow(tr);
      if (modalTitle) modalTitle.textContent = "Hír szerkesztése";
      openModal();
    });
  });

  // Törlés gombok
  document.querySelectorAll(".btn-delete").forEach((btn) => {
    btn.addEventListener("click", () => {
      const tr = btn.closest("tr");
      if (!tr) return;
      const id = tr.dataset.id;
      const title = tr.dataset.title || id;

      if (!confirm('Biztosan törlöd ezt a hírt?\n\n' + title)) {
        return;
      }

      const formData = new FormData();
      formData.append("action", "delete");
      formData.append("id", id);

      fetch("news.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            alert(data.error || "Ismeretlen hiba történt törlés közben.");
            return;
          }
          tr.remove();
        })
        .catch((err) => {
          console.error(err);
          alert("Hálózati hiba történt a törlés során.");
        });
    });
  });

  // Láthatóság toggle
  document.querySelectorAll(".visibility-toggle").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      const current = btn.dataset.visible === "1";
      const next = current ? 0 : 1;

      const formData = new FormData();
      formData.append("action", "toggle_visible");
      formData.append("id", id);
      formData.append("is_visible", String(next));

      fetch("news.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            alert(data.error || "Hiba a láthatóság állításakor.");
            return;
          }
          // UI frissítés
          btn.dataset.visible = String(next);
          btn.setAttribute("aria-pressed", next ? "true" : "false");
          btn.classList.toggle("is-on", !!next);
          btn.classList.toggle("is-off", !next);
          btn.title = next
            ? "Látható – kattints az elrejtéshez"
            : "Rejtett – kattints a megjelenítéshez";

          // sor data attribútum is frissüljön
          const tr = btn.closest("tr");
          if (tr) {
            tr.dataset.is_visible = String(next);
          }
        })
        .catch((err) => {
          console.error(err);
          alert("Hálózati hiba történt a láthatóság állításakor.");
        });
    });
  });

  // Modal bezárása
  [closeBtn, backdrop, cancelBtn].forEach((el) => {
    if (el) {
      el.addEventListener("click", (e) => {
        e.preventDefault();
        closeModal();
      });
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal();
    }
  });

  // Mentor: Mentés
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      if (errorEl) {
        errorEl.hidden = true;
        errorEl.textContent = "";
      }

      const formData = new FormData(form);

      fetch("news.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            if (errorEl) {
              errorEl.textContent =
                data.error || "Ismeretlen hiba történt mentés közben.";
              errorEl.hidden = false;
            } else {
              alert(data.error || "Ismeretlen hiba történt mentés közben.");
            }
            return;
          }

          // egyszerűen frissítjük az oldalt, hogy minden adat frissüljön
          window.location.reload();
        })
        .catch((err) => {
          console.error(err);
          if (errorEl) {
            errorEl.textContent = "Hálózati hiba történt a mentés során.";
            errorEl.hidden = false;
          } else {
            alert("Hálózati hiba történt a mentés során.");
          }
        });
    });
  }
});
