"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", () => {
    const chatMsgs = document.getElementById("chat-messages");
    if (chatMsgs) {
        chatMsgs.scrollTop = chatMsgs.scrollHeight;
    }
    const fileInput = document.getElementById("chat-file-input");
    const previewContainer = document.getElementById("image-preview-container");
    const previewImg = document.getElementById("image-preview");
    const removeBtn = document.getElementById("remove-image-btn");
    if (fileInput && previewContainer && previewImg) {
        fileInput.addEventListener("change", function () {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        var _a;
                        previewImg.src = (_a = e.target) === null || _a === void 0 ? void 0 : _a.result;
                        previewContainer.style.display = "inline-block";
                    };
                    reader.readAsDataURL(file);
                }
            }
            else {
                clearPreview();
            }
        });
    }
    if (removeBtn)
        removeBtn.addEventListener("click", clearPreview);
    function clearPreview() {
        if (fileInput)
            fileInput.value = "";
        if (previewContainer)
            previewContainer.style.display = "none";
        if (previewImg)
            previewImg.src = "";
    }
    const chatTextarea = document.querySelector(".chat-textarea");
    const chatForm = document.querySelector(".chat-form");
    // --- REAL-TIME AJAX ÉS TYPING INDICATOR LOGIKA ---
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
    let typingTimeout = null;
    if (chatTextarea) {
        chatTextarea.addEventListener("input", function () {
            this.style.height = "24px";
            this.style.height = (this.scrollHeight) + "px";
            // Ha írunk, beállítjuk a typing flag-et 2 másodpercre
            isTyping = true;
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => { isTyping = false; }, 2000);
        });
        chatTextarea.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                const hasText = this.value.trim() !== '';
                const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
                if (hasText || hasFile) {
                    if (chatForm)
                        chatForm.submit();
                }
            }
        });
    }
    if (chatForm) {
        chatForm.addEventListener("submit", function (e) {
            const hasText = chatTextarea && chatTextarea.value.trim() !== '';
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
            if (!hasText && !hasFile)
                e.preventDefault();
        });
    }
    // A szinkronizáló ciklus (Minden 2 mp)
    if (ticketId && chatMsgs) {
        setInterval(() => {
            fetch(`?action=sync&id=${ticketId}&last_id=${lastMsgId}&typing=${isTyping ? 1 : 0}`)
                .then(res => res.json())
                .then(data => {
                if (data.html) {
                    // Beszúrjuk az új üzenetet a Typing Indicator ELÉ!
                    if (typingIndicator) {
                        typingIndicator.insertAdjacentHTML('beforebegin', data.html);
                    }
                    else {
                        chatMsgs.insertAdjacentHTML('beforeend', data.html);
                    }
                    lastMsgId = data.last_id;
                    chatMsgs.scrollTop = chatMsgs.scrollHeight;
                }
                if (typingIndicator) {
                    if (data.other_typing) {
                        typingIndicator.classList.add("active");
                        chatMsgs.scrollTop = chatMsgs.scrollHeight;
                    }
                    else {
                        typingIndicator.classList.remove("active");
                    }
                }
            })
                .catch(err => console.error("Sync hiba:", err));
        }, 2000);
    }
});
