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
    var modal = document.getElementById("log-modal");
    var uaContainer = document.getElementById("log-ua");
    var ctxContainer = document.getElementById("log-context");
    var closeBtns = document.querySelectorAll(".modal-close, #log-close-btn");
    var tbody = document.getElementById("logs-tbody");
    var searchInput = document.getElementById("log-search");
    var debounceTimer;
    // Fő funkció: Táblázat adatainak lekérése API-ból
    function loadLogs() {
        return __awaiter(this, arguments, void 0, function (query) {
            var res, json, err_1;
            if (query === void 0) { query = ''; }
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (!tbody)
                            return [2 /*return*/];
                        // Töltő képernyő (Loading)
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);"><span class="material-symbols-rounded spinning" style="font-size: 2rem;">refresh</span><br><br>Adatok betöltése...</td></tr>';
                        _a.label = 1;
                    case 1:
                        _a.trys.push([1, 4, , 5]);
                        return [4 /*yield*/, fetch("/admin/api/get_logs.php?q=".concat(encodeURIComponent(query)))];
                    case 2:
                        res = _a.sent();
                        return [4 /*yield*/, res.json()];
                    case 3:
                        json = _a.sent();
                        if (json.status === 'success') {
                            tbody.innerHTML = ''; // Töröljük a régi táblázatot
                            if (json.data.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">Nincs a keresésnek megfelelő találat a naplóban.</td></tr>';
                                return [2 /*return*/];
                            }
                            // Generáljuk az új sorokat
                            json.data.forEach(function (log) {
                                var _a;
                                var tr = document.createElement('tr');
                                tr.className = 'hover-row log-row';
                                // Biztonságos Dátum formázás JS-ben
                                var dateObj = new Date(log.created_at);
                                var formattedDate = !isNaN(dateObj.getTime())
                                    ? dateObj.toISOString().replace('T', ' ').substring(0, 19)
                                    : log.created_at;
                                var paddedId = String(log.id).padStart(4, '0');
                                var username = log.username || 'Rendszer';
                                var headUser = log.username ? log.username : 'MHF_Steve';
                                tr.innerHTML = "\n                        <td class=\"td-id\">#".concat(paddedId, "</td>\n                        <td>\n                            <div class=\"player-cell\">\n                                <img src=\"https://minotar.net/helm/").concat(headUser, "/24.png\" class=\"player-head\">\n                                <strong>").concat(username, "</strong>\n                            </div>\n                        </td>\n                        <td style=\"color: #cbd5e1;\">").concat(log.action, "</td>\n                        <td class=\"td-muted\">").concat(log.ip_address || '-', "</td>\n                        <td class=\"td-muted\">").concat(formattedDate, "</td>\n                        <td>\n                            <span class=\"status-badge\" style=\"background: rgba(255,255,255,0.05); color: var(--text-muted); cursor: pointer;\" title=\"").concat(log.user_agent || '', "\">\n                                <span class=\"material-symbols-rounded\" style=\"font-size: 1.1rem;\">devices</span> Info\n                            </span>\n                        </td>\n                    ");
                                // Gombkattintás a Modal megnyitásához
                                (_a = tr.querySelector('.status-badge')) === null || _a === void 0 ? void 0 : _a.addEventListener("click", function () { return openModal(log.user_agent, log.context); });
                                tbody.appendChild(tr);
                            });
                        }
                        else {
                            tbody.innerHTML = "<tr><td colspan=\"6\" style=\"text-align: center; color: var(--admin-red); padding: 2rem;\">Hiba: ".concat(json.message, "</td></tr>");
                        }
                        return [3 /*break*/, 5];
                    case 4:
                        err_1 = _a.sent();
                        tbody.innerHTML = "<tr><td colspan=\"6\" style=\"text-align: center; color: var(--admin-red); padding: 2rem;\">H\u00E1l\u00F3zati hiba t\u00F6rt\u00E9nt az adatok lek\u00E9r\u00E9sekor.</td></tr>";
                        return [3 /*break*/, 5];
                    case 5: return [2 /*return*/];
                }
            });
        });
    }
    // Modal kezelő függvény
    function openModal(ua, contextRaw) {
        if (!modal || !uaContainer || !ctxContainer)
            return;
        uaContainer.textContent = ua || "Ismeretlen böngésző/eszköz";
        try {
            var parsed = JSON.parse(contextRaw || "{}");
            ctxContainer.textContent = JSON.stringify(parsed, null, 4);
        }
        catch (e) {
            ctxContainer.textContent = contextRaw || "{}";
        }
        modal.classList.add("open");
    }
    var closeModal = function () { if (modal)
        modal.classList.remove("open"); };
    closeBtns.forEach(function (btn) { return btn.addEventListener("click", function (e) { e.preventDefault(); closeModal(); }); });
    if (modal)
        modal.addEventListener("click", function (e) { if (e.target === modal)
            closeModal(); });
    document.addEventListener("keydown", function (e) { if (e.key === "Escape")
        closeModal(); });
    // ÉLŐ KERESÉS ESEMÉNY (Amikor a felhasználó gépel)
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            clearTimeout(debounceTimer); // Töröljük a korábbi időzítőt
            var target = e.target;
            debounceTimer = setTimeout(function () {
                loadLogs(target.value); // Fél másodperc csend után betöltjük!
            }, 300);
        });
    }
    // Amikor betölt az oldal, egyből le is húzzuk az alap adatokat
    loadLogs('');
});
