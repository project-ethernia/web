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
    var rconForm = document.getElementById('rcon-form');
    var rconInput = document.getElementById('rcon-input');
    var terminalBox = document.getElementById('rcon-terminal-output');
    if (rconForm && rconInput && terminalBox) {
        rconForm.addEventListener('submit', function (e) { return __awaiter(void 0, void 0, void 0, function () {
            var command, res, data, err_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        e.preventDefault();
                        command = rconInput.value.trim();
                        if (!command)
                            return [2 /*return*/];
                        // 1. Azonnal beírjuk a terminálba a saját parancsunkat kék színnel
                        appendTerminal(command, 'command');
                        rconInput.value = '';
                        rconInput.disabled = true;
                        _a.label = 1;
                    case 1:
                        _a.trys.push([1, 4, 5, 6]);
                        return [4 /*yield*/, fetch('/admin/api/rcon_send.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ command: command })
                            })];
                    case 2:
                        res = _a.sent();
                        return [4 /*yield*/, res.json()];
                    case 3:
                        data = _a.sent();
                        // 3. Kiírjuk a választ
                        if (data.status === 'success') {
                            appendTerminal(data.response, 'success');
                        }
                        else {
                            appendTerminal(data.message, 'error');
                        }
                        return [3 /*break*/, 6];
                    case 4:
                        err_1 = _a.sent();
                        appendTerminal('Hálózati hiba: Nem sikerült kommunikálni az API-val.', 'error');
                        return [3 /*break*/, 6];
                    case 5:
                        rconInput.disabled = false;
                        rconInput.focus();
                        return [7 /*endfinally*/];
                    case 6: return [2 /*return*/];
                }
            });
        }); });
    }
    function appendTerminal(text, type) {
        if (!terminalBox)
            return;
        var div = document.createElement('div');
        div.className = "terminal-line type-".concat(type);
        if (type === 'command') {
            div.innerHTML = "<span class=\"prompt\">> /</span> ".concat(text);
        }
        else {
            // Sima szöveg escape-elése (biztonság miatt, hogy ne törje meg a HTML-t)
            var safeText = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            // Színes Minecraft kódok (pl. §c) eltávolítása, ha bejönnének
            var cleanText = safeText.replace(/§[0-9a-fk-or]/g, "");
            div.innerHTML = cleanText.replace(/\n/g, '<br>'); // Sortörések megtartása
        }
        terminalBox.appendChild(div);
        // Automatikusan az aljára görgetünk, mint egy igazi terminálnál
        terminalBox.scrollTop = terminalBox.scrollHeight;
    }
});
