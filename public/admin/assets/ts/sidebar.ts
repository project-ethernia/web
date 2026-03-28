document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('sidebar-toggle') as HTMLButtonElement | null;
    const sidebar = document.getElementById('admin-sidebar') as HTMLElement | null;
    const main = document.querySelector('.admin-main') as HTMLElement | null;

    if (toggleBtn && sidebar && main) {
        // Ellenőrizzük a böngészőből, hogy korábban be volt-e csukva
        const isCollapsed: boolean = localStorage.getItem('sidebarCollapsed') === 'true';
        const toggleIcon = toggleBtn.querySelector('.material-symbols-rounded') as HTMLElement | null;
        
        // Ha csukva volt az előző munkamenetben, akkor most is úgy nyitjuk meg
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            main.classList.add('collapsed');
            if (toggleIcon) toggleIcon.innerText = 'menu';
        }

        // Kattintás esemény
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('collapsed');
            
            const collapsedNow: boolean = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', String(collapsedNow)); // Állapot mentése szövegként
            
            // Ikon cseréje (menu vs menu_open)
            if (toggleIcon) {
                toggleIcon.innerText = collapsedNow ? 'menu' : 'menu_open';
            }
        });
    }
});