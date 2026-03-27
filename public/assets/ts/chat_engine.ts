/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    const chatMsgs = document.getElementById("chat-messages") as HTMLDivElement | null;
    const chatForm = document.getElementById("chat-form") as HTMLFormElement | null;
    const chatTextarea = document.getElementById("chat-textarea") as HTMLTextAreaElement | null;
    const fileInput = document.getElementById("chat-file-input") as HTMLInputElement | null;
    const submitBtn = document.getElementById("chat-submit-btn") as HTMLButtonElement | null;
    const ticketIdEl = document.getElementById("chat-ticket-id") as HTMLInputElement | null;
    const contextEl = document.getElementById("chat-context") as HTMLInputElement | null;
    const typingIndicator = document.getElementById("typing-indicator") as HTMLDivElement | null;
    
    const previewContainer = document.getElementById("image-preview-container") as HTMLDivElement | null;
    const previewImg = document.getElementById("image-preview") as HTMLImageElement | null;
    const removeBtn = document.getElementById("remove-image-btn") as HTMLButtonElement | null;

    if (!chatMsgs || !ticketIdEl) return;

    const ticketId = parseInt(ticketIdEl.value, 10);
    const chatContext = contextEl ? contextEl.value : 'player'; // <-- ITT OLVASSA BE
    
    let lastMsgId = 0;
    let isTyping = false;
    let typingTimeout: ReturnType<typeof setTimeout> | null = null;
    let isSubmitting = false;

    // --- 1. KÉP ELŐNÉZET ---
    if (fileInput && previewContainer && previewImg && removeBtn) {
        fileInput.addEventListener("change", function() {
            if (fileInput.files && fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = (e.target?.result as string) || '';
                        previewContainer.style.display = "inline-block";
                    }
                    reader.readAsDataURL(file);
                }
            } else {
                clearPreview();
            }
        });
        removeBtn.addEventListener("click", clearPreview);
    }

    function clearPreview() {
        if (fileInput) fileInput.value = "";
        if (previewContainer) previewContainer.style.display = "none";
        if (previewImg) previewImg.src = "";
    }

    // --- 2. GÉPELÉS ÉRZÉKELÉS ÉS AUTOSIZE ---
    if (chatTextarea) {
        chatTextarea.addEventListener("input", function() {
            chatTextarea.style.height = "45px"; 
            chatTextarea.style.height = (chatTextarea.scrollHeight) + "px"; 
            
            isTyping = true;
            if (typingTimeout) clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => { 
                isTyping = false; 
                notifyTyping(false);
            }, 2000);

            notifyTyping(true);
        });

        chatTextarea.addEventListener("keydown", function(e: KeyboardEvent) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault(); 
                if (chatForm) chatForm.dispatchEvent(new Event("submit"));
            }
        });
    }

    let lastTypingNotify = 0;
    function notifyTyping(typing: boolean) {
        const now = Date.now();
        if (typing && now - lastTypingNotify < 2000) return; 
        lastTypingNotify = now;
        // CONTEXT BEKÜLDÉSE:
        fetch(`/chat_api.php?action=typing&ticket_id=${ticketId}&typing=${typing ? 1 : 0}&context=${chatContext}`).catch(() => {});
    }

    // --- 3. ZERO-RELOAD KÜLDÉS ---
    if (chatForm) {
        chatForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            if (isSubmitting) return;

            const hasText = chatTextarea && chatTextarea.value.trim() !== '';
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

            if (!hasText && !hasFile) return;

            isSubmitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">sync</span>';
            }
            if (chatTextarea) chatTextarea.readOnly = true;

            const formData = new FormData(chatForm);
            formData.append('action', 'send');
            formData.append('ticket_id', ticketId.toString());
            formData.append('context', chatContext); // CONTEXT BEKÜLDÉSE!

            try {
                const res = await fetch('/chat_api.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    if (chatTextarea) {
                        chatTextarea.value = '';
                        chatTextarea.style.height = '45px';
                    }
                    clearPreview();
                    syncChat();
                } else if (data.error === 'cooldown') {
                    alert("Kérjük, várj picit a következő üzenet előtt!");
                } else {
                    console.error("Hiba:", data.error);
                }
            } catch (err) {
                console.error(err);
            } finally {
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

    // --- 4. SZINKRONIZÁCIÓ ---
    let isSyncing = false;
    async function syncChat() {
        if (isSyncing || !chatMsgs) return; 
        isSyncing = true;

        try {
            // CONTEXT BEKÜLDÉSE:
            const res = await fetch(`/chat_api.php?action=sync&ticket_id=${ticketId}&last_id=${lastMsgId}&context=${chatContext}`);
            const data = await res.json();

            if (data.success && data.messages && data.messages.length > 0) {
                let html = '';
                data.messages.forEach((m: any) => {
                    if (m.is_system) {
                        html += `<div class="system-msg-simple" data-id="${m.id}"><span class="material-symbols-rounded">info</span> ${m.message.replace(/\n/g, '<br>')}</div>`;
                    } else {
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
                } else {
                    chatMsgs.innerHTML += html;
                }
                
                lastMsgId = data.last_id;
                chatMsgs.scrollTo({ top: chatMsgs.scrollHeight, behavior: 'smooth' });
            }

            if (typingIndicator) {
                if (data.other_typing) {
                    typingIndicator.classList.add("active");
                    chatMsgs.scrollTo({ top: chatMsgs.scrollHeight, behavior: 'smooth' });
                } else {
                    typingIndicator.classList.remove("active");
                }
            }

            if (data.ticket_status === 'closed') {
                const inputArea = document.querySelector('.chat-input-area');
                if (inputArea) {
                    inputArea.innerHTML = '<div class="chat-closed-alert"><span class="material-symbols-rounded">lock</span> Ez a hibajegy le lett zárva.</div>';
                }
            }

        } catch (err) {
            console.error("Sync error:", err);
        } finally {
            isSyncing = false;
        }
    }

    syncChat();
    setInterval(syncChat, 1500);

    const style = document.createElement('style');
    style.textContent = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
    document.head.appendChild(style);
});