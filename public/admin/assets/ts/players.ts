/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {

    const API = '/admin/rcon_api.php';

    // ── Típusok ────────────────────────────────────────────────────────────
    interface StatusData {
        online: boolean;
        player_count: number;
        max_players: number;
        players: string[];
    }
    interface ApiResponse {
        ok: boolean;
        data?: StatusData;
        players?: string[];
        response?: string;
        error?: string;
    }

    // ── Elemek ─────────────────────────────────────────────────────────────
    const statusDot    = document.getElementById('status-dot') as HTMLElement | null;
    const statusLabel  = document.getElementById('status-label') as HTMLElement | null;
    const statusCount  = document.getElementById('status-players') as HTMLElement | null;
    const onlineGrid   = document.getElementById('online-players-grid') as HTMLElement | null;
    const rconInput    = document.getElementById('rcon-input') as HTMLInputElement | null;
    const rconSendBtn  = document.getElementById('rcon-send-btn') as HTMLButtonElement | null;
    const rconResponse = document.getElementById('rcon-response') as HTMLElement | null;
    const refreshBtn   = document.getElementById('btn-refresh-status') as HTMLButtonElement | null;

    // ── Segédfüggvények ────────────────────────────────────────────────────
    function showResult(el: HTMLElement | null, res: ApiResponse): void {
        if (!el) return;
        el.style.display = 'block';
        el.className = 'rcon-inline-result ' + (res.ok ? 'success' : 'error');
        el.textContent = res.response ?? res.error ?? '–';
    }

    async function rconPost(action: string, extra: Record<string, string> = {}): Promise<ApiResponse> {
        const body = new FormData();
        Object.entries(extra).forEach(([k, v]) => body.append(k, v));
        const res = await fetch(`${API}?action=${action}`, { method: 'POST', body });
        return res.json();
    }

    // ── Szerver státusz lekérése ───────────────────────────────────────────
    async function fetchStatus(): Promise<void> {
        if (statusDot)   statusDot.className = 'status-dot';
        if (statusLabel) statusLabel.textContent = 'Kapcsolódás...';

        try {
            const res: ApiResponse = await fetch(`${API}?action=status`).then(r => r.json());

            if (res.ok && res.data?.online) {
                if (statusDot)   statusDot.className = 'status-dot online';
                if (statusLabel) statusLabel.textContent = 'Szerver Online';
                if (statusCount) statusCount.textContent = `${res.data.player_count} / ${res.data.max_players}`;
                renderOnlinePlayers(res.data.players);
            } else {
                if (statusDot)   statusDot.className = 'status-dot offline';
                if (statusLabel) statusLabel.textContent = 'Szerver Offline';
                if (statusCount) statusCount.textContent = '0 / –';
                if (onlineGrid)  onlineGrid.innerHTML = `
                    <div class="online-empty">
                        <span class="material-symbols-rounded">wifi_off</span>
                        <p>A szerver nem elérhető vagy az RCON le van tiltva.</p>
                    </div>`;
            }
        } catch {
            if (statusDot)   statusDot.className = 'status-dot offline';
            if (statusLabel) statusLabel.textContent = 'RCON Hiba';
            if (onlineGrid)  onlineGrid.innerHTML = `
                <div class="online-empty">
                    <span class="material-symbols-rounded">error</span>
                    <p>Nem sikerült kapcsolódni az RCON API-hoz.</p>
                </div>`;
        }
    }

    // ── Online játékosok kártyái ───────────────────────────────────────────
    function renderOnlinePlayers(players: string[]): void {
        if (!onlineGrid) return;

        if (players.length === 0) {
            onlineGrid.innerHTML = `
                <div class="online-empty">
                    <span class="material-symbols-rounded">person_off</span>
                    <p>Jelenleg nincs online játékos.</p>
                </div>`;
            return;
        }

        onlineGrid.innerHTML = players.map(name => `
            <div class="online-player-card glass">
                <img src="https://minotar.net/helm/${encodeURIComponent(name)}/48.png"
                     alt="${name}" class="online-avatar"
                     onerror="this.src='https://minotar.net/helm/Steve/48.png'">
                <span class="online-name">${name}</span>
                <div class="online-actions">
                    <button class="btn-sm btn-kick" data-player="${name}">Kick</button>
                    <button class="btn-sm btn-msg"  data-player="${name}">Msg</button>
                </div>
            </div>
        `).join('');

        // Kártyákon lévő gombok eseményei
        onlineGrid.querySelectorAll<HTMLButtonElement>('.btn-kick').forEach(btn => {
            btn.addEventListener('click', () => {
                const player = btn.dataset.player!;
                const reason = prompt(`Kirúgás oka (${player}):`) ?? 'Admin kirúgta.';
                rconPost('kick', { username: player, reason })
                    .then(res => {
                        alert(res.response ?? res.error ?? '–');
                        fetchStatus();
                    });
            });
        });

        onlineGrid.querySelectorAll<HTMLButtonElement>('.btn-msg').forEach(btn => {
            btn.addEventListener('click', () => {
                const player = btn.dataset.player!;
                const msg = prompt(`Üzenet ${player} számára:`);
                if (!msg) return;
                sendRconCommand(`msg ${player} ${msg}`);
            });
        });
    }

    // ── RCON parancs küldése ───────────────────────────────────────────────
    async function sendRconCommand(cmd: string, resultEl?: HTMLElement | null): Promise<void> {
        const out = resultEl ?? rconResponse;
        if (!out) return;

        out.style.display = 'block';
        out.className = 'rcon-response loading';
        out.textContent = 'Küldés...';

        try {
            const body = new FormData();
            body.append('command', cmd);
            const res: ApiResponse = await fetch(`${API}?action=command`, { method: 'POST', body }).then(r => r.json());
            out.className   = 'rcon-response ' + (res.ok ? 'success' : 'error');
            out.textContent = res.response ?? res.error ?? '–';
        } catch {
            out.className   = 'rcon-response error';
            out.textContent = 'Kapcsolati hiba – az RCON API nem válaszolt.';
        }
    }

    // ── RCON input gomb + Enter ────────────────────────────────────────────
    rconSendBtn?.addEventListener('click', () => {
        const cmd = rconInput?.value.trim();
        if (cmd) {
            sendRconCommand(cmd);
            if (rconInput) rconInput.value = '';
        }
    });

    rconInput?.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            rconSendBtn?.click();
        }
    });

    // ── Refresh gomb ──────────────────────────────────────────────────────
    refreshBtn?.addEventListener('click', () => {
        if (refreshBtn) {
            const icon = refreshBtn.querySelector('.material-symbols-rounded');
            if (icon) icon.classList.add('spinning');
            fetchStatus().finally(() => icon?.classList.remove('spinning'));
        }
    });

    // ── Profilpanel RCON gombok (ban/unban/kick/msg) ───────────────────────
    document.querySelectorAll<HTMLElement>('.rcon-actions').forEach(panel => {
        const username = panel.dataset.username!;
        const resultEl = panel.closest('.panel-body')
            ?.querySelector<HTMLElement>('[id^="rcon-result-"]') ?? null;

        panel.querySelectorAll<HTMLButtonElement>('[data-action]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const action = btn.dataset.action!;

                if (action === 'kick') {
                    const reason = prompt(`Kirúgás oka (${username}):`) ?? 'Admin kirúgta.';
                    const res = await rconPost('kick', { username, reason });
                    showResult(resultEl, res);

                } else if (action === 'ban') {
                    if (!confirm(`Biztosan kitiltod: ${username}?\n(Minecraft szerver + weboldal egyszerre)`)) return;
                    const reason = prompt('Tiltás oka:') ?? 'Admin döntés alapján.';
                    const res = await rconPost('ban', { username, reason });
                    showResult(resultEl, res);
                    if (res.ok) setTimeout(() => location.reload(), 1500);

                } else if (action === 'unban') {
                    if (!confirm(`Feloldod ${username} tiltását? (MC + web)`)) return;
                    const res = await rconPost('unban', { username });
                    showResult(resultEl, res);
                    if (res.ok) setTimeout(() => location.reload(), 1500);

                } else if (action === 'msg') {
                    const msg = prompt(`Privát üzenet ${username} számára:`);
                    if (!msg) return;
                    await sendRconCommand(`msg ${username} ${msg}`, resultEl);
                }
            });
        });
    });

    // ── Indulás + auto-refresh 30 másodpercenként ─────────────────────────
    fetchStatus();
    setInterval(fetchStatus, 30_000);

});