/// <reference lib="dom" />

declare function showToast(type: string, message: string): void;
declare function ethConfirm(message: string, onConfirm: Function): void;

async function executeTicketAction(action: string, ticketId: number) {
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

            // Chat doboz frissítése
            const currentChat = document.getElementById('chat-messages');
            const newChat = doc.getElementById('chat-messages');
            if (currentChat && newChat) {
                currentChat.innerHTML = newChat.innerHTML;
                currentChat.scrollTop = currentChat.scrollHeight; 
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

// Gombnyomás kezelő
function doTicketAction(action: string, ticketId: number, confirmMessage?: string) {
    if (confirmMessage) {
        ethConfirm(confirmMessage, () => executeTicketAction(action, ticketId));
    } else {
        executeTicketAction(action, ticketId);
    }
}

(window as any).doTicketAction = doTicketAction;