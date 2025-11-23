// admin/news.js

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("news-modal");
  const form = document.getElementById("news-form");
  const errorEl = document.getElementById("news-error");
  const closeBtn = modal ? modal.querySelector(".modal-close") : null;
  const backdrop = modal ? modal.querySelector(".modal-backdrop") : null;
  const cancelBtn = document.getElementById("news-cancel");
  const addBtn = document.getElementById("btn-add-news");
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

  // Új hír
  if (addBtn) {
    addBtn.addEventListener("click", () => {
      resetForm();
      if (modalTitle) modalTitle.textContent = "Új hír";
      openModal();
    });
  }

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

  // Mentés
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      if (!form) return;

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

          // egyszerű megoldás: frissítjük az oldalt, hogy a lista is frissüljön
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
