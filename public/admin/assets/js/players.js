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
    var API = '/admin/rcon_api.php';
    // ── Elemek ─────────────────────────────────────────────────────────────
    var statusDot = document.getElementById('status-dot');
    var statusLabel = document.getElementById('status-label');
    var statusCount = document.getElementById('status-players');
    var onlineGrid = document.getElementById('online-players-grid');
    var rconInput = document.getElementById('rcon-input');
    var rconSendBtn = document.getElementById('rcon-send-btn');
    var rconResponse = document.getElementById('rcon-response');
    var refreshBtn = document.getElementById('btn-refresh-status');
    // ── Segédfüggvények ────────────────────────────────────────────────────
    function showResult(el, res) {
        var _a, _b;
        if (!el)
            return;
        el.style.display = 'block';
        el.className = 'rcon-inline-result ' + (res.ok ? 'success' : 'error');
        el.textContent = (_b = (_a = res.response) !== null && _a !== void 0 ? _a : res.error) !== null && _b !== void 0 ? _b : '–';
    }
    function rconPost(action_1) {
        return __awaiter(this, arguments, void 0, function (action, extra) {
            var body, res;
            if (extra === void 0) { extra = {}; }
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        body = new FormData();
                        Object.entries(extra).forEach(function (_a) {
                            var k = _a[0], v = _a[1];
                            return body.append(k, v);
                        });
                        return [4 /*yield*/, fetch("".concat(API, "?action=").concat(action), { method: 'POST', body: body })];
                    case 1:
                        res = _a.sent();
                        return [2 /*return*/, res.json()];
                }
            });
        });
    }
    // ── Szerver státusz lekérése ───────────────────────────────────────────
    function fetchStatus() {
        return __awaiter(this, void 0, void 0, function () {
            var res, _a;
            var _b;
            return __generator(this, function (_c) {
                switch (_c.label) {
                    case 0:
                        if (statusDot)
                            statusDot.className = 'status-dot';
                        if (statusLabel)
                            statusLabel.textContent = 'Kapcsolódás...';
                        _c.label = 1;
                    case 1:
                        _c.trys.push([1, 3, , 4]);
                        return [4 /*yield*/, fetch("".concat(API, "?action=status")).then(function (r) { return r.json(); })];
                    case 2:
                        res = _c.sent();
                        if (res.ok && ((_b = res.data) === null || _b === void 0 ? void 0 : _b.online)) {
                            if (statusDot)
                                statusDot.className = 'status-dot online';
                            if (statusLabel)
                                statusLabel.textContent = 'Szerver Online';
                            if (statusCount)
                                statusCount.textContent = "".concat(res.data.player_count, " / ").concat(res.data.max_players);
                            renderOnlinePlayers(res.data.players);
                        }
                        else {
                            if (statusDot)
                                statusDot.className = 'status-dot offline';
                            if (statusLabel)
                                statusLabel.textContent = 'Szerver Offline';
                            if (statusCount)
                                statusCount.textContent = '0 / –';
                            if (onlineGrid)
                                onlineGrid.innerHTML = "\n                    <div class=\"online-empty\">\n                        <span class=\"material-symbols-rounded\">wifi_off</span>\n                        <p>A szerver nem el\u00E9rhet\u0151 vagy az RCON le van tiltva.</p>\n                    </div>";
                        }
                        return [3 /*break*/, 4];
                    case 3:
                        _a = _c.sent();
                        if (statusDot)
                            statusDot.className = 'status-dot offline';
                        if (statusLabel)
                            statusLabel.textContent = 'RCON Hiba';
                        if (onlineGrid)
                            onlineGrid.innerHTML = "\n                <div class=\"online-empty\">\n                    <span class=\"material-symbols-rounded\">error</span>\n                    <p>Nem siker\u00FClt kapcsol\u00F3dni az RCON API-hoz.</p>\n                </div>";
                        return [3 /*break*/, 4];
                    case 4: return [2 /*return*/];
                }
            });
        });
    }
    // ── Online játékosok kártyái ───────────────────────────────────────────
    function renderOnlinePlayers(players) {
        if (!onlineGrid)
            return;
        if (players.length === 0) {
            onlineGrid.innerHTML = "\n                <div class=\"online-empty\">\n                    <span class=\"material-symbols-rounded\">person_off</span>\n                    <p>Jelenleg nincs online j\u00E1t\u00E9kos.</p>\n                </div>";
            return;
        }
        onlineGrid.innerHTML = players.map(function (name) { return "\n            <div class=\"online-player-card glass\">\n                <img src=\"https://minotar.net/helm/".concat(encodeURIComponent(name), "/48.png\"\n                     alt=\"").concat(name, "\" class=\"online-avatar\"\n                     onerror=\"this.src='https://minotar.net/helm/Steve/48.png'\">\n                <span class=\"online-name\">").concat(name, "</span>\n                <div class=\"online-actions\">\n                    <button class=\"btn-sm btn-kick\" data-player=\"").concat(name, "\">Kick</button>\n                    <button class=\"btn-sm btn-msg\"  data-player=\"").concat(name, "\">Msg</button>\n                </div>\n            </div>\n        "); }).join('');
        // Kártyákon lévő gombok eseményei
        onlineGrid.querySelectorAll('.btn-kick').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var _a;
                var player = btn.dataset.player;
                var reason = (_a = prompt("Kir\u00FAg\u00E1s oka (".concat(player, "):"))) !== null && _a !== void 0 ? _a : 'Admin kirúgta.';
                rconPost('kick', { username: player, reason: reason })
                    .then(function (res) {
                    var _a, _b;
                    alert((_b = (_a = res.response) !== null && _a !== void 0 ? _a : res.error) !== null && _b !== void 0 ? _b : '–');
                    fetchStatus();
                });
            });
        });
        onlineGrid.querySelectorAll('.btn-msg').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var player = btn.dataset.player;
                var msg = prompt("\u00DCzenet ".concat(player, " sz\u00E1m\u00E1ra:"));
                if (!msg)
                    return;
                sendRconCommand("msg ".concat(player, " ").concat(msg));
            });
        });
    }
    // ── RCON parancs küldése ───────────────────────────────────────────────
    function sendRconCommand(cmd, resultEl) {
        return __awaiter(this, void 0, void 0, function () {
            var out, body, res, _a;
            var _b, _c;
            return __generator(this, function (_d) {
                switch (_d.label) {
                    case 0:
                        out = resultEl !== null && resultEl !== void 0 ? resultEl : rconResponse;
                        if (!out)
                            return [2 /*return*/];
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
                        out.className = 'rcon-response ' + (res.ok ? 'success' : 'error');
                        out.textContent = (_c = (_b = res.response) !== null && _b !== void 0 ? _b : res.error) !== null && _c !== void 0 ? _c : '–';
                        return [3 /*break*/, 4];
                    case 3:
                        _a = _d.sent();
                        out.className = 'rcon-response error';
                        out.textContent = 'Kapcsolati hiba – az RCON API nem válaszolt.';
                        return [3 /*break*/, 4];
                    case 4: return [2 /*return*/];
                }
            });
        });
    }
    // ── RCON input gomb + Enter ────────────────────────────────────────────
    rconSendBtn === null || rconSendBtn === void 0 ? void 0 : rconSendBtn.addEventListener('click', function () {
        var cmd = rconInput === null || rconInput === void 0 ? void 0 : rconInput.value.trim();
        if (cmd) {
            sendRconCommand(cmd);
            if (rconInput)
                rconInput.value = '';
        }
    });
    rconInput === null || rconInput === void 0 ? void 0 : rconInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            rconSendBtn === null || rconSendBtn === void 0 ? void 0 : rconSendBtn.click();
        }
    });
    // ── Refresh gomb ──────────────────────────────────────────────────────
    refreshBtn === null || refreshBtn === void 0 ? void 0 : refreshBtn.addEventListener('click', function () {
        if (refreshBtn) {
            var icon_1 = refreshBtn.querySelector('.material-symbols-rounded');
            if (icon_1)
                icon_1.classList.add('spinning');
            fetchStatus().finally(function () { return icon_1 === null || icon_1 === void 0 ? void 0 : icon_1.classList.remove('spinning'); });
        }
    });
    // ── Profilpanel RCON gombok (ban/unban/kick/msg) ───────────────────────
    document.querySelectorAll('.rcon-actions').forEach(function (panel) {
        var _a, _b;
        var username = panel.dataset.username;
        var resultEl = (_b = (_a = panel.closest('.panel-body')) === null || _a === void 0 ? void 0 : _a.querySelector('[id^="rcon-result-"]')) !== null && _b !== void 0 ? _b : null;
        panel.querySelectorAll('[data-action]').forEach(function (btn) {
            btn.addEventListener('click', function () { return __awaiter(void 0, void 0, void 0, function () {
                var action, reason, res, reason, res, res, msg;
                var _a, _b;
                return __generator(this, function (_c) {
                    switch (_c.label) {
                        case 0:
                            action = btn.dataset.action;
                            if (!(action === 'kick')) return [3 /*break*/, 2];
                            reason = (_a = prompt("Kir\u00FAg\u00E1s oka (".concat(username, "):"))) !== null && _a !== void 0 ? _a : 'Admin kirúgta.';
                            return [4 /*yield*/, rconPost('kick', { username: username, reason: reason })];
                        case 1:
                            res = _c.sent();
                            showResult(resultEl, res);
                            return [3 /*break*/, 8];
                        case 2:
                            if (!(action === 'ban')) return [3 /*break*/, 4];
                            if (!confirm("Biztosan kitiltod: ".concat(username, "?\n(Minecraft szerver + weboldal egyszerre)")))
                                return [2 /*return*/];
                            reason = (_b = prompt('Tiltás oka:')) !== null && _b !== void 0 ? _b : 'Admin döntés alapján.';
                            return [4 /*yield*/, rconPost('ban', { username: username, reason: reason })];
                        case 3:
                            res = _c.sent();
                            showResult(resultEl, res);
                            if (res.ok)
                                setTimeout(function () { return location.reload(); }, 1500);
                            return [3 /*break*/, 8];
                        case 4:
                            if (!(action === 'unban')) return [3 /*break*/, 6];
                            if (!confirm("Feloldod ".concat(username, " tilt\u00E1s\u00E1t? (MC + web)")))
                                return [2 /*return*/];
                            return [4 /*yield*/, rconPost('unban', { username: username })];
                        case 5:
                            res = _c.sent();
                            showResult(resultEl, res);
                            if (res.ok)
                                setTimeout(function () { return location.reload(); }, 1500);
                            return [3 /*break*/, 8];
                        case 6:
                            if (!(action === 'msg')) return [3 /*break*/, 8];
                            msg = prompt("Priv\u00E1t \u00FCzenet ".concat(username, " sz\u00E1m\u00E1ra:"));
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
    // ── Indulás + auto-refresh 30 másodpercenként ─────────────────────────
    fetchStatus();
    setInterval(fetchStatus, 30000);
});
