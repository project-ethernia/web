document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("news-modal") as HTMLElement | null;
  const form = document.getElementById("news-form") as HTMLFormElement | null;
  const errorEl = document.getElementById("news-error") as HTMLElement | null;

  const closeBtn = modal?.querySelector<HTMLElement>(".modal-close") ?? null;
  const backdrop = modal?.querySelector<HTMLElement>(".modal-backdrop") ?? null;
  const cancelBtn = document.getElementById("news-cancel") as HTMLElement | null;

  const addBtn = document.getElementById("btn-add-news") as HTMLElement | null;
  const addBtnEmpty = document.getElementById("btn-add-news-empty") as HTMLElement | null;
  const modalTitle = document.getElementById("news-modal-title") as HTMLElement | null;

  const idInput = document.getElementById("news-id") as HTMLInputElement | null;
  const titleInput = document.getElementById("news-title") as HTMLInputElement | null;
  const tagSelect = document.getElementById("news-tag") as HTMLSelectElement | null;
  const shortInput = document.getElementById("news-short") as HTMLTextAreaElement | null;
  const fullInput = document.getElementById("news-full") as HTMLTextAreaElement | null;
  const orderInput = document.getElementById("news-order") as HTMLInputElement | null;
  const visibleInput = document.getElementById("news-visible") as HTMLInputElement | null;
  const metaAuthor = document.getElementById("news-meta-author") as HTMLElement | null;
  const metaDate = document.getElementById("news-meta-date") as HTMLElement | null;

  const openModal = (): void => {
    if (!modal) return;
    modal.classList.add("open");
  };

  const closeModal = (): void => {
    if (!modal) return;
    modal.classList.remove("open");
    if (errorEl) {
      errorEl.hidden = true;
      errorEl.textContent = "";
    }
  };

  const resetForm = (): void => {
    if (!form) return;
    form.reset();
    if (idInput) idInput.value = "";
    if (orderInput) orderInput.value = "0";
    if (visibleInput) visibleInput.checked = true;
    if (metaAuthor) metaAuthor.textContent = "Mentés után";
    if (metaDate) metaDate.textContent = "Mentés után";
  };

  const fillFormFromRow = (tr: HTMLTableRowElement): void => {
    if (idInput) idInput.value = tr.dataset.id || "";
    if (titleInput) titleInput.value = tr.dataset.title || "";
    if (tagSelect) tagSelect.value = tr.dataset.tag || "Info";
    if (shortInput) shortInput.value = tr.dataset.short_text || "";
    if (fullInput) fullInput.value = tr.dataset.full_text || "";
    if (orderInput) orderInput.value = tr.dataset.order_index || "0";
    if (visibleInput) visibleInput.checked = tr.dataset.is_visible === "1";
    if (metaAuthor) metaAuthor.textContent = tr.dataset.author || "Ismeretlen";
    if (metaDate) metaDate.textContent = tr.dataset.date_display || "-";
  };

  const handleNewClick = (): void => {
    resetForm();
    if (modalTitle) modalTitle.textContent = "Új hír";
    openModal();
  };

  if (addBtn) addBtn.addEventListener("click", handleNewClick);
  if (addBtnEmpty) addBtnEmpty.addEventListener("click", handleNewClick);

  document.querySelectorAll<HTMLElement>(".btn-edit").forEach((btn) => {
    btn.addEventListener("click", () => {
      const tr = btn.closest("tr") as HTMLTableRowElement | null;
      if (!tr) return;
      resetForm();
      fillFormFromRow(tr);
      if (modalTitle) modalTitle.textContent = "Hír szerkesztése";
      openModal();
    });
  });

  document.querySelectorAll<HTMLElement>(".btn-delete").forEach((btn) => {
    btn.addEventListener("click", () => {
      const tr = btn.closest("tr") as HTMLTableRowElement | null;
      if (!tr) return;

      const id = tr.dataset.id;
      const title = tr.dataset.title || id || "";

      if (!id) return;

      if (!window.confirm("Biztosan törlöd ezt a hírt?\n\n" + title)) {
        return;
      }

      const formData = new FormData();
      formData.append("action", "delete");
      formData.append("id", id);

      fetch("news.php", {
        method: "POST",
        body: formData
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            window.alert(data.error || "Ismeretlen hiba történt törlés közben.");
            return;
          }
          tr.remove();
        })
        .catch((err) => {
          console.error(err);
          window.alert("Hálózati hiba történt a törlés során.");
        });
    });
  });

  document.querySelectorAll<HTMLElement>(".visibility-toggle").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      if (!id) return;

      const current = btn.dataset.visible === "1";
      const next = current ? 0 : 1;

      const formData = new FormData();
      formData.append("action", "toggle_visible");
      formData.append("id", id);
      formData.append("is_visible", String(next));

      fetch("news.php", {
        method: "POST",
        body: formData
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            window.alert(data.error || "Hiba a láthatóság állításakor.");
            return;
          }

          btn.dataset.visible = String(next);
          btn.setAttribute("aria-pressed", next ? "true" : "false");
          btn.classList.toggle("is-on", !!next);
          btn.classList.toggle("is-off", !next);
          btn.title = next
            ? "Látható – kattints az elrejtéshez"
            : "Rejtett – kattints a megjelenítéshez";

          const tr = btn.closest("tr") as HTMLTableRowElement | null;
          if (tr) {
            tr.dataset.is_visible = String(next);
          }
        })
        .catch((err) => {
          console.error(err);
          window.alert("Hálózati hiba történt a láthatóság állításakor.");
        });
    });
  });

  const closers: (HTMLElement | null)[] = [closeBtn, backdrop, cancelBtn];
  closers.forEach((el) => {
    if (!el) return;
    el.addEventListener("click", (e) => {
      e.preventDefault();
      closeModal();
    });
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal();
    }
  });

  if (form) {
    form.addEventListener("submit", (e: Event) => {
      e.preventDefault();
      if (errorEl) {
        errorEl.hidden = true;
        errorEl.textContent = "";
      }

      const formData = new FormData(form);

      fetch("news.php", {
        method: "POST",
        body: formData
      })
        .then((res) => res.json())
        .then((data) => {
          if (!data.ok) {
            if (errorEl) {
              errorEl.textContent =
                data.error || "Ismeretlen hiba történt mentés közben.";
              errorEl.hidden = false;
            } else {
              window.alert(data.error || "Ismeretlen hiba történt mentés közben.");
            }
            return;
          }

          window.location.reload();
        })
        .catch((err) => {
          console.error(err);
          if (errorEl) {
            errorEl.textContent = "Hálózati hiba történt a mentés során.";
            errorEl.hidden = false;
          } else {
            window.alert("Hálózati hiba történt a mentés során.");
          }
        });
    });
  }
});
