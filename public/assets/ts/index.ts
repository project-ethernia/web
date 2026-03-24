/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    // Évszám beállítása a footerben
    const yearEl = document.getElementById('year');
    if (yearEl) yearEl.textContent = new Date().getFullYear().toString();

    // IP Másolás funkció és Toast
    const toastContainer = document.getElementById('toast-container');
    const showToast = (message: string) => {
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        toastContainer.appendChild(toast);
        
        // Animációk miatt 3 mp múlva töröljük
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    document.querySelectorAll('.copy-ip').forEach(el => {
        el.addEventListener('click', () => {
            const ip = (el as HTMLElement).dataset.ip || 'play.ethernia.hu';
            navigator.clipboard.writeText(ip).then(() => {
                showToast(`Szerver IP (${ip}) másolva a vágólapra!`);
            });
        });
    });

    // Modal kezelés a hírekhez
    const modal = document.getElementById('news-modal');
    const modalInner = document.getElementById('modal-content-inner');
    const modalCloseBtn = document.querySelector('.modal-close');

    if (modal && modalInner && modalCloseBtn) {
        document.querySelectorAll('.news-card').forEach(card => {
            card.addEventListener('click', () => {
                const fullText = (card as HTMLElement).dataset.full || '';
                const title = card.querySelector('.news-title')?.textContent || '';
                const date = card.querySelector('.date')?.textContent || '';
                const badgeHtml = card.querySelector('.badge')?.outerHTML || '';
                
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
            if (e.target === modal) modal.classList.remove('open');
        });
    }

    // Ide jöhet a Minecraft/Discord fetch API logika (ugyanaz maradhat, ami volt)
    // fetch('https://api.mcsrvstat.us/2/play.ethernia.hu')... stb.
});