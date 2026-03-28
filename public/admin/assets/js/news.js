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
    var tbody = document.getElementById("news-tbody");
    var searchInput = document.getElementById("news-search");
    var form = document.getElementById("news-form");
    var debounceTimer;
    var currentNewsList = [];
    function loadNews() {
        return __awaiter(this, arguments, void 0, function (query) {
            var res, json, err_1;
            if (query === void 0) { query = ''; }
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (!tbody)
                            return [2 /*return*/];
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;"><span class="material-symbols-rounded spinning" style="font-size: 2rem;">refresh</span><br><br>Betöltés...</td></tr>';
                        _a.label = 1;
                    case 1:
                        _a.trys.push([1, 4, , 5]);
                        return [4 /*yield*/, fetch("/admin/api/get_news.php?q=".concat(encodeURIComponent(query)))];
                    case 2:
                        res = _a.sent();
                        return [4 /*yield*/, res.json()];
                    case 3:
                        json = _a.sent();
                        if (json.status === 'success') {
                            tbody.innerHTML = '';
                            if (json.data.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">Nincs a keresésnek megfelelő találat.</td></tr>';
                                return [2 /*return*/];
                            }
                            currentNewsList = json.data;
                            json.data.forEach(function (news) {
                                var tr = document.createElement('tr');
                                tr.className = 'hover-row';
                                var isPub = Number(news.is_published) === 1;
                                // Toggle Gomb Logika
                                var visibilityBtn = isPub
                                    ? "<button type=\"button\" class=\"toggle-visibility active\" style=\"cursor: pointer;\" title=\"Kattints az elrejt\u00E9shez\" onclick=\"doNewsAction('toggle', ".concat(news.id, ", 0)\"><span class=\"material-symbols-rounded\">visibility</span></button>")
                                    : "<button type=\"button\" class=\"toggle-visibility inactive\" style=\"cursor: pointer;\" title=\"Kattints a k\u00F6zz\u00E9t\u00E9telhez\" onclick=\"doNewsAction('toggle', ".concat(news.id, ", 1)\"><span class=\"material-symbols-rounded\">visibility_off</span></button>");
                                // JAVÍTVA: Gyönyörű Badge dizájn kategóriák alapján
                                var badgeClass = 'default';
                                if (news.category === 'Karbantartás')
                                    badgeClass = 'info';
                                else if (news.category === 'Frissítés')
                                    badgeClass = 'success';
                                else if (news.category === 'Bejelentés')
                                    badgeClass = 'warning';
                                else if (news.category === 'Esemény')
                                    badgeClass = 'error';
                                tr.innerHTML = "\n                        <td class=\"td-id\">#".concat(String(news.id).padStart(3, '0'), "</td>\n                        <td>\n                            <div style=\"font-weight: 700; font-size: 1.05rem; margin-bottom: 0.4rem; color: #fff;\">").concat(news.title, "</div>\n                            <span class=\"badge ").concat(badgeClass, "\">").concat(news.category, "</span>\n                        </td>\n                        <td class=\"td-muted\">").concat(news.author_name || 'Ismeretlen', "</td>\n                        <td>").concat(visibilityBtn, "</td>\n                        <td>\n                            <div class=\"action-buttons\">\n                                <button type=\"button\" class=\"btn-sm btn-edit\" onclick=\"editNews(").concat(news.id, ")\"><span class=\"material-symbols-rounded\">edit</span></button>\n                                <button type=\"button\" class=\"btn-sm btn-danger\" onclick=\"doNewsAction('delete', ").concat(news.id, ")\"><span class=\"material-symbols-rounded\">delete</span></button>\n                            </div>\n                        </td>\n                    ");
                                tbody.appendChild(tr);
                            });
                        }
                        return [3 /*break*/, 5];
                    case 4:
                        err_1 = _a.sent();
                        tbody.innerHTML = "<tr><td colspan=\"5\" style=\"text-align: center; color: var(--admin-red);\">H\u00E1l\u00F3zati hiba t\u00F6rt\u00E9nt.</td></tr>";
                        return [3 /*break*/, 5];
                    case 5: return [2 /*return*/];
                }
            });
        });
    }
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () { return loadNews(e.target.value); }, 300);
        });
    }
    if (form) {
        form.addEventListener('submit', function (e) { return __awaiter(void 0, void 0, void 0, function () {
            var formData, payload, btn, res, data, actionInput, defaultRadio, headerText, headerIcon, err_2;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        e.preventDefault();
                        formData = new FormData(form);
                        payload = {
                            action: formData.get('action'),
                            id: formData.get('id'),
                            title: formData.get('title'),
                            category: formData.get('category'),
                            snippet: formData.get('snippet'),
                            content: formData.get('content'),
                            image_url: formData.get('image_url'),
                            is_published: formData.get('is_published') ? 1 : 0
                        };
                        btn = form.querySelector('button[type="submit"]');
                        if (btn) {
                            btn.disabled = true;
                            btn.innerText = "Mentés...";
                        }
                        _a.label = 1;
                    case 1:
                        _a.trys.push([1, 4, 5, 6]);
                        return [4 /*yield*/, fetch('/admin/api/news_action.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(payload)
                            })];
                    case 2:
                        res = _a.sent();
                        return [4 /*yield*/, res.json()];
                    case 3:
                        data = _a.sent();
                        if (data.status === 'success') {
                            showToast('success', data.message);
                            form.reset();
                            actionInput = document.getElementById('news-action');
                            if (actionInput)
                                actionInput.value = 'add';
                            defaultRadio = document.querySelector('input[name="category"][value="Karbantartás"]');
                            if (defaultRadio)
                                defaultRadio.checked = true;
                            headerText = document.getElementById('form-header-text');
                            headerIcon = document.getElementById('form-header-icon');
                            if (headerText && headerIcon) {
                                headerText.innerText = 'Új hír írása';
                                headerIcon.innerText = 'add_circle';
                                headerIcon.style.color = 'var(--admin-red)';
                            }
                            loadNews();
                        }
                        else {
                            showToast('error', data.message);
                        }
                        return [3 /*break*/, 6];
                    case 4:
                        err_2 = _a.sent();
                        showToast('error', "Hálózati hiba a mentés során.");
                        return [3 /*break*/, 6];
                    case 5:
                        if (btn) {
                            btn.disabled = false;
                            btn.innerText = "Közzététel / Mentés";
                        }
                        return [7 /*endfinally*/];
                    case 6: return [2 /*return*/];
                }
            });
        }); });
    }
    // Toggle és Törlés API hívó (Custom Confirm modallal)
    function executeAction(action, id, state) {
        return __awaiter(this, void 0, void 0, function () {
            var res, data, e_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        _a.trys.push([0, 3, , 4]);
                        return [4 /*yield*/, fetch('/admin/api/news_action.php', {
                                method: 'POST', headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: action, id: id, state: state })
                            })];
                    case 1:
                        res = _a.sent();
                        return [4 /*yield*/, res.json()];
                    case 2:
                        data = _a.sent();
                        if (data.status === 'success') {
                            showToast('success', data.message);
                            loadNews(); // Frissíti a listát, így a szem ikon is azonnal átvált!
                        }
                        else {
                            showToast('error', data.message);
                        }
                        return [3 /*break*/, 4];
                    case 3:
                        e_1 = _a.sent();
                        showToast('error', "Hiba az akció során.");
                        return [3 /*break*/, 4];
                    case 4: return [2 /*return*/];
                }
            });
        });
    }
    window.doNewsAction = function (action, id, state) {
        if (action === 'delete') {
            ethConfirm("Biztosan véglegesen törlöd ezt a hírt?", function () { return executeAction(action, id, state); });
        }
        else {
            executeAction(action, id, state);
        }
    };
    window.editNews = function (id) {
        var news = currentNewsList.find(function (n) { return Number(n.id) === id; });
        if (!news)
            return;
        var actionInput = document.getElementById('news-action');
        if (actionInput)
            actionInput.value = 'edit';
        var idInput = document.getElementById('news-id');
        if (idInput)
            idInput.value = news.id;
        var titleInput = document.getElementById('news-title');
        if (titleInput)
            titleInput.value = news.title;
        var catRadio = document.querySelector("input[name=\"category\"][value=\"".concat(news.category, "\"]"));
        if (catRadio)
            catRadio.checked = true;
        var snippetInput = document.getElementById('news-snippet');
        if (snippetInput)
            snippetInput.value = news.snippet || '';
        var contentInput = document.getElementById('news-content');
        if (contentInput)
            contentInput.value = news.content || '';
        var imageInput = document.getElementById('news-image');
        if (imageInput)
            imageInput.value = news.image_url || '';
        var pubInput = document.getElementById('news-published');
        if (pubInput)
            pubInput.checked = (Number(news.is_published) === 1);
        var headerText = document.getElementById('form-header-text');
        var headerIcon = document.getElementById('form-header-icon');
        if (headerText && headerIcon) {
            headerText.innerText = 'Hír szerkesztése';
            headerIcon.innerText = 'edit';
            headerIcon.style.color = 'var(--admin-info)';
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    loadNews();
});
