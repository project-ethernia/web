/// <reference lib="dom" />

class AdminToastManager {
    private container: HTMLDivElement;

    constructor() {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    }

    show(message: string, type: 'success' | 'error' | 'warning' | 'info' = 'info', duration: number = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons: Record<string, string> = { success: 'check_circle', error: 'error', warning: 'warning', info: 'info' };

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

    success(msg: string) { this.show(msg, 'success'); }
    error(msg: string) { this.show(msg, 'error'); }
    warning(msg: string) { this.show(msg, 'warning'); }
    info(msg: string) { this.show(msg, 'info'); }
}

const Toast = new AdminToastManager();
(window as any).Toast = Toast;

// ITT A BIZTOS VAGY EBBEN ABLAK LOGIKÁJA
function ethConfirm(e: Event, msg: string, url: string): void {
    e.preventDefault();
    if (confirm(msg)) window.location.href = url;
}
(window as any).ethConfirm = ethConfirm;

// CSRF API VÉDELEM
const originalFetch = window.fetch;
window.fetch = async (input: RequestInfo | URL, init?: RequestInit): Promise<Response> => {
    let config = init || {};
    if (!config.headers) config.headers = {};
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        if (config.headers instanceof Headers) {
            config.headers.append('X-CSRF-Token', csrfToken);
        } else {
            (config.headers as Record<string, string>)['X-CSRF-Token'] = csrfToken;
        }
    }
    return originalFetch(input, config);
};