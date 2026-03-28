/// <reference lib="dom" />

// Egy globális Toast értesítő függvény (ezt a dizájnt már beírtuk a CSS-be!)
function showToast(type: 'success' | 'error' | 'warning' | 'info', message: string) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'info';
    if (type === 'success') icon = 'check_circle';
    if (type === 'error') icon = 'error';
    if (type === 'warning') icon = 'warning';

    toast.innerHTML = `<span class="material-symbols-rounded">${icon}</span> ${message}`;
    container.appendChild(toast);

    // Kicsi késleltetés az animáció beúszásához
    setTimeout(() => toast.classList.add('show'), 10);

    // 3 másodperc múlva animálva eltűnik
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// A funkció, ami lekezeli a gombnyomást
async function doTicketAction(action: string, ticketId: number, confirmMessage?: string) {
    // Ha van megerősítő üzenet (pl. törlésnél), rákérdezünk
    if (confirmMessage && !confirm(confirmMessage)) return;

    try {
        const res = await fetch('/admin/api/ticket_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, id: ticketId })
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast('success', data.message);
            
            // MÁGIA: Frissítjük a kijelzőt a háttérben oldalfrissítés nélkül!
            const htmlRes = await fetch(window.location.href);
            const htmlText = await htmlRes.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');
            
            // Kicseréljük a gombokat (Jobb oldali panel)
            const currentControls = document.querySelector('.admin-controls');
            const newControls = doc.querySelector('.admin-controls');
            if (currentControls && newControls) currentControls.innerHTML = newControls.innerHTML;
            
            // Kicseréljük a státusz jelvényt (Felső rész)
            const currentHeader = document.querySelector('.chat-header');
            const newHeader = doc.querySelector('.chat-header');
            if (currentHeader && newHeader) currentHeader.innerHTML = newHeader.innerHTML;

            // Ha bejött egy rendszerüzenet, frissítjük a chat dobozt is!
            const currentChat = document.getElementById('chat-messages');
            const newChat = doc.getElementById('chat-messages');
            if (currentChat && newChat) {
                currentChat.innerHTML = newChat.innerHTML;
                currentChat.scrollTop = currentChat.scrollHeight; // Legörgetünk az aljára
            }
            
            // Input doboz logikája (lezárás vs újranyitás)
            const chatArea = document.querySelector('.chat-input-area');
            const newChatArea = doc.querySelector('.chat-input-area');
            if (chatArea && newChatArea) {
                 chatArea.parentNode?.replaceChild(newChatArea, chatArea);
            } else if (!newChatArea && chatArea) {
                 const alert = doc.querySelector('.chat-closed-alert');
                 if(alert) chatArea.parentNode?.replaceChild(alert, chatArea);
            } else if (!chatArea && newChatArea) {
                 const alert = document.querySelector('.chat-closed-alert');
                 if(alert) alert.parentNode?.replaceChild(newChatArea, alert);
            }

        } else {
            showToast('error', data.message || 'Hiba történt a művelet során.');
        }
    } catch (err) {
        console.error(err);
        showToast('error', 'Hálózati hiba történt az API hívás közben!');
    }
}

// Hozzárendeljük a globális window objektumhoz, hogy a HTML gombok elérjék
(window as any).doTicketAction = doTicketAction;