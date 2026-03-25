"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    // --- SIDEBAR LOGIKA ---
    var sidebar = document.getElementById('admin-sidebar');
    var toggleBtn = document.getElementById('sidebar-toggle');
    var toggleIcon = toggleBtn === null || toggleBtn === void 0 ? void 0 : toggleBtn.querySelector('.material-symbols-rounded');
    if (sidebar && toggleBtn && toggleIcon) {
        var isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            toggleIcon.textContent = 'menu';
        }
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            var currentlyCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebar_collapsed', currentlyCollapsed ? 'true' : 'false');
            toggleIcon.textContent = currentlyCollapsed ? 'menu' : 'menu_open';
        });
    }
    // --- GLOBÁLIS MODAL LOGIKA ---
    window.ethConfirm = function (event, message, url) {
        event.preventDefault(); // Megakadályozzuk az azonnali kattintást/link megnyitást
        var overlay = document.getElementById('eth-modal');
        var msgEl = document.getElementById('eth-modal-msg');
        var confirmBtn = document.getElementById('eth-modal-confirm');
        var cancelBtn = document.getElementById('eth-modal-cancel');
        if (overlay && msgEl && confirmBtn && cancelBtn) {
            msgEl.textContent = message;
            confirmBtn.href = url;
            overlay.classList.add('active');
            // Mégse gomb
            cancelBtn.onclick = function () {
                overlay.classList.remove('active');
            };
            // Overlay-re kattintás (háttér) is bezárja
            overlay.onclick = function (e) {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }
            };
        }
    };
});
