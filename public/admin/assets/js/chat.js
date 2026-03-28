"use strict";
/// <reference lib="dom" />
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g = Object.create((typeof Iterator === "function" ? Iterator : Object).prototype);
    return g.next = verb(0), g["throw"] = verb(1), g["return"] = verb(2), typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
document.addEventListener("DOMContentLoaded", function () {
    var chatMsgs = document.getElementById("chat-messages");
    var chatForm = document.getElementById("chat-form");
    var chatTextarea = document.getElementById("chat-textarea");
    var fileInput = document.getElementById("chat-file-input");
    var submitBtn = document.getElementById("chat-submit-btn");
    var ticketIdEl = document.getElementById("chat-ticket-id");
    var contextEl = document.getElementById("chat-context");
    var typingIndicator = document.getElementById("typing-indicator");
    var previewContainer = document.getElementById("image-preview-container");
    var previewImg = document.getElementById("image-preview");
    var removeBtn = document.getElementById("remove-image-btn");
    if (!chatMsgs || !ticketIdEl)
        return;
    var ticketId = parseInt(ticketIdEl.value, 10);
    var chatContext = contextEl ? contextEl.value : 'admin';
    var lastMsgId = 0;
    var isTyping = false;
    var typingTimeout = null;
    var isSubmitting = false;
    if (fileInput && previewContainer && previewImg && removeBtn) {
        fileInput.addEventListener("change", function () {
            if (fileInput.files && fileInput.files.length > 0) {
                var file = fileInput.files[0];
                if (file.type.startsWith('image/')) {
                    var reader = new FileReader();
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
    if (chatTextarea) {
        chatTextarea.addEventListener("input", function () {
            chatTextarea.style.height = "45px";
            chatTextarea.style.height = (chatTextarea.scrollHeight) + "px";
            isTyping = true;
            if (typingTimeout)
                clearTimeout(typingTimeout);
            typingTimeout = setTimeout(function () { isTyping = false; notifyTyping(false); }, 2000);
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
    var lastTypingNotify = 0;
    function notifyTyping(typing) {
        var now = Date.now();
        if (typing && now - lastTypingNotify < 2000)
            return;
        lastTypingNotify = now;
        var fd = new URLSearchParams();
        fd.append('action', 'typing');
        fd.append('ticket_id', ticketId.toString());
        fd.append('context', chatContext);
        fetch("/api/chat.php", { method: 'POST', body: fd }).catch(function () { });
    }
    if (chatForm) {
        chatForm.addEventListener("submit", function (e) {
            return __awaiter(this, void 0, void 0, function () {
                var hasText, hasFile, formData, res, data, err_1;
                return __generator(this, function (_a) {
                    switch (_a.label) {
                        case 0:
                            e.preventDefault();
                            if (isSubmitting)
                                return [2 /*return*/];
                            hasText = chatTextarea && chatTextarea.value.trim() !== '';
                            hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
                            if (!hasText && !hasFile)
                                return [2 /*return*/];
                            isSubmitting = true;
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                submitBtn.innerHTML = '<span class="material-symbols-rounded" style="animation: spin 1s linear infinite;">sync</span>';
                            }
                            if (chatTextarea)
                                chatTextarea.readOnly = true;
                            formData = new FormData(chatForm);
                            formData.append('action', 'send');
                            formData.append('ticket_id', ticketId.toString());
                            formData.append('context', chatContext);
                            _a.label = 1;
                        case 1:
                            _a.trys.push([1, 4, 5, 6]);
                            return [4 /*yield*/, fetch('/api/chat.php', { method: 'POST', body: formData })];
                        case 2:
                            res = _a.sent();
                            return [4 /*yield*/, res.json()];
                        case 3:
                            data = _a.sent();
                            if (data.success) {
                                if (chatTextarea) {
                                    chatTextarea.value = '';
                                    chatTextarea.style.height = '45px';
                                }
                                clearPreview();
                                syncChat();
                            }
                            else if (data.error === 'cooldown') {
                                if (window.Toast)
                                    window.Toast.warning("Kérjük, várj egy picit a következő üzenet előtt!");
                            }
                            else {
                                if (window.Toast)
                                    window.Toast.error("Hiba történt: " + data.error);
                            }
                            return [3 /*break*/, 6];
                        case 4:
                            err_1 = _a.sent();
                            if (window.Toast)
                                window.Toast.error("Hálózati hiba történt a küldés során.");
                            return [3 /*break*/, 6];
                        case 5:
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
                            return [7 /*endfinally*/];
                        case 6: return [2 /*return*/];
                    }
                });
            });
        });
    }
    var isSyncing = false;
    function syncChat() {
        return __awaiter(this, void 0, void 0, function () {
            var res, data, html_1, inputArea, err_2;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (isSyncing || !chatMsgs)
                            return [2 /*return*/];
                        isSyncing = true;
                        _a.label = 1;
                    case 1:
                        _a.trys.push([1, 4, 5, 6]);
                        return [4 /*yield*/, fetch("/api/chat.php?action=sync&ticket_id=".concat(ticketId, "&last_id=").concat(lastMsgId, "&context=").concat(chatContext))];
                    case 2:
                        res = _a.sent();
                        return [4 /*yield*/, res.json()];
                    case 3:
                        data = _a.sent();
                        if (data.success && data.messages && data.messages.length > 0) {
                            html_1 = '';
                            data.messages.forEach(function (m) {
                                if (m.is_system) {
                                    html_1 += "<div class=\"system-msg-simple\" data-id=\"".concat(m.id, "\"><span class=\"material-symbols-rounded\">info</span> ").concat(m.message.replace(/\n/g, '<br>'), "</div>");
                                }
                                else {
                                    var wrapperClass = m.is_mine ? 'mine' : (m.is_admin ? 'admin' : 'player');
                                    var badgeHTML = (!m.is_mine && m.is_admin) ? "<span class=\"role-badge role-STAFF\">STAFF</span>" : '';
                                    html_1 += "\n                        <div class=\"chat-bubble-wrapper ".concat(wrapperClass, "\" data-id=\"").concat(m.id, "\">\n                            <img src=\"").concat(m.avatar, "\" alt=\"Avatar\" class=\"chat-avatar\">\n                            <div class=\"chat-content\">\n                                <div class=\"chat-meta\">\n                                    <span class=\"chat-author\">").concat(m.author, " ").concat(badgeHTML, "</span>\n                                    <span class=\"chat-time\">").concat(m.created_at, "</span>\n                                </div>\n                                ").concat(m.message ? "<div class=\"chat-text\">".concat(m.message.replace(/\n/g, '<br>'), "</div>") : '', "\n                                ").concat(m.attachment ? "<div class=\"chat-attachment\" ".concat(!m.message ? 'style="margin-top: 0;"' : '', "><a href=\"").concat(m.attachment, "\" target=\"_blank\"><img src=\"").concat(m.attachment, "\"></a></div>") : '', "\n                            </div>\n                        </div>");
                                }
                            });
                            if (typingIndicator)
                                typingIndicator.insertAdjacentHTML('beforebegin', html_1);
                            else
                                chatMsgs.innerHTML += html_1;
                            lastMsgId = data.last_id;
                            chatMsgs.scrollTo({ top: chatMsgs.scrollHeight, behavior: 'smooth' });
                        }
                        if (typingIndicator) {
                            if (data.other_typing) {
                                typingIndicator.classList.add("active");
                                chatMsgs.scrollTo({ top: chatMsgs.scrollHeight, behavior: 'smooth' });
                            }
                            else {
                                typingIndicator.classList.remove("active");
                            }
                        }
                        if (data.ticket_status === 'closed') {
                            inputArea = document.querySelector('.chat-input-area');
                            if (inputArea)
                                inputArea.innerHTML = '<div class="chat-closed-alert"><span class="material-symbols-rounded">lock</span> Ez a hibajegy le lett zárva.</div>';
                        }
                        return [3 /*break*/, 6];
                    case 4:
                        err_2 = _a.sent();
                        console.error("Sync error:", err_2);
                        return [3 /*break*/, 6];
                    case 5:
                        isSyncing = false;
                        return [7 /*endfinally*/];
                    case 6: return [2 /*return*/];
                }
            });
        });
    }
    syncChat();
    setInterval(syncChat, 1500);
});
