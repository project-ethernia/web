/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    const toggleBtn = document.getElementById("chat-toggle-btn");
    const closeBtn = document.getElementById("chat-close-btn");
    const panel = document.getElementById("chat-panel");
    const form = document.getElementById("chat-form") as HTMLFormElement;
    const input = document.getElementById("chat-input") as HTMLInputElement;
    const messagesContainer = document.getElementById("chat-messages");
    const badge = document.getElementById("chat-badge");

    let lastId = 0;
    let isPanelOpen = false;

    const toggleChat = () => {
        isPanelOpen = !isPanelOpen;
        panel?.classList.toggle("open", isPanelOpen);
        toggleBtn?.classList.toggle("hidden", isPanelOpen);
        
        if (isPanelOpen) {
            input?.focus();
            if (badge) badge.style.display = 'none';
            scrollToBottom();
        }
    };

    toggleBtn?.addEventListener("click", toggleChat);
    closeBtn?.addEventListener("click", toggleChat);

    const scrollToBottom = () => {
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    };

    const escapeHTML = (str: string) => {
        const p = document.createElement("p");
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    };

    const appendMessage = (msg: any) => {
        if (!messagesContainer) return;
        
        const div = document.createElement("div");
        div.className = `chat-msg ${msg.is_me ? 'msg-me' : 'msg-other'}`;

        const avatarHTML = msg.is_me ? '' : `<img src="${msg.avatar}" alt="Avatar" class="chat-avatar">`;
        const nameHTML = msg.is_me ? '' : `<div class="chat-name">${msg.username} <span class="chat-time">${msg.time}</span></div>`;
        const timeHTML = msg.is_me ? `<div class="chat-time" style="text-align: right; margin-top:2px;">${msg.time}</div>` : '';

        div.innerHTML = `
            ${avatarHTML}
            <div class="chat-bubble-wrapper">
                ${nameHTML}
                <div class="chat-bubble">${escapeHTML(msg.message)}</div>
                ${timeHTML}
            </div>
        `;
        messagesContainer.appendChild(div);
    };

    const fetchMessages = () => {
        fetch(`/admin/chat_api.php?last_id=${lastId}`)
            .then(res => res.json())
            .then(data => {
                if (data.ok && data.messages && data.messages.length > 0) {
                    const isAtBottom = messagesContainer ? (messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 50) : false;

                    data.messages.forEach((msg: any) => {
                        appendMessage(msg);
                        lastId = msg.id;
                    });

                    if (isPanelOpen && isAtBottom) {
                        scrollToBottom();
                    } else if (!isPanelOpen && badge) {
                        badge.style.display = 'block';
                    }
                }
            })
            .catch(console.error);
    };

    form?.addEventListener("submit", (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        const formData = new FormData();
        formData.append("message", text);

        input.value = "";
        input.focus();

        fetch("/admin/chat_api.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok) fetchMessages();
        })
        .catch(console.error);
    });

    setInterval(fetchMessages, 3000);
    fetchMessages();
});