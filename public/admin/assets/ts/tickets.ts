/// <reference lib="dom" />

// Szólunk a TypeScript-nek, hogy ezek a funkciók léteznek a globális térben!
declare function showToast(type: string, msg: string): void;
declare function ethConfirm(msg: string, cb: Function): void;

const executeTicketAction = async (action: string, ticketId: number) => {
    try {
        const res = await fetch('/admin/api/ticket_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, id: ticketId })
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast('success', data.message);
            
            const htmlRes = await fetch(window.location.href);
            const htmlText = await htmlRes.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');
            
            const currentControls = document.querySelector('.admin-controls');
            const newControls = doc.querySelector('.admin-controls');
            if (currentControls && newControls) currentControls.innerHTML = newControls.innerHTML;
            
            const currentHeader = document.querySelector('.chat-header');
            const newHeader = doc.querySelector('.chat-header');
            if (currentHeader && newHeader) currentHeader.innerHTML = newHeader.innerHTML;

            const currentChat = document.getElementById('chat-messages');
            const newChat = doc.getElementById('chat-messages');
            if (currentChat && newChat) {
                currentChat.innerHTML = newChat.innerHTML;
                currentChat.scrollTop = currentChat.scrollHeight; 
            }
            
            const chatArea = document.querySelector('.chat-input-area');
            const newChatArea = doc.querySelector('.chat-input-area');
            if (chatArea && newChatArea) {
                 chatArea.parentNode?.replaceChild(newChatArea, chatArea);
            } else if (!newChatArea && chatArea) {
                 const alertBox = doc.querySelector('.chat-closed-alert');
                 if(alertBox) chatArea.parentNode?.replaceChild(alertBox, chatArea);
            } else if (!chatArea && newChatArea) {
                 const alertBox = document.querySelector('.chat-closed-alert');
                 if(alertBox) alertBox.parentNode?.replaceChild(newChatArea, alertBox);
            }

        } else {
            showToast('error', data.message || 'Hiba történt a művelet során.');
        }
    } catch (err) {
        console.error(err);
        showToast('error', 'Hálózati hiba történt az API hívás közben!');
    }
};

const doTicketAction = (action: string, ticketId: number, confirmMessage?: string) => {
    if (confirmMessage) {
        ethConfirm(confirmMessage, () => executeTicketAction(action, ticketId));
    } else {
        executeTicketAction(action, ticketId);
    }
};

(window as any).doTicketAction = doTicketAction;