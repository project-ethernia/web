"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    var toggleBtn = document.getElementById("chat-toggle-btn");
    var closeBtn = document.getElementById("chat-close-btn");
    var panel = document.getElementById("chat-panel");
    var form = document.getElementById("chat-form");
    var input = document.getElementById("chat-input");
    var messagesContainer = document.getElementById("chat-messages");
    var badge = document.getElementById("chat-badge");
    var lastId = 0;
    var isPanelOpen = false;
    var toggleChat = function () {
        isPanelOpen = !isPanelOpen;
        panel === null || panel === void 0 ? void 0 : panel.classList.toggle("open", isPanelOpen);
        toggleBtn === null || toggleBtn === void 0 ? void 0 : toggleBtn.classList.toggle("hidden", isPanelOpen);
        if (isPanelOpen) {
            input === null || input === void 0 ? void 0 : input.focus();
            if (badge)
                badge.style.display = 'none';
            scrollToBottom();
        }
    };
    toggleBtn === null || toggleBtn === void 0 ? void 0 : toggleBtn.addEventListener("click", toggleChat);
    closeBtn === null || closeBtn === void 0 ? void 0 : closeBtn.addEventListener("click", toggleChat);
    var scrollToBottom = function () {
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    };
    var escapeHTML = function (str) {
        var p = document.createElement("p");
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    };
    var appendMessage = function (msg) {
        if (!messagesContainer)
            return;
        var div = document.createElement("div");
        div.className = "chat-msg ".concat(msg.is_me ? 'msg-me' : 'msg-other');
        var avatarHTML = msg.is_me ? '' : "<img src=\"".concat(msg.avatar, "\" alt=\"Avatar\" class=\"chat-avatar\">");
        var nameHTML = msg.is_me ? '' : "<div class=\"chat-name\">".concat(msg.username, " <span class=\"chat-time\">").concat(msg.time, "</span></div>");
        var timeHTML = msg.is_me ? "<div class=\"chat-time\" style=\"text-align: right; margin-top:2px;\">".concat(msg.time, "</div>") : '';
        div.innerHTML = "\n            ".concat(avatarHTML, "\n            <div class=\"chat-bubble-wrapper\">\n                ").concat(nameHTML, "\n                <div class=\"chat-bubble\">").concat(escapeHTML(msg.message), "</div>\n                ").concat(timeHTML, "\n            </div>\n        ");
        messagesContainer.appendChild(div);
    };
    var fetchMessages = function () {
        fetch("/admin/chat_api.php?last_id=".concat(lastId))
            .then(function (res) { return res.json(); })
            .then(function (data) {
            if (data.ok && data.messages && data.messages.length > 0) {
                var isAtBottom = messagesContainer ? (messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 50) : false;
                data.messages.forEach(function (msg) {
                    appendMessage(msg);
                    lastId = msg.id;
                });
                if (isPanelOpen && isAtBottom) {
                    scrollToBottom();
                }
                else if (!isPanelOpen && badge) {
                    badge.style.display = 'block';
                }
            }
        })
            .catch(console.error);
    };
    form === null || form === void 0 ? void 0 : form.addEventListener("submit", function (e) {
        e.preventDefault();
        var text = input.value.trim();
        if (!text)
            return;
        var formData = new FormData();
        formData.append("message", text);
        input.value = "";
        input.focus();
        fetch("/admin/chat_api.php", {
            method: "POST",
            body: formData
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
            if (data.ok)
                fetchMessages();
        })
            .catch(console.error);
    });
    setInterval(fetchMessages, 3000);
    fetchMessages();
});
