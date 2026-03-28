/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    const rconForm = document.getElementById('rcon-form') as HTMLFormElement | null;
    const rconInput = document.getElementById('rcon-input') as HTMLInputElement | null;
    const terminalBox = document.getElementById('rcon-terminal-output') as HTMLElement | null;

    if (rconForm && rconInput && terminalBox) {
        rconForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const command = rconInput.value.trim();
            if (!command) return;

            // 1. Azonnal beírjuk a terminálba a saját parancsunkat kék színnel
            appendTerminal(command, 'command');
            rconInput.value = '';
            rconInput.disabled = true;

            try {
                // 2. Háttérben lekérjük az RCON választ
                const res = await fetch('/admin/api/rcon_send.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ command })
                });
                const data = await res.json();

                // 3. Kiírjuk a választ
                if (data.status === 'success') {
                    appendTerminal(data.response, 'success');
                } else {
                    appendTerminal(data.message, 'error');
                }
            } catch (err) {
                appendTerminal('Hálózati hiba: Nem sikerült kommunikálni az API-val.', 'error');
            } finally {
                rconInput.disabled = false;
                rconInput.focus();
            }
        });
    }

    function appendTerminal(text: string, type: 'command' | 'success' | 'error') {
        if (!terminalBox) return;
        const div = document.createElement('div');
        div.className = `terminal-line type-${type}`;
        
        if (type === 'command') {
            div.innerHTML = `<span class="prompt">> /</span> ${text}`;
        } else {
            // Sima szöveg escape-elése (biztonság miatt, hogy ne törje meg a HTML-t)
            const safeText = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            
            // Színes Minecraft kódok (pl. §c) eltávolítása, ha bejönnének
            const cleanText = safeText.replace(/§[0-9a-fk-or]/g, ""); 
            
            div.innerHTML = cleanText.replace(/\n/g, '<br>'); // Sortörések megtartása
        }
        
        terminalBox.appendChild(div);
        
        // Automatikusan az aljára görgetünk, mint egy igazi terminálnál
        terminalBox.scrollTop = terminalBox.scrollHeight;
    }
});