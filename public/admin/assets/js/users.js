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
    var tbody = document.getElementById("users-tbody");
    var searchInput = document.getElementById("user-search");
    var profilePanel = document.getElementById("profile-panel");
    var debounceTimer;
    function loadUsers() {
        return __awaiter(this, arguments, void 0, function (query) {
            var res, json, err_1;
            if (query === void 0) { query = ''; }
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (!tbody)
                            return [2 /*return*/];
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 3rem; color: var(--text-muted);"><span class="material-symbols-rounded spinning" style="font-size: 2rem;">refresh</span><br><br>Játékosok keresése...</td></tr>';
                        _a.label = 1;
                    case 1:
                        _a.trys.push([1, 4, , 5]);
                        return [4 /*yield*/, fetch("/admin/api/get_users.php?q=".concat(encodeURIComponent(query)))];
                    case 2:
                        res = _a.sent();
                        return [4 /*yield*/, res.json()];
                    case 3:
                        json = _a.sent();
                        if (json.status === 'success') {
                            tbody.innerHTML = '';
                            if (json.data.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">Nincs a keresésnek megfelelő játékos.</td></tr>';
                                return [2 /*return*/];
                            }
                            json.data.forEach(function (user) {
                                var tr = document.createElement('tr');
                                tr.className = 'hover-row';
                                var paddedId = String(user.id).padStart(4, '0');
                                var dateObj = new Date(user.created_at);
                                var formattedDate = !isNaN(dateObj.getTime()) ? dateObj.toISOString().split('T')[0] : user.created_at;
                                tr.innerHTML = "\n                        <td class=\"td-id\">#".concat(paddedId, "</td>\n                        <td>\n                            <div class=\"player-cell\">\n                                <img src=\"https://minotar.net/helm/").concat(user.username, "/24.png\" class=\"player-head\">\n                                <strong>").concat(user.username, "</strong>\n                            </div>\n                        </td>\n                        <td class=\"td-muted\">").concat(formattedDate, "</td>\n                        <td>\n                            <button class=\"btn-sm btn-open load-profile-btn\" data-id=\"").concat(user.id, "\">Megnyit\u00E1s</button>\n                        </td>\n                    ");
                                tbody.appendChild(tr);
                            });
                            document.querySelectorAll('.load-profile-btn').forEach(function (btn) {
                                btn.addEventListener('click', function (e) {
                                    var _a;
                                    document.querySelectorAll('.log-row, .hover-row').forEach(function (r) { return r.classList.remove('active-row'); });
                                    var target = e.currentTarget;
                                    (_a = target.closest('tr')) === null || _a === void 0 ? void 0 : _a.classList.add('active-row');
                                    var id = target.getAttribute('data-id');
                                    if (id)
                                        loadUserProfile(id);
                                });
                            });
                        }
                        else {
                            tbody.innerHTML = "<tr><td colspan=\"4\" style=\"text-align: center; color: var(--admin-red); padding: 2rem;\">Hiba: ".concat(json.message, "</td></tr>");
                        }
                        return [3 /*break*/, 5];
                    case 4:
                        err_1 = _a.sent();
                        tbody.innerHTML = "<tr><td colspan=\"4\" style=\"text-align: center; color: var(--admin-red); padding: 2rem;\">H\u00E1l\u00F3zati hiba t\u00F6rt\u00E9nt.</td></tr>";
                        return [3 /*break*/, 5];
                    case 5: return [2 /*return*/];
                }
            });
        });
    }
    function loadUserProfile(id) {
        return __awaiter(this, void 0, void 0, function () {
            var res, json, u, statusBadge, err_2;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (!profilePanel)
                            return [2 /*return*/];
                        profilePanel.innerHTML = '<div class="empty-profile"><span class="material-symbols-rounded spinning" style="font-size: 3rem;">refresh</span><p>Profil betöltése...</p></div>';
                        _a.label = 1;
                    case 1:
                        _a.trys.push([1, 4, , 5]);
                        return [4 /*yield*/, fetch("/admin/api/get_user_profile.php?id=".concat(id))];
                    case 2:
                        res = _a.sent();
                        return [4 /*yield*/, res.json()];
                    case 3:
                        json = _a.sent();
                        if (json.status === 'success') {
                            u = json.data;
                            statusBadge = u.status === 'Aktív' ? 'success' : 'error';
                            // JAVÍTVA: Az alert() helyett mostantól a showToast() fut le!
                            profilePanel.innerHTML = "\n                    <div class=\"panel-header\" style=\"border-radius: 12px 12px 0 0;\">\n                        <h2><span class=\"material-symbols-rounded\">person</span> J\u00E1t\u00E9kos Profilja</h2>\n                    </div>\n                    <div class=\"panel-body\">\n                        <div class=\"profile-header\">\n                            <img src=\"https://minotar.net/armor/bust/".concat(u.username, "/80.png\" class=\"profile-avatar\">\n                            <div>\n                                <h3 class=\"profile-name\">").concat(u.username, "</h3>\n                                <span class=\"profile-id\">ID: #").concat(String(u.id).padStart(4, '0'), "</span>\n                                <span class=\"badge ").concat(statusBadge, "\" style=\"margin-left: 0.5rem;\">").concat(u.status, "</span>\n                            </div>\n                        </div>\n                        \n                        <div class=\"profile-info-grid\">\n                            <div class=\"info-box\">\n                                <span class=\"info-label\">Regisztr\u00E1ci\u00F3 ideje</span>\n                                <span class=\"info-value\">").concat(u.created_at, "</span>\n                            </div>\n                            <div class=\"info-box\">\n                                <span class=\"info-label\">Rendelkez\u00E9sre \u00E1ll\u00F3 Ethernia Coin</span>\n                                <span class=\"info-value\" style=\"color: var(--admin-warning); font-weight: 800;\">").concat(u.coins, " EC</span>\n                            </div>\n                            <div class=\"info-box\">\n                                <span class=\"info-label\">Jelenlegi Rang</span>\n                                <span class=\"info-value\" style=\"color: var(--admin-info); font-weight: 800;\">").concat(u.rank, "</span>\n                            </div>\n                        </div>\n\n                        <hr class=\"control-divider\">\n                        <h4 style=\"color: var(--text-muted); text-transform: uppercase; font-size: 0.8rem; margin-bottom: 1rem;\">Adminisztr\u00E1tori M\u0171veletek</h4>\n                        \n                        <div class=\"punishment-actions\">\n                            <button class=\"btn-punish\" onclick=\"showToast('info', 'A b\u00FCntet\u00E9si API hamarosan bek\u00F6t\u00E9sre ker\u00FCl!')\">\n                                <span class=\"material-symbols-rounded\">gavel</span>\n                                <div>\n                                    <strong>Kitilt\u00E1s (Ban)</strong>\n                                    <span>J\u00E1t\u00E9kos v\u00E9gleges vagy ideiglenes kitilt\u00E1sa</span>\n                                </div>\n                            </button>\n                            <button class=\"btn-punish\" onclick=\"showToast('info', 'A b\u00FCntet\u00E9si API hamarosan bek\u00F6t\u00E9sre ker\u00FCl!')\">\n                                <span class=\"material-symbols-rounded\">volume_off</span>\n                                <div>\n                                    <strong>N\u00E9m\u00EDt\u00E1s (Mute)</strong>\n                                    <span>Chat haszn\u00E1lat\u00E1nak megvon\u00E1sa</span>\n                                </div>\n                            </button>\n                        </div>\n                    </div>\n                ");
                        }
                        else {
                            profilePanel.innerHTML = "<div class=\"empty-profile\"><span class=\"material-symbols-rounded\" style=\"color: var(--admin-red);\">error</span><p>Hiba: ".concat(json.message, "</p></div>");
                        }
                        return [3 /*break*/, 5];
                    case 4:
                        err_2 = _a.sent();
                        profilePanel.innerHTML = '<div class="empty-profile"><span class="material-symbols-rounded" style="color: var(--admin-red);">wifi_off</span><p>Hálózati hiba a profil betöltésekor.</p></div>';
                        return [3 /*break*/, 5];
                    case 5: return [2 /*return*/];
                }
            });
        });
    }
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            clearTimeout(debounceTimer);
            var target = e.target;
            debounceTimer = setTimeout(function () {
                loadUsers(target.value);
            }, 300);
        });
    }
    loadUsers('');
});
