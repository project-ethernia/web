/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    // 1. DOM elemek lekérése típusokkal
    const modal = document.getElementById("news-modal") as HTMLElement | null;
    const form = document.getElementById("news-form") as HTMLFormElement | null;
    const titleInput = document.getElementById("news-title") as HTMLInputElement | null;
    const contentInput = document.getElementById("news-content") as HTMLTextAreaElement | null;
    const categoryInput = document.getElementById("news-category") as HTMLSelectElement | null;
    const visibleInput = document.getElementById("news-visible") as HTMLInputElement | null;
    const actionInput = document.getElementById("news-action") as HTMLInputElement | null;
    const idInput = document.getElementById("news-id") as HTMLInputElement | null;
    const modalTitle = document.getElementById("news-modal-title") as HTMLElement | null;
    const submitBtn = document.getElementById("news-submit-btn") as HTMLButtonElement | null;
    const errorText = document.getElementById("news-error") as HTMLElement | null;

    // Ha valami nagyon hiányzik, ne fusson le hibára
    if (!modal || !form || !titleInput || !contentInput || !categoryInput || !visibleInput || !actionInput || !idInput || !modalTitle || !submitBtn || !errorText) {
        console.warn("A hírek kezelőjének egyes DOM elemei nem találhatóak.");
        return;
    }

    // Modal kezelő függvények
    const openModal = () => {
        errorText.hidden = true;
        modal.classList.add("open");
    };

    const closeModal = () => {
        modal.classList.remove("open");
        form.reset();
    };

    // --- Új hír nyitása ---
    document.getElementById("btn-add-news")?.addEventListener("click", () => {
        form.reset();
        actionInput.value = "add";
        idInput.value = "";
        modalTitle.textContent = "Új hír írása";
        submitBtn.textContent = "Közzététel";
        openModal();
    });

    // Ha üres az oldal és arra a gombra nyom
    document.getElementById("btn-add-news-empty")?.addEventListener("click", () => {
        document.getElementById("btn-add-news")?.click();
    });

    // --- Szerkesztés nyitása ---
    document.querySelectorAll(".btn-edit-news").forEach(btn => {
        btn.addEventListener("click", (e: Event) => {
            const target = e.currentTarget as HTMLElement;
            const row = target.closest("tr") as HTMLTableRowElement | null;
            
            if (!row) return;

            actionInput.value = "edit";
            idInput.value = row.dataset.id || "";
            titleInput.value = row.dataset.title || "";
            contentInput.value = row.dataset.content || "";
            categoryInput.value = row.dataset.category || "INFO";
            visibleInput.checked = row.dataset.visible === "1";
            
            modalTitle.textContent = "Hír szerkesztése";
            submitBtn.textContent = "Mentés";
            openModal();
        });
    });

    // --- Bezárás gombok ---
    document.querySelector(".modal-close")?.addEventListener("click", closeModal);
    document.getElementById("news-cancel")?.addEventListener("click", closeModal);

    // ESC gombra is záródjon be
    document.addEventListener("keydown", (e: KeyboardEvent) => {
        if (e.key === "Escape" && modal.classList.contains("open")) {
            closeModal();
        }
    });

    // --- Form beküldése (Hozzáadás/Szerkesztés) ---
    form.addEventListener("submit", (e: SubmitEvent) => {
        e.preventDefault();
        const formData = new FormData(form);

        fetch("/admin/news.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                location.reload();
            } else {
                errorText.textContent = data.error || "Hiba történt a mentés során.";
                errorText.hidden = false;
            }
        })
        .catch(() => {
            errorText.textContent = "Hálózati hiba történt a kommunikáció során.";
            errorText.hidden = false;
        });
    });

    // --- Láthatóság Toggle (A zöld/szürke csúszka) ---
    document.querySelectorAll(".toggle-visibility").forEach(btn => {
        btn.addEventListener("click", (e: Event) => {
            const target = e.currentTarget as HTMLButtonElement;
            const id = target.dataset.id || "0";
            const currentVisible = target.dataset.visible === "1";
            const newVisible = currentVisible ? 0 : 1;

            const formData = new FormData();
            formData.append("action", "toggle_visible");
            formData.append("id", id);
            formData.append("is_visible", newVisible.toString());

            fetch("/admin/news.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    target.dataset.visible = newVisible.toString();
                    target.classList.toggle("active", newVisible === 1);
                } else {
                    alert(data.error || "Hiba történt a módosítás során!");
                }
            })
            .catch(console.error);
        });
    });

    // --- Törlés ---
    document.querySelectorAll(".btn-delete-news").forEach(btn => {
        btn.addEventListener("click", (e: Event) => {
            if (!confirm("Biztosan törölni szeretnéd ezt a hírt? Ezt nem lehet visszavonni!")) return;
            
            const target = e.currentTarget as HTMLButtonElement;
            const id = target.dataset.id || "0";
            
            const formData = new FormData();
            formData.append("action", "delete");
            formData.append("id", id);

            fetch("/admin/news.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    location.reload();
                } else {
                    alert(data.error || "Hiba történt a törlés során!");
                }
            })
            .catch(console.error);
        });
    });
});