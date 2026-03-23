/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("log-modal") as HTMLElement | null;
    const uaContainer = document.getElementById("log-ua");
    const ctxContainer = document.getElementById("log-context");
    const closeBtns = document.querySelectorAll(".modal-close, #log-close-btn");
    const logRows = document.querySelectorAll(".log-row");

    if (!modal || !uaContainer || !ctxContainer) return;

    logRows.forEach(row => {
        row.addEventListener("click", () => {
            const ua = row.getAttribute("data-ua") || "Ismeretlen böngésző";
            const contextRaw = row.getAttribute("data-context") || "{}";

            uaContainer.textContent = ua;
            
            try {
                // Megpróbáljuk szépen formázni a JSON-t
                const parsed = JSON.parse(contextRaw);
                ctxContainer.textContent = JSON.stringify(parsed, null, 4);
            } catch (e) {
                ctxContainer.textContent = contextRaw;
            }

            modal.classList.add("open");
        });
    });

    const closeModal = () => {
        modal.classList.remove("open");
    };

    closeBtns.forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            closeModal();
        });
    });

    // Ha a sötét háttérre kattint
    modal.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
    });

    // ESC gombra záródjon
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeModal();
    });
});