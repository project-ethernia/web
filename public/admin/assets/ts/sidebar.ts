/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    // --- SIDEBAR LOGIKA ---
    const sidebar = document.getElementById('admin-sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const toggleIcon = toggleBtn?.querySelector('.material-symbols-rounded');

    if (sidebar && toggleBtn && toggleIcon) {
        const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
        
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            toggleIcon.textContent = 'menu';
        }

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            const currentlyCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebar_collapsed', currentlyCollapsed ? 'true' : 'false');
            toggleIcon.textContent = currentlyCollapsed ? 'menu' : 'menu_open';
        });
    }

    // --- GLOBÁLIS MODAL LOGIKA ---
    (window as any).ethConfirm = function(event: Event, message: string, url: string) {
        event.preventDefault(); // Megakadályozzuk az azonnali kattintást/link megnyitást

        const overlay = document.getElementById('eth-modal');
        const msgEl = document.getElementById('eth-modal-msg');
        const confirmBtn = document.getElementById('eth-modal-confirm') as HTMLAnchorElement;
        const cancelBtn = document.getElementById('eth-modal-cancel');

        if (overlay && msgEl && confirmBtn && cancelBtn) {
            msgEl.textContent = message;
            confirmBtn.href = url;
            overlay.classList.add('active');

            // Mégse gomb
            cancelBtn.onclick = function() {
                overlay.classList.remove('active');
            };

            // Overlay-re kattintás (háttér) is bezárja
            overlay.onclick = function(e) {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }
            };
        }
    };
});