"use strict";
// public/admin/assets/js/players.ts
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
var _a, _b, _c;
var API = '/admin/rcon_api.php';
// ── Státusz lekérés ──────────────────────────────────────────────────────
function fetchStatus() {
    return __awaiter(this, void 0, void 0, function () {
        var dot, label, count, grid, res, _a;
        var _b;
        return __generator(this, function (_c) {
            switch (_c.label) {
                case 0:
                    dot = document.getElementById('status-dot');
                    label = document.getElementById('status-label');
                    count = document.getElementById('status-players');
                    grid = document.getElementById('online-players-grid');
                    _c.label = 1;
                case 1:
                    _c.trys.push([1, 3, , 4]);
                    return [4 /*yield*/, fetch("".concat(API, "?action=status")).then(function (r) { return r.json(); })];
                case 2:
                    res = _c.sent();
                    if (res.ok && ((_b = res.data) === null || _b === void 0 ? void 0 : _b.online)) {
                        dot.className = 'status-dot online';
                        label.textContent = 'Szerver Online';
                        count.textContent = "".concat(res.data.player_count, " / ").concat(res.data.max_players);
                        renderOnlinePlayers(res.data.players);
                    }
                    else {
                        dot.className = 'status-dot offline';
                        label.textContent = 'Szerver Offline';
                        count.textContent = '0 / –';
                        grid.innerHTML = '<div class="online-empty"><span class="material-symbols-rounded">wifi_off</span><p>A szerver nem elérhető.</p></div>';
                    }
                    return [3 /*break*/, 4];
                case 3:
                    _a = _c.sent();
                    dot.className = 'status-dot offline';
                    label.textContent = 'RCON Hiba';
                    return [3 /*break*/, 4];
                case 4: return [2 /*return*/];
            }
        });
    });
}
function renderOnlinePlayers(players) {
    var grid = document.getElementById('online-players-grid');
    if (players.length === 0) {
        grid.innerHTML = '<div class="online-empty"><span class="material-symbols-rounded">person_off</span><p>Jelenleg nincs online játékos.</p></div>';
        return;
    }
    grid.innerHTML = players.map(function (name) { return "\n        <div class=\"online-player-card glass\">\n            <img src=\"https://minotar.net/helm/".concat(encodeURIComponent(name), "/48.png\"\n                 alt=\"").concat(name, "\" class=\"online-avatar\">\n            <span class=\"online-name\">").concat(name, "</span>\n            <div class=\"online-actions\">\n                <button class=\"btn-sm btn-kick\"\n                        onclick=\"quickAction('kick','").concat(name, "')\">Kick</button>\n                <button class=\"btn-sm btn-msg\"\n                        onclick=\"quickMsg('").concat(name, "')\">Msg</button>\n            </div>\n        </div>\n    "); }).join('');
}
// ── RCON parancs input ────────────────────────────────────────────────────
function sendRconCommand(cmd, resultEl) {
    return __awaiter(this, void 0, void 0, function () {
        var out, body, res, _a;
        var _b, _c;
        return __generator(this, function (_d) {
            switch (_d.label) {
                case 0:
                    out = resultEl !== null && resultEl !== void 0 ? resultEl : document.getElementById('rcon-response');
                    out.style.display = 'block';
                    out.className = 'rcon-response loading';
                    out.textContent = 'Küldés...';
                    _d.label = 1;
                case 1:
                    _d.trys.push([1, 3, , 4]);
                    body = new FormData();
                    body.append('command', cmd);
                    return [4 /*yield*/, fetch("".concat(API, "?action=command"), { method: 'POST', body: body }).then(function (r) { return r.json(); })];
                case 2:
                    res = _d.sent();
                    out.className = res.ok ? 'rcon-response success' : 'rcon-response error';
                    out.textContent = (_c = (_b = res.response) !== null && _b !== void 0 ? _b : res.error) !== null && _c !== void 0 ? _c : '–';
                    return [3 /*break*/, 4];
                case 3:
                    _a = _d.sent();
                    out.className = 'rcon-response error';
                    out.textContent = 'Kapcsolati hiba.';
                    return [3 /*break*/, 4];
                case 4: return [2 /*return*/];
            }
        });
    });
}
// ── Gyors akciók az online kártyákon ────────────────────────────────────
function quickAction(action, username) {
    if (!confirm("Biztosan: ".concat(action, " \u2192 ").concat(username, "?")))
        return;
    var body = new FormData();
    body.append('username', username);
    fetch("".concat(API, "?action=").concat(action), { method: 'POST', body: body })
        .then(function (r) { return r.json(); })
        .then(function (res) {
        var _a, _b;
        alert((_b = (_a = res.response) !== null && _a !== void 0 ? _a : res.error) !== null && _b !== void 0 ? _b : '–');
        fetchStatus();
    });
}
function quickMsg(username) {
    var msg = prompt("\u00DCzenet ".concat(username, " sz\u00E1m\u00E1ra:"));
    if (!msg)
        return;
    sendRconCommand("msg ".concat(username, " ").concat(msg));
}
// ── Profilpanel RCON gombok ───────────────────────────────────────────────
document.querySelectorAll('.rcon-actions').forEach(function (panel) {
    var username = panel.dataset.username;
    var resultEl = panel.parentElement.querySelector('[id^="rcon-result-"]');
    panel.querySelectorAll('[data-action]').forEach(function (btn) {
        btn.addEventListener('click', function () { return __awaiter(void 0, void 0, void 0, function () {
            var action, reason, body, res, reason, body, res, body, res, msg;
            var _a, _b;
            return __generator(this, function (_c) {
                switch (_c.label) {
                    case 0:
                        action = btn.dataset.action;
                        if (!(action === 'kick')) return [3 /*break*/, 2];
                        reason = (_a = prompt('Kirúgás oka (opcionális):')) !== null && _a !== void 0 ? _a : 'Admin kirúgta.';
                        body = new FormData();
                        body.append('username', username);
                        body.append('reason', reason);
                        return [4 /*yield*/, fetch("".concat(API, "?action=kick"), { method: 'POST', body: body }).then(function (r) { return r.json(); })];
                    case 1:
                        res = _c.sent();
                        showResult(resultEl, res);
                        return [3 /*break*/, 8];
                    case 2:
                        if (!(action === 'ban')) return [3 /*break*/, 4];
                        if (!confirm("Biztosan kitiltod: ".concat(username, "? (MC szerveren + weboldalon)")))
                            return [2 /*return*/];
                        reason = (_b = prompt('Tiltás oka:')) !== null && _b !== void 0 ? _b : 'Admin döntés alapján.';
                        body = new FormData();
                        body.append('username', username);
                        body.append('reason', reason);
                        return [4 /*yield*/, fetch("".concat(API, "?action=ban"), { method: 'POST', body: body }).then(function (r) { return r.json(); })];
                    case 3:
                        res = _c.sent();
                        showResult(resultEl, res);
                        if (res.ok)
                            setTimeout(function () { return location.reload(); }, 1500);
                        return [3 /*break*/, 8];
                    case 4:
                        if (!(action === 'unban')) return [3 /*break*/, 6];
                        if (!confirm("Feloldod ".concat(username, " tilt\u00E1s\u00E1t?")))
                            return [2 /*return*/];
                        body = new FormData();
                        body.append('username', username);
                        return [4 /*yield*/, fetch("".concat(API, "?action=unban"), { method: 'POST', body: body }).then(function (r) { return r.json(); })];
                    case 5:
                        res = _c.sent();
                        showResult(resultEl, res);
                        if (res.ok)
                            setTimeout(function () { return location.reload(); }, 1500);
                        return [3 /*break*/, 8];
                    case 6:
                        if (!(action === 'msg')) return [3 /*break*/, 8];
                        msg = prompt("\u00DCzenet ".concat(username, " sz\u00E1m\u00E1ra:"));
                        if (!msg)
                            return [2 /*return*/];
                        return [4 /*yield*/, sendRconCommand("msg ".concat(username, " ").concat(msg), resultEl)];
                    case 7:
                        _c.sent();
                        _c.label = 8;
                    case 8: return [2 /*return*/];
                }
            });
        }); });
    });
});
function showResult(el, res) {
    var _a, _b;
    if (!el)
        return;
    el.style.display = 'block';
    el.className = res.ok ? 'rcon-inline-result success' : 'rcon-inline-result error';
    el.textContent = (_b = (_a = res.response) !== null && _a !== void 0 ? _a : res.error) !== null && _b !== void 0 ? _b : '–';
}
// ── RCON parancs gomb ─────────────────────────────────────────────────────
(_a = document.getElementById('rcon-send-btn')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function () {
    var input = document.getElementById('rcon-input');
    if (input.value.trim()) {
        sendRconCommand(input.value.trim());
        input.value = '';
    }
});
(_b = document.getElementById('rcon-input')) === null || _b === void 0 ? void 0 : _b.addEventListener('keydown', function (e) {
    var _a;
    if (e.key === 'Enter') {
        (_a = document.getElementById('rcon-send-btn')) === null || _a === void 0 ? void 0 : _a.click();
    }
});
// ── Refresh gomb ──────────────────────────────────────────────────────────
(_c = document.getElementById('btn-refresh-status')) === null || _c === void 0 ? void 0 : _c.addEventListener('click', fetchStatus);
// ── Induláskor + auto-refresh 30mp-ként ───────────────────────────────────
fetchStatus();
setInterval(fetchStatus, 30000);
