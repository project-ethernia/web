// public/admin/assets/js/players.ts

const API = '/admin/rcon_api.php';

// ── Típusok ──────────────────────────────────────────────────────────────
interface StatusResponse {
    ok: boolean;
    data?: { online: boolean; player_count: number; max_players: number; players: string[] };
}
interface CommandResponse { ok: boolean; response?: string; error?: string; }

// ── Státusz lekérés ──────────────────────────────────────────────────────
async function fetchStatus(): Promise<void> {
    const dot   = document.getElementById('status-dot')!;
    const label = document.getElementById('status-label')!;
    const count = document.getElementById('status-players')!;
    const grid  = document.getElementById('online-players-grid')!;

    try {
        const res: StatusResponse = await fetch(`${API}?action=status`).then(r => r.json());

        if (res.ok && res.data?.online) {
            dot.className   = 'status-dot online';
            label.textContent = 'Szerver Online';
            count.textContent = `${res.data.player_count} / ${res.data.max_players}`;
            renderOnlinePlayers(res.data.players);
        } else {
            dot.className     = 'status-dot offline';
            label.textContent = 'Szerver Offline';
            count.textContent = '0 / –';
            grid.innerHTML    = '<div class="online-empty"><span class="material-symbols-rounded">wifi_off</span><p>A szerver nem elérhető.</p></div>';
        }
    } catch {
        dot.className     = 'status-dot offline';
        label.textContent = 'RCON Hiba';
    }
}

function renderOnlinePlayers(players: string[]): void {
    const grid = document.getElementById('online-players-grid')!;

    if (players.length === 0) {
        grid.innerHTML = '<div class="online-empty"><span class="material-symbols-rounded">person_off</span><p>Jelenleg nincs online játékos.</p></div>';
        return;
    }

    grid.innerHTML = players.map(name => `
        <div class="online-player-card glass">
            <img src="https://minotar.net/helm/${encodeURIComponent(name)}/48.png"
                 alt="${name}" class="online-avatar">
            <span class="online-name">${name}</span>
            <div class="online-actions">
                <button class="btn-sm btn-kick"
                        onclick="quickAction('kick','${name}')">Kick</button>
                <button class="btn-sm btn-msg"
                        onclick="quickMsg('${name}')">Msg</button>
            </div>
        </div>
    `).join('');
}

// ── RCON parancs input ────────────────────────────────────────────────────
async function sendRconCommand(cmd: string, resultEl?: HTMLElement | null): Promise<void> {
    const out = resultEl ?? document.getElementById('rcon-response')!;
    out.style.display = 'block';
    out.className = 'rcon-response loading';
    out.textContent = 'Küldés...';

    try {
        const body = new FormData();
        body.append('command', cmd);

        const res: CommandResponse = await fetch(`${API}?action=command`, { method: 'POST', body }).then(r => r.json());

        out.className   = res.ok ? 'rcon-response success' : 'rcon-response error';
        out.textContent = res.response ?? res.error ?? '–';
    } catch {
        out.className   = 'rcon-response error';
        out.textContent = 'Kapcsolati hiba.';
    }
}

// ── Gyors akciók az online kártyákon ────────────────────────────────────
function quickAction(action: string, username: string): void {
    if (!confirm(`Biztosan: ${action} → ${username}?`)) return;
    const body = new FormData();
    body.append('username', username);
    fetch(`${API}?action=${action}`, { method: 'POST', body })
        .then(r => r.json())
        .then((res: CommandResponse) => {
            alert(res.response ?? res.error ?? '–');
            fetchStatus();
        });
}

function quickMsg(username: string): void {
    const msg = prompt(`Üzenet ${username} számára:`);
    if (!msg) return;
    sendRconCommand(`msg ${username} ${msg}`);
}

// ── Profilpanel RCON gombok ───────────────────────────────────────────────
document.querySelectorAll<HTMLElement>('.rcon-actions').forEach(panel => {
    const username  = panel.dataset.username!;
    const resultEl  = panel.parentElement!.querySelector<HTMLElement>('[id^="rcon-result-"]');

    panel.querySelectorAll<HTMLButtonElement>('[data-action]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const action = btn.dataset.action!;

            if (action === 'kick') {
                const reason = prompt('Kirúgás oka (opcionális):') ?? 'Admin kirúgta.';
                const body   = new FormData();
                body.append('username', username);
                body.append('reason', reason);
                const res: CommandResponse = await fetch(`${API}?action=kick`, { method: 'POST', body }).then(r => r.json());
                showResult(resultEl, res);

            } else if (action === 'ban') {
                if (!confirm(`Biztosan kitiltod: ${username}? (MC szerveren + weboldalon)`)) return;
                const reason = prompt('Tiltás oka:') ?? 'Admin döntés alapján.';
                const body   = new FormData();
                body.append('username', username);
                body.append('reason', reason);
                const res: CommandResponse = await fetch(`${API}?action=ban`, { method: 'POST', body }).then(r => r.json());
                showResult(resultEl, res);
                if (res.ok) setTimeout(() => location.reload(), 1500);

            } else if (action === 'unban') {
                if (!confirm(`Feloldod ${username} tiltását?`)) return;
                const body = new FormData();
                body.append('username', username);
                const res: CommandResponse = await fetch(`${API}?action=unban`, { method: 'POST', body }).then(r => r.json());
                showResult(resultEl, res);
                if (res.ok) setTimeout(() => location.reload(), 1500);

            } else if (action === 'msg') {
                const msg = prompt(`Üzenet ${username} számára:`);
                if (!msg) return;
                await sendRconCommand(`msg ${username} ${msg}`, resultEl);
            }
        });
    });
});

function showResult(el: HTMLElement | null, res: CommandResponse): void {
    if (!el) return;
    el.style.display = 'block';
    el.className     = res.ok ? 'rcon-inline-result success' : 'rcon-inline-result error';
    el.textContent   = res.response ?? res.error ?? '–';
}

// ── RCON parancs gomb ─────────────────────────────────────────────────────
document.getElementById('rcon-send-btn')?.addEventListener('click', () => {
    const input = document.getElementById('rcon-input') as HTMLInputElement;
    if (input.value.trim()) {
        sendRconCommand(input.value.trim());
        input.value = '';
    }
});

document.getElementById('rcon-input')?.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Enter') {
        (document.getElementById('rcon-send-btn') as HTMLButtonElement)?.click();
    }
});

// ── Refresh gomb ──────────────────────────────────────────────────────────
document.getElementById('btn-refresh-status')?.addEventListener('click', fetchStatus);

// ── Induláskor + auto-refresh 30mp-ként ───────────────────────────────────
fetchStatus();
setInterval(fetchStatus, 30_000);