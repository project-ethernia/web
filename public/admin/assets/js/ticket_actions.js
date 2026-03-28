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
// Egy globális Toast értesítő függvény (ezt a dizájnt már beírtuk a CSS-be!)
function showToast(type, message) {
    var container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    var toast = document.createElement('div');
    toast.className = "toast toast-".concat(type);
    var icon = 'info';
    if (type === 'success')
        icon = 'check_circle';
    if (type === 'error')
        icon = 'error';
    if (type === 'warning')
        icon = 'warning';
    toast.innerHTML = "<span class=\"material-symbols-rounded\">".concat(icon, "</span> ").concat(message);
    container.appendChild(toast);
    // Kicsi késleltetés az animáció beúszásához
    setTimeout(function () { return toast.classList.add('show'); }, 10);
    // 3 másodperc múlva animálva eltűnik
    setTimeout(function () {
        toast.classList.remove('show');
        setTimeout(function () { return toast.remove(); }, 300);
    }, 3000);
}
// A funkció, ami lekezeli a gombnyomást
function doTicketAction(action, ticketId, confirmMessage) {
    return __awaiter(this, void 0, void 0, function () {
        var res, data, htmlRes, htmlText, parser, doc, currentControls, newControls, currentHeader, newHeader, currentChat, newChat, chatArea, newChatArea, alert_1, alert_2, err_1;
        var _a, _b, _c;
        return __generator(this, function (_d) {
            switch (_d.label) {
                case 0:
                    // Ha van megerősítő üzenet (pl. törlésnél), rákérdezünk
                    if (confirmMessage && !confirm(confirmMessage))
                        return [2 /*return*/];
                    _d.label = 1;
                case 1:
                    _d.trys.push([1, 8, , 9]);
                    return [4 /*yield*/, fetch('/admin/api/ticket_action.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: action, id: ticketId })
                        })];
                case 2:
                    res = _d.sent();
                    return [4 /*yield*/, res.json()];
                case 3:
                    data = _d.sent();
                    if (!(data.status === 'success')) return [3 /*break*/, 6];
                    showToast('success', data.message);
                    return [4 /*yield*/, fetch(window.location.href)];
                case 4:
                    htmlRes = _d.sent();
                    return [4 /*yield*/, htmlRes.text()];
                case 5:
                    htmlText = _d.sent();
                    parser = new DOMParser();
                    doc = parser.parseFromString(htmlText, 'text/html');
                    currentControls = document.querySelector('.admin-controls');
                    newControls = doc.querySelector('.admin-controls');
                    if (currentControls && newControls)
                        currentControls.innerHTML = newControls.innerHTML;
                    currentHeader = document.querySelector('.chat-header');
                    newHeader = doc.querySelector('.chat-header');
                    if (currentHeader && newHeader)
                        currentHeader.innerHTML = newHeader.innerHTML;
                    currentChat = document.getElementById('chat-messages');
                    newChat = doc.getElementById('chat-messages');
                    if (currentChat && newChat) {
                        currentChat.innerHTML = newChat.innerHTML;
                        currentChat.scrollTop = currentChat.scrollHeight; // Legörgetünk az aljára
                    }
                    chatArea = document.querySelector('.chat-input-area');
                    newChatArea = doc.querySelector('.chat-input-area');
                    if (chatArea && newChatArea) {
                        (_a = chatArea.parentNode) === null || _a === void 0 ? void 0 : _a.replaceChild(newChatArea, chatArea);
                    }
                    else if (!newChatArea && chatArea) {
                        alert_1 = doc.querySelector('.chat-closed-alert');
                        if (alert_1)
                            (_b = chatArea.parentNode) === null || _b === void 0 ? void 0 : _b.replaceChild(alert_1, chatArea);
                    }
                    else if (!chatArea && newChatArea) {
                        alert_2 = document.querySelector('.chat-closed-alert');
                        if (alert_2)
                            (_c = alert_2.parentNode) === null || _c === void 0 ? void 0 : _c.replaceChild(newChatArea, alert_2);
                    }
                    return [3 /*break*/, 7];
                case 6:
                    showToast('error', data.message || 'Hiba történt a művelet során.');
                    _d.label = 7;
                case 7: return [3 /*break*/, 9];
                case 8:
                    err_1 = _d.sent();
                    console.error(err_1);
                    showToast('error', 'Hálózati hiba történt az API hívás közben!');
                    return [3 /*break*/, 9];
                case 9: return [2 /*return*/];
            }
        });
    });
}
// Hozzárendeljük a globális window objektumhoz, hogy a HTML gombok elérjék
window.doTicketAction = doTicketAction;
