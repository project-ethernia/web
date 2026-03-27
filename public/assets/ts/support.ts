/// <reference lib="dom" />

document.addEventListener("DOMContentLoaded", () => {
    const chatMsgs = document.getElementById("chat-messages");
    if (chatMsgs) {
        chatMsgs.scrollTop = chatMsgs.scrollHeight;
    }

    const fileInput = document.getElementById("chat-file-input") as HTMLInputElement | null;
    const previewContainer = document.getElementById("image-preview-container");
    const previewImg = document.getElementById("image-preview") as HTMLImageElement | null;
    const removeBtn = document.getElementById("remove-image-btn");
    
    if (fileInput && previewContainer && previewImg) {
        fileInput.addEventListener("change", function() {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target?.result as string;
                        previewContainer.style.display = "inline-block";
                    }
                    reader.readAsDataURL(file);
                }
            } else {
                clearPreview();
            }
        });
    }

    if (removeBtn) removeBtn.addEventListener("click", clearPreview);

    function clearPreview() {
        if (fileInput) fileInput.value = "";
        if (previewContainer) previewContainer.style.display = "none";
        if (previewImg) previewImg.src = "";
    }

    const chatTextarea = document.querySelector(".chat-textarea") as HTMLTextAreaElement | null;
    const chatForm = document.querySelector(".chat-form") as HTMLFormElement | null;
    const chatSubmitBtn = document.getElementById("chat-submit-btn") as HTMLButtonElement | null;
    
    let isSubmitting = false; // Duplikáció megakadályozása

    let lastMsgId = 0;
    const msgElements = document.querySelectorAll(".chat-bubble-wrapper, .system-msg-simple");
    if (msgElements.length > 0) {
        const lastEl = msgElements[msgElements.length - 1];
        lastMsgId = parseInt(lastEl.getAttribute("data-id") || "0");
    }

    const typingIndicator = document.getElementById("typing-indicator");
    const urlParams = new URLSearchParams(window.location.search);
    const ticketId = urlParams.get('id');

    let isTyping = false;
    let typingTimeout: ReturnType<typeof setTimeout> | null = null;

    if (chatTextarea) {
        chatTextarea.addEventListener("input", function() {
            this.style.height = "45px"; 
            this.style.height = (this.scrollHeight) + "px"; 
            
            isTyping = true;
            if (typingTimeout) clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => { isTyping = false; }, 3000); // 3 másodperc tűrés
        });

        chatTextarea.addEventListener("keydown", function(e: KeyboardEvent) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault(); 
                if (isSubmitting) return; // Ha már küld, ignorálja
                
                const hasText = this.value.trim() !== '';
                const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
                if (hasText || hasFile) {
                    if (chatForm) {
                        lockForm();
                        chatForm.submit();
                    }
                }
            }
        });
    }

    if (chatForm) {
        chatForm.addEventListener("submit", function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            
            const hasText = chatTextarea && chatTextarea.value.trim() !== '';
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
            if (!hasText && !hasFile) {
                e.preventDefault();
            } else {
                lockForm();
            }
        });
    }

    // Beküldéskor gomb és textarea lezárása
    function lockForm() {
        isSubmitting = true;
        isTyping = false; // Ne mutassa tovább, hogy ír
        if (chatSubmitBtn) {
            chatSubmitBtn.disabled = true;
            chatSubmitBtn.innerHTML = '<span class="material-symbols-rounded">hourglass_empty</span>';
        }
        if (chatTextarea) {
            chatTextarea.readOnly = true;
        }
    }

    // Valós idejű AJAX szinkronizáció
    if (ticketId && chatMsgs) {
        setInterval(() => {
            fetch(`?action=sync&id=${ticketId}&last_id=${lastMsgId}&typing=${isTyping ? 1 : 0}`)
                .then(res => res.json())
                .then(data => {
                    if (data.html) {
                        if (typingIndicator) {
                            typingIndicator.insertAdjacentHTML('beforebegin', data.html);
                        } else {
                            chatMsgs.insertAdjacentHTML('beforeend', data.html);
                        }
                        lastMsgId = data.last_id;
                        chatMsgs.scrollTop = chatMsgs.scrollHeight;
                    }
                    
                    if (typingIndicator) {
                        if (data.other_typing) {
                            typingIndicator.classList.add("active");
                            chatMsgs.scrollTop = chatMsgs.scrollHeight;
                        } else {
                            typingIndicator.classList.remove("active");
                        }
                    }
                })
                .catch(err => console.error("Sync hiba:", err));
        }, 2000);
    }
});