"use strict";
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('sidebar-toggle');
    var sidebar = document.getElementById('admin-sidebar');
    var main = document.querySelector('.admin-main');
    if (toggleBtn && sidebar && main) {
        // Ellenőrizzük a böngészőből, hogy korábban be volt-e csukva
        var isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        var toggleIcon_1 = toggleBtn.querySelector('.material-symbols-rounded');
        // Ha csukva volt az előző munkamenetben, akkor most is úgy nyitjuk meg
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            main.classList.add('collapsed');
            if (toggleIcon_1)
                toggleIcon_1.innerText = 'menu';
        }
        // Kattintás esemény
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('collapsed');
            var collapsedNow = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', String(collapsedNow)); // Állapot mentése szövegként
            // Ikon cseréje (menu vs menu_open)
            if (toggleIcon_1) {
                toggleIcon_1.innerText = collapsedNow ? 'menu' : 'menu_open';
            }
        });
    }
});
