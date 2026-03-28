/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("log-modal") as HTMLElement | null;
    const uaContainer = document.getElementById("log-ua");
    const ctxContainer = document.getElementById("log-context");
    const closeBtns = document.querySelectorAll(".modal-close, #log-close-btn");
    
    const tbody = document.getElementById("logs-tbody");
    const searchInput = document.getElementById("log-search") as HTMLInputElement | null;

    let debounceTimer: number;

    // Fő funkció: Táblázat adatainak lekérése API-ból
    async function loadLogs(query: string = '') {
        if (!tbody) return;
        
        // Töltő képernyő (Loading)
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);"><span class="material-symbols-rounded spinning" style="font-size: 2rem;">refresh</span><br><br>Adatok betöltése...</td></tr>';

        try {
            const res = await fetch(`/admin/api/get_logs.php?q=${encodeURIComponent(query)}`);
            const json = await res.json();

            if (json.status === 'success') {
                tbody.innerHTML = ''; // Töröljük a régi táblázatot
                
                if (json.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">Nincs a keresésnek megfelelő találat a naplóban.</td></tr>';
                    return;
                }

                // Generáljuk az új sorokat
                json.data.forEach((log: any) => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover-row log-row';
                    
                    // Biztonságos Dátum formázás JS-ben
                    const dateObj = new Date(log.created_at);
                    const formattedDate = !isNaN(dateObj.getTime()) 
                        ? dateObj.toISOString().replace('T', ' ').substring(0, 19)
                        : log.created_at;

                    const paddedId = String(log.id).padStart(4, '0');
                    const username = log.username || 'Rendszer';
                    const headUser = log.username ? log.username : 'MHF_Steve';

                    tr.innerHTML = `
                        <td class="td-id">#${paddedId}</td>
                        <td>
                            <div class="player-cell">
                                <img src="https://minotar.net/helm/${headUser}/24.png" class="player-head">
                                <strong>${username}</strong>
                            </div>
                        </td>
                        <td style="color: #cbd5e1;">${log.action}</td>
                        <td class="td-muted">${log.ip_address || '-'}</td>
                        <td class="td-muted">${formattedDate}</td>
                        <td>
                            <span class="status-badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted); cursor: pointer;" title="${log.user_agent || ''}">
                                <span class="material-symbols-rounded" style="font-size: 1.1rem;">devices</span> Info
                            </span>
                        </td>
                    `;

                    // Gombkattintás a Modal megnyitásához
                    tr.querySelector('.status-badge')?.addEventListener("click", () => openModal(log.user_agent, log.context));
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--admin-red); padding: 2rem;">Hiba: ${json.message}</td></tr>`;
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--admin-red); padding: 2rem;">Hálózati hiba történt az adatok lekérésekor.</td></tr>`;
        }
    }

    // Modal kezelő függvény
    function openModal(ua: string, contextRaw: string) {
        if (!modal || !uaContainer || !ctxContainer) return;
        uaContainer.textContent = ua || "Ismeretlen böngésző/eszköz";
        
        try {
            const parsed = JSON.parse(contextRaw || "{}");
            ctxContainer.textContent = JSON.stringify(parsed, null, 4);
        } catch (e) {
            ctxContainer.textContent = contextRaw || "{}";
        }
        modal.classList.add("open");
    }

    const closeModal = () => { if (modal) modal.classList.remove("open"); };

    closeBtns.forEach(btn => btn.addEventListener("click", (e) => { e.preventDefault(); closeModal(); }));
    if (modal) modal.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") closeModal(); });

    // ÉLŐ KERESÉS ESEMÉNY (Amikor a felhasználó gépel)
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer); // Töröljük a korábbi időzítőt
            const target = e.target as HTMLInputElement;
            debounceTimer = setTimeout(() => {
                loadLogs(target.value); // Fél másodperc csend után betöltjük!
            }, 300);
        });
    }

    // Amikor betölt az oldal, egyből le is húzzuk az alap adatokat
    loadLogs('');
});