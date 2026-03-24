"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", () => {
    // 1. Évszám beállítása a footerben
    const yearEl = document.getElementById('year');
    if (yearEl)
        yearEl.textContent = new Date().getFullYear().toString();
    // 2. IP Másolás funkció és Toast
    const toastContainer = document.getElementById('toast-container');
    const showToast = (message) => {
        if (!toastContainer)
            return;
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };
    document.querySelectorAll('.copy-ip').forEach(el => {
        el.addEventListener('click', () => {
            const ip = el.dataset.ip || 'play.ethernia.hu';
            navigator.clipboard.writeText(ip).then(() => {
                showToast(`Szerver IP (${ip}) másolva a vágólapra!`);
            });
        });
    });
    // 3. Modal kezelés a hírekhez
    const modal = document.getElementById('news-modal');
    const modalInner = document.getElementById('modal-content-inner');
    const modalCloseBtn = document.querySelector('.modal-close');
    if (modal && modalInner && modalCloseBtn) {
        document.querySelectorAll('.news-card').forEach(card => {
            card.addEventListener('click', () => {
                var _a, _b, _c;
                const fullText = card.dataset.full || '';
                const title = ((_a = card.querySelector('.news-title')) === null || _a === void 0 ? void 0 : _a.textContent) || '';
                const date = ((_b = card.querySelector('.date')) === null || _b === void 0 ? void 0 : _b.textContent) || '';
                const badgeHtml = ((_c = card.querySelector('.badge')) === null || _c === void 0 ? void 0 : _c.outerHTML) || '';
                modalInner.innerHTML = `
                    <div style="margin-bottom: 1rem;">${badgeHtml} <span style="color:#94a3b8; font-size:0.85rem; margin-left:10px;">${date}</span></div>
                    <h2 style="font-size: 1.8rem; margin-bottom: 1rem; color:#fff;">${title}</h2>
                    <div style="color: #cbd5e1; line-height: 1.7; font-size: 0.95rem;">${fullText}</div>
                `;
                modal.classList.add('open');
            });
        });
        modalCloseBtn.addEventListener('click', () => modal.classList.remove('open'));
        modal.addEventListener('click', (e) => {
            if (e.target === modal)
                modal.classList.remove('open');
        });
    }
    // 4. Session Timeout Visszaszámláló
    const timerEl = document.getElementById('countdown-timer');
    if (timerEl) {
        // A PHP-ből kapott hátralévő másodpercek
        let seconds = parseInt(timerEl.dataset.seconds || '1800', 10);
        const updateTimer = () => {
            var _a;
            if (seconds <= 0) {
                // Ha lejárt, azonnali kiléptetés
                window.location.href = '/auth/logout.php?error=timeout';
                return;
            }
            // Másodperc konvertálása MM:SS formátumba
            const m = Math.floor(seconds / 60).toString().padStart(2, '0');
            const s = (seconds % 60).toString().padStart(2, '0');
            timerEl.textContent = `${m}:${s}`;
            // Ha kevesebb mint 1 perc van hátra, kezdjen el vörösen villogni a doboz
            if (seconds <= 60) {
                (_a = timerEl.parentElement) === null || _a === void 0 ? void 0 : _a.classList.add('danger-pulse');
            }
            seconds--;
        };
        // Azonnali frissítés, majd 1 másodperces időzítő
        updateTimer();
        setInterval(updateTimer, 1000);
    }
});
