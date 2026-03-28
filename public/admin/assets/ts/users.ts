/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.getElementById("users-tbody");
    const searchInput = document.getElementById("user-search") as HTMLInputElement | null;
    const profilePanel = document.getElementById("profile-panel");
    let debounceTimer: number;

    // 1. TÁBLÁZAT ÉS KERESÉS BETÖLTÉSE
    async function loadUsers(query: string = '') {
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-muted);"><span class="material-symbols-rounded spinning" style="font-size: 2rem;">refresh</span><br><br>Játékosok keresése...</td></tr>';

        try {
            const res = await fetch(`/admin/api/get_users.php?q=${encodeURIComponent(query)}`);
            const json = await res.json();

            if (json.status === 'success') {
                tbody.innerHTML = '';
                if (json.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">Nincs a keresésnek megfelelő játékos.</td></tr>';
                    return;
                }

                json.data.forEach((user: any) => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover-row';
                    const paddedId = String(user.id).padStart(4, '0');
                    
                    const dateObj = new Date(user.created_at);
                    const formattedDate = !isNaN(dateObj.getTime()) ? dateObj.toISOString().split('T')[0] : user.created_at;

                    tr.innerHTML = `
                        <td class="td-id">#${paddedId}</td>
                        <td>
                            <div class="player-cell">
                                <img src="https://minotar.net/helm/${user.username}/24.png" class="player-head">
                                <strong>${user.username}</strong>
                            </div>
                        </td>
                        <td class="td-muted">${formattedDate}</td>
                        <td>
                            <button class="btn-sm btn-open load-profile-btn" data-id="${user.id}">Megnyitás</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                // Eseménykezelők hozzáadása a friss gombokhoz
                document.querySelectorAll('.load-profile-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        // Kiemeljük az aktív sort
                        document.querySelectorAll('.log-row, .hover-row').forEach(r => r.classList.remove('active-row'));
                        const target = e.currentTarget as HTMLButtonElement;
                        target.closest('tr')?.classList.add('active-row');
                        
                        const id = target.getAttribute('data-id');
                        if (id) loadUserProfile(id);
                    });
                });

            } else {
                tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--admin-red); padding: 2rem;">Hiba: ${json.message}</td></tr>`;
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--admin-red); padding: 2rem;">Hálózati hiba történt.</td></tr>`;
        }
    }

    // 2. PROFIL PANEL DINAMIKUS BETÖLTÉSE
    async function loadUserProfile(id: string) {
        if (!profilePanel) return;
        profilePanel.innerHTML = '<div class="empty-profile"><span class="material-symbols-rounded spinning" style="font-size: 3rem;">refresh</span><p>Profil betöltése...</p></div>';

        try {
            const res = await fetch(`/admin/api/get_user_profile.php?id=${id}`);
            const json = await res.json();

            if (json.status === 'success') {
                const u = json.data;
                const statusBadge = u.status === 'Aktív' ? 'success' : 'error';
                
                profilePanel.innerHTML = `
                    <div class="panel-header" style="border-radius: 12px 12px 0 0;">
                        <h2><span class="material-symbols-rounded">person</span> Játékos Profilja</h2>
                    </div>
                    <div class="panel-body">
                        <div class="profile-header">
                            <img src="https://minotar.net/armor/bust/${u.username}/80.png" class="profile-avatar">
                            <div>
                                <h3 class="profile-name">${u.username}</h3>
                                <span class="profile-id">ID: #${String(u.id).padStart(4, '0')}</span>
                                <span class="badge ${statusBadge}" style="margin-left: 0.5rem;">${u.status}</span>
                            </div>
                        </div>
                        
                        <div class="profile-info-grid">
                            <div class="info-box">
                                <span class="info-label">Regisztráció ideje</span>
                                <span class="info-value">${u.created_at}</span>
                            </div>
                            <div class="info-box">
                                <span class="info-label">Rendelkezésre álló Ethernia Coin</span>
                                <span class="info-value" style="color: var(--admin-warning); font-weight: 800;">${u.coins} EC</span>
                            </div>
                            <div class="info-box">
                                <span class="info-label">Jelenlegi Rang</span>
                                <span class="info-value" style="color: var(--admin-info); font-weight: 800;">${u.rank}</span>
                            </div>
                        </div>

                        <hr class="control-divider">
                        <h4 style="color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; margin-bottom: 1rem;">Adminisztrátori Műveletek</h4>
                        
                        <div class="punishment-actions">
                            <button class="btn-punish" onclick="alert('A büntetési API hamarosan bekötésre kerül!')">
                                <span class="material-symbols-rounded">gavel</span>
                                <div>
                                    <strong>Kitiltás (Ban)</strong>
                                    <span>Játékos végleges vagy ideiglenes kitiltása</span>
                                </div>
                            </button>
                            <button class="btn-punish" onclick="alert('A büntetési API hamarosan bekötésre kerül!')">
                                <span class="material-symbols-rounded">volume_off</span>
                                <div>
                                    <strong>Némítás (Mute)</strong>
                                    <span>Chat használatának megvonása</span>
                                </div>
                            </button>
                        </div>
                    </div>
                `;
            } else {
                profilePanel.innerHTML = `<div class="empty-profile"><span class="material-symbols-rounded" style="color: var(--admin-red);">error</span><p>Hiba: ${json.message}</p></div>`;
            }
        } catch (err) {
            profilePanel.innerHTML = '<div class="empty-profile"><span class="material-symbols-rounded" style="color: var(--admin-red);">wifi_off</span><p>Hálózati hiba a profil betöltésekor.</p></div>';
        }
    }

    // Gépelés figyelése (Debounce)
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const target = e.target as HTMLInputElement;
            debounceTimer = setTimeout(() => {
                loadUsers(target.value);
            }, 300);
        });
    }

    // Alapértelmezett lista betöltése
    loadUsers('');
});