"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    var chatMsgs = document.getElementById("chat-messages");
    if (chatMsgs)
        chatMsgs.scrollTop = chatMsgs.scrollHeight;
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
    var chatTextarea = document.querySelector(".chat-textarea");
    var chatForm = document.querySelector(".chat-form");
    var lastMsgId = 0;
    var msgElements = document.querySelectorAll(".chat-bubble-wrapper, .system-msg-simple");
    if (msgElements.length > 0) {
        var lastEl = msgElements[msgElements.length - 1];
        lastMsgId = parseInt(lastEl.getAttribute("data-id") || "0");
    }
    var typingIndicator = document.getElementById("typing-indicator");
    var urlParams = new URLSearchParams(window.location.search);
    var ticketId = urlParams.get('id');
    var isTyping = false;
    var typingTimeout = null;
    if (chatTextarea) {
        chatTextarea.addEventListener("input", function () {
            this.style.height = "24px";
            this.style.height = (this.scrollHeight) + "px";
            isTyping = true;
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(function () { isTyping = false; }, 2000);
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
    if (chatForm) {
        chatForm.addEventListener("submit", function (e) {
            var hasText = chatTextarea && chatTextarea.value.trim() !== '';
            var hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
            if (!hasText && !hasFile)
                e.preventDefault();
        });
    }
    if (ticketId && chatMsgs) {
        setInterval(function () {
            fetch("?action=sync&id=".concat(ticketId, "&last_id=").concat(lastMsgId, "&typing=").concat(isTyping ? 1 : 0))
                .then(function (res) { return res.json(); })
                .then(function (data) {
                if (data.html) {
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
                .catch(function (err) { return console.error("Sync hiba:", err); });
        }, 2000);
    }
});
