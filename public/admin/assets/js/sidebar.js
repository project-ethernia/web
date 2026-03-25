"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    var sidebar = document.getElementById('admin-sidebar');
    var toggleBtn = document.getElementById('sidebar-toggle');
    var toggleIcon = toggleBtn === null || toggleBtn === void 0 ? void 0 : toggleBtn.querySelector('.material-symbols-rounded');
    if (sidebar && toggleBtn && toggleIcon) {
        // Oldaltöltéskor megnézzük, mi volt az utolsó állapot
        var isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            toggleIcon.textContent = 'menu'; // Hamburger ikon, ha csukva van
        }
        // Gombnyomás esemény
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            var currentlyCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebar_collapsed', currentlyCollapsed ? 'true' : 'false');
            // Ikon cseréje
            toggleIcon.textContent = currentlyCollapsed ? 'menu' : 'menu_open';
        });
    }
});
