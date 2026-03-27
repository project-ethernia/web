"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", () => {
    // Kőkemény típusdeklarációk a TypeScript miatt
    const chatMsgs = document.getElementById("chat-messages");
    const chatForm = document.getElementById("chat-form");
    const chatTextarea = document.getElementById("chat-textarea");
    const fileInput = document.getElementById("chat-file-input");
    const submitBtn = document.getElementById("chat-submit-btn");
    const ticketIdEl = document.getElementById("chat-ticket-id");
    const typingIndicator = document.getElementById("typing-indicator");
    const previewContainer = document.getElementById("image-preview-container");
    const previewImg = document.getElementById("image-preview");
    const removeBtn = document.getElementById("remove-image-btn");
    // Ha nem egy ticket nézetben vagyunk (nincs chat), a TS megnyugszik és kilépünk
    if (!chatMsgs || !ticketIdEl)
        return;
    const ticketId = parseInt(ticketIdEl.value, 10);
    let lastMsgId = 0;
    let isTyping = false;
    let typingTimeout = null;
    let isSubmitting = false;
    // --- 1. KÉP ELŐNÉZET ---
    if (fileInput && previewContainer && previewImg && removeBtn) {
        fileInput.addEventListener("change", function () {
            if (fileInput.files && fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        var _a;
                        previewImg.src = ((_a = e.target) === null || _a === void 0 ? void 0 : _a.result) || '';
                        previewContainer.style.display = "inline-block";
                    };
                    reader.readAsDataURL(file);
                }
            }
            else {
                clearPreview();
            }
        });
        removeBtn.addEventListener("click", clearPreview);
    }
    function clearPreview() {
        if (fileInput)
            fileInput.value = "";
        if (previewContainer)
            previewContainer.style.display = "none";
        if (previewImg)
            previewImg.src = "";
    }
    // --- 2. GÉPELÉS ÉRZÉKELÉS ÉS AUTOSIZE ---
    if (chatTextarea) {
        chatTextarea.addEventListener("input", function () {
            chatTextarea.style.height = "45px";
            chatTextarea.style.height = (chatTextarea.scrollHeight) + "px";
            isTyping = true;
            if (typingTimeout)
                clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                isTyping = false;
                notifyTyping(false);
            }, 2000);
            notifyTyping(true);
        });
        chatTextarea.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                if (chatForm)
                    chatForm.dispatchEvent(new Event("submit"));
            }
        });
    }
    let lastTypingNotify = 0;
    function notifyTyping(typing) {
        const now = Date.now();
        if (typing && now - lastTypingNotify < 2000)
            return; // Ne spammeljük az API-t
        lastTypingNotify = now;
        fetch(`/chat_api.php?action=typing&ticket_id=${ticketId}&typing=${typing ? 1 : 0}`).catch(() => { });
    }
    // --- 3. ZERO-RELOAD KÜLDÉS (FETCH API) ---
    if (chatForm) {
        chatForm.addEventListener("submit", async function (e) {
            e.preventDefault();
            if (isSubmitting)
                return;
            const hasText = chatTextarea && chatTextarea.value.trim() !== '';
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
            if (!hasText && !hasFile)
                return;
            // UI Lezárása (Megakadályozza a Spam-et)
            isSubmitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">sync</span>';
            }
            if (chatTextarea)
                chatTextarea.readOnly = true;
            const formData = new FormData(chatForm);
            formData.append('action', 'send');
            formData.append('ticket_id', ticketId.toString());
            try {
                const res = await fetch('/chat_api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    if (chatTextarea) {
                        chatTextarea.value = '';
                        chatTextarea.style.height = '45px';
                    }
                    clearPreview();
                    syncChat(); // Azonnali frissítés kérése
                }
                else if (data.error === 'cooldown') {
                    alert("Kérjük, várj picit a következő üzenet előtt!");
                }
                else {
                    console.error("Hiba:", data.error);
                }
            }
            catch (err) {
                console.error(err);
            }
            finally {
                // UI Feloldása
                isSubmitting = false;
                isTyping = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span class="material-symbols-rounded">send</span>';
                }
                if (chatTextarea) {
                    chatTextarea.readOnly = false;
                    chatTextarea.focus();
                }
            }
        });
    }
    // --- 4. SZINKRONIZÁCIÓ (JSON RENDERELÉS) ---
    let isSyncing = false;
    async function syncChat() {
        if (isSyncing || !chatMsgs)
            return; // Ha a chatMsgs nincs, ne is fusson
        isSyncing = true;
        try {
            const res = await fetch(`/chat_api.php?action=sync&ticket_id=${ticketId}&last_id=${lastMsgId}`);
            const data = await res.json();
            if (data.success && data.messages && data.messages.length > 0) {
                let html = '';
                data.messages.forEach((m) => {
                    if (m.is_system) {
                        html += `<div class="system-msg-simple" data-id="${m.id}"><span class="material-symbols-rounded">info</span> ${m.message.replace(/\n/g, '<br>')}</div>`;
                    }
                    else {
                        const wrapperClass = m.is_mine ? 'mine' : (m.is_admin ? 'admin' : 'player');
                        const badgeHTML = (!m.is_mine && m.is_admin) ? `<span class="role-badge role-STAFF">STAFF</span>` : '';
                        html += `
                        <div class="chat-bubble-wrapper ${wrapperClass}" data-id="${m.id}">
                            <img src="${m.avatar}" alt="Avatar" class="chat-avatar">
                            <div class="chat-content">
                                <div class="chat-meta">
                                    <span class="chat-author">${m.author} ${badgeHTML}</span>
                                    <span class="chat-time">${m.created_at}</span>
                                </div>
                                ${m.message ? `<div class="chat-text">${m.message.replace(/\n/g, '<br>')}</div>` : ''}
                                ${m.attachment ? `<div class="chat-attachment" ${!m.message ? 'style="margin-top: 0;"' : ''}><a href="${m.attachment}" target="_blank"><img src="${m.attachment}"></a></div>` : ''}
                            </div>
                        </div>`;
                    }
                });
                if (typingIndicator) {
                    typingIndicator.insertAdjacentHTML('beforebegin', html);
                }
                else {
                    chatMsgs.innerHTML += html;
                }
                lastMsgId = data.last_id;
                chatMsgs.scrollTo({ top: chatMsgs.scrollHeight, behavior: 'smooth' });
            }
            // Gépelés indikátor frissítése
            if (typingIndicator) {
                if (data.other_typing) {
                    typingIndicator.classList.add("active");
                    chatMsgs.scrollTo({ top: chatMsgs.scrollHeight, behavior: 'smooth' });
                }
                else {
                    typingIndicator.classList.remove("active");
                }
            }
            // Ha lezárták a ticketet időközben
            if (data.ticket_status === 'closed') {
                const inputArea = document.querySelector('.chat-input-area');
                if (inputArea) {
                    inputArea.innerHTML = '<div class="chat-closed-alert"><span class="material-symbols-rounded">lock</span> Ez a hibajegy le lett zárva.</div>';
                }
            }
        }
        catch (err) {
            console.error("Sync error:", err);
        }
        finally {
            isSyncing = false;
        }
    }
    syncChat();
    setInterval(syncChat, 1500);
    const style = document.createElement('style');
    style.textContent = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
    document.head.appendChild(style);
});
