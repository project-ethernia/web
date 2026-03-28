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
    if (!document.getElementById('spin-anim')) {
        var style = document.createElement('style');
        style.id = 'spin-anim';
        style.textContent = "@keyframes spin { 100% { transform: rotate(360deg); } }";
        document.head.appendChild(style);
    }
    var form = document.querySelector("form.login-form");
    if (form) {
        form.addEventListener("submit", function (e) { return __awaiter(void 0, void 0, void 0, function () {
            var btn, loaderHTML, loader, formData, res_1, err_1;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        e.preventDefault();
                        btn = form.querySelector("button[type='submit']");
                        if (btn)
                            btn.disabled = true;
                        loaderHTML = "\n                <div id=\"admin-loader\" style=\"position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #0b0710; backdrop-filter: blur(15px); z-index: 999999; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff; opacity: 0; transition: opacity 0.3s ease;\">\n                    <span class=\"material-symbols-rounded\" style=\"font-size: 5rem; color: #ef4444; filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.4)); animation: spin 1s linear infinite;\">admin_panel_settings</span>\n                    <h2 style=\"margin-top: 2rem; font-family: 'Poppins', sans-serif; letter-spacing: 0.15em; font-size: 1.5rem; color: #ef4444; text-shadow: 0 0 20px rgba(239, 68, 68, 0.4); text-transform: uppercase;\">Rendszer Bet\u00F6lt\u00E9se</h2>\n                    <p style=\"color: #94a3b8; font-family: 'Outfit', sans-serif; margin-top: 0.5rem; font-size: 1.1rem;\">Titkos\u00EDtott csatorna fel\u00E9p\u00EDt\u00E9se...</p>\n                </div>\n            ";
                        document.body.insertAdjacentHTML('beforeend', loaderHTML);
                        loader = document.getElementById('admin-loader');
                        if (loader) {
                            setTimeout(function () { loader.style.opacity = '1'; }, 10);
                        }
                        _a.label = 1;
                    case 1:
                        _a.trys.push([1, 3, , 4]);
                        formData = new FormData(form);
                        return [4 /*yield*/, fetch(form.action || window.location.href, {
                                method: form.method || 'POST',
                                body: formData
                            })];
                    case 2:
                        res_1 = _a.sent();
                        setTimeout(function () {
                            window.location.href = res_1.url;
                        }, 2500);
                        return [3 /*break*/, 4];
                    case 3:
                        err_1 = _a.sent();
                        if (window.Toast) {
                            window.Toast.error("Kapcsolati hiba a szerverrel!");
                        }
                        setTimeout(function () { window.location.reload(); }, 1500);
                        return [3 /*break*/, 4];
                    case 4: return [2 /*return*/];
                }
            });
        }); });
    }
});
