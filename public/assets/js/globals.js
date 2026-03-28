"use strict";
/// <reference lib="dom" />
class ToastManager {
    constructor() {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    }
    show(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const icons = { success: 'check_circle', error: 'error', warning: 'warning', info: 'info' };
        toast.innerHTML = `
            <span class="material-symbols-rounded">${icons[type]}</span>
            <span class="toast-message">${message}</span>
        `;
        this.container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
    success(msg) { this.show(msg, 'success'); }
    error(msg) { this.show(msg, 'error'); }
    warning(msg) { this.show(msg, 'warning'); }
    info(msg) { this.show(msg, 'info'); }
}
const Toast = new ToastManager();
window.Toast = Toast;
document.addEventListener("DOMContentLoaded", () => {
    const timerEl = document.getElementById('countdown-timer');
    if (timerEl) {
        let seconds = parseInt(timerEl.getAttribute('data-seconds') || '3600', 10);
        const updateTimer = () => {
            if (seconds > 0)
                seconds--;
            let m = Math.floor(seconds / 60).toString().padStart(2, '0');
            let s = (seconds % 60).toString().padStart(2, '0');
            timerEl.innerText = `${m}:${s}`;
            if (seconds <= 0)
                window.location.href = '/auth/logout.php';
        };
        updateTimer();
        setInterval(updateTimer, 1000);
    }
});
function ethConfirm(e, msg, url) {
    e.preventDefault();
    if (confirm(msg))
        window.location.href = url;
}
window.ethConfirm = ethConfirm;
const originalFetch = window.fetch;
window.fetch = async (input, init) => {
    var _a;
    let config = init || {};
    if (!config.headers)
        config.headers = {};
    const csrfToken = (_a = document.querySelector('meta[name="csrf-token"]')) === null || _a === void 0 ? void 0 : _a.getAttribute('content');
    if (csrfToken) {
        if (config.headers instanceof Headers) {
            config.headers.append('X-CSRF-Token', csrfToken);
        }
        else {
            config.headers['X-CSRF-Token'] = csrfToken;
        }
    }
    return originalFetch(input, config);
};
