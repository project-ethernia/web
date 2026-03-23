/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("log-modal") as HTMLElement | null;
    const rows = document.querySelectorAll(".log-row") as NodeListOf<HTMLElement>;
    const uaContainer = document.getElementById("log-ua");
    const ctxContainer = document.getElementById("log-context");
    const closeBtn = modal?.querySelector(".modal-close");
    const closeBtnFooter = document.getElementById("log-close-btn");

    if (!modal || !uaContainer || !ctxContainer) return;

    rows.forEach(row => {
        row.addEventListener("click", () => {
            const ua = row.getAttribute("data-ua") || "Nincs adat";
            const context = row.getAttribute("data-context") || "{}";

            uaContainer.textContent = ua;
            
            try {
                const parsed = JSON.parse(context);
                ctxContainer.textContent = JSON.stringify(parsed, null, 4);
            } catch (e) {
                ctxContainer.textContent = context;
            }

            modal.classList.add("open");
        });
    });

    const closeModal = () => modal.classList.remove("open");

    closeBtn?.addEventListener("click", closeModal);
    closeBtnFooter?.addEventListener("click", closeModal);
    
    modal.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeModal();
    });
});