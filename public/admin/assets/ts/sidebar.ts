/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById('admin-sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const toggleIcon = toggleBtn?.querySelector('.material-symbols-rounded');

    if (sidebar && toggleBtn && toggleIcon) {
        // Oldaltöltéskor megnézzük, mi volt az utolsó állapot
        const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
        
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            toggleIcon.textContent = 'menu'; // Hamburger ikon, ha csukva van
        }

        // Gombnyomás esemény
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            
            const currentlyCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebar_collapsed', currentlyCollapsed ? 'true' : 'false');
            
            // Ikon cseréje
            toggleIcon.textContent = currentlyCollapsed ? 'menu' : 'menu_open';
        });
    }
});