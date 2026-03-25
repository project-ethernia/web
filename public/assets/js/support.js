"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", () => {
    // 1. Auto-scroll a chat aljára
    const chatMsgs = document.getElementById("chat-messages");
    if (chatMsgs) {
        chatMsgs.scrollTop = chatMsgs.scrollHeight;
    }
    // 2. Fájl előnézet (Image Preview) logika
    const fileInput = document.getElementById("chat-file-input");
    const previewContainer = document.getElementById("image-preview-container");
    const previewImg = document.getElementById("image-preview");
    const removeBtn = document.getElementById("remove-image-btn");
    if (fileInput && previewContainer && previewImg) {
        fileInput.addEventListener("change", function () {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                // Csak akkor csinálunk előnézetet, ha tényleg kép
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
    if (removeBtn) {
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
    // 3. Textarea dinamikus magasság és Okos Küldés
    const chatTextarea = document.querySelector(".chat-textarea");
    const chatForm = document.querySelector(".chat-form");
    if (chatTextarea) {
        chatTextarea.addEventListener("input", function () {
            this.style.height = "24px";
            this.style.height = (this.scrollHeight) + "px";
        });
        // Enter gomb lekezelése
        chatTextarea.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                const hasText = this.value.trim() !== '';
                const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
                // Csak akkor küldi el, ha van kép VAGY van szöveg
                if (hasText || hasFile) {
                    if (chatForm) {
                        chatForm.submit();
                    }
                }
            }
        });
    }
    // Gombra kattintás lekezelése (hogy ne lehessen üreset küldeni)
    if (chatForm) {
        chatForm.addEventListener("submit", function (e) {
            const hasText = chatTextarea && chatTextarea.value.trim() !== '';
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
            if (!hasText && !hasFile) {
                e.preventDefault(); // Megállítjuk a küldést, ha mindkettő üres
            }
        });
    }
});
