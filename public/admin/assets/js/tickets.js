"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    // 1. Auto-scroll a chat aljára
    var chatMsgs = document.getElementById("chat-messages");
    if (chatMsgs) {
        chatMsgs.scrollTop = chatMsgs.scrollHeight;
    }
    // 2. Fájl előnézet (Image Preview)
    var fileInput = document.getElementById("chat-file-input");
    var previewContainer = document.getElementById("image-preview-container");
    var previewImg = document.getElementById("image-preview");
    var removeBtn = document.getElementById("remove-image-btn");
    if (fileInput && previewContainer && previewImg) {
        fileInput.addEventListener("change", function () {
            if (this.files && this.files.length > 0) {
                var file = this.files[0];
                if (file.type.startsWith('image/')) {
                    var reader = new FileReader();
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
    // 3. Textarea auto-resize és Enter-küldés
    var chatTextarea = document.querySelector(".chat-textarea");
    var chatForm = document.querySelector(".chat-form");
    if (chatTextarea) {
        chatTextarea.addEventListener("input", function () {
            this.style.height = "24px";
            this.style.height = (this.scrollHeight) + "px";
        });
        chatTextarea.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                var hasText = this.value.trim() !== '';
                var hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
                if (hasText || hasFile) {
                    if (chatForm)
                        chatForm.submit();
                }
            }
        });
    }
    // 4. Üres küldés blokkolása gombra kattintáskor
    if (chatForm) {
        chatForm.addEventListener("submit", function (e) {
            var hasText = chatTextarea && chatTextarea.value.trim() !== '';
            var hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
            if (!hasText && !hasFile) {
                e.preventDefault();
            }
        });
    }
});
