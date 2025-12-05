"use strict";
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
    var _a, _b;
    var modal = document.getElementById("admin-modal");
    var form = document.getElementById("admin-form");
    var errorEl = document.getElementById("admin-error");
    var closeBtn = (_a = modal === null || modal === void 0 ? void 0 : modal.querySelector(".modal-close")) !== null && _a !== void 0 ? _a : null;
    var backdrop = (_b = modal === null || modal === void 0 ? void 0 : modal.querySelector(".modal-backdrop")) !== null && _b !== void 0 ? _b : null;
    var cancelBtn = document.getElementById("admin-cancel");
    var addBtn = document.getElementById("btn-add-admin");
    var addBtnEmpty = document.getElementById("btn-add-admin-empty");
    var usernameInput = document.getElementById("admin-username");
    function openModal() {
        if (!modal)
            return;
        if (form)
            form.reset();
        if (errorEl) {
            errorEl.hidden = true;
            errorEl.textContent = "";
        }
        modal.classList.add("open");
        if (usernameInput)
            usernameInput.focus();
    }
    function closeModal() {
        if (!modal)
            return;
        modal.classList.remove("open");
    }
    if (addBtn)
        addBtn.addEventListener("click", openModal);
    if (addBtnEmpty)
        addBtnEmpty.addEventListener("click", openModal);
    [closeBtn, backdrop, cancelBtn].forEach(function (el) {
        if (!el)
            return;
        el.addEventListener("click", function (e) {
            e.preventDefault();
            closeModal();
        });
    });
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape")
            closeModal();
    });
    function showError(message) {
        if (errorEl) {
            errorEl.hidden = false;
            errorEl.textContent = message;
        }
        else {
            alert(message);
        }
    }
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            if (errorEl) {
                errorEl.hidden = true;
                errorEl.textContent = "";
            }
            var submitBtn = form.querySelector("button[type=submit]");
            if (submitBtn)
                submitBtn.disabled = true;
            var formData = new FormData(form);
            fetch("/admin/admins.php", {
                method: "POST",
                body: formData
            })
                .then(function (res) { return __awaiter(void 0, void 0, void 0, function () {
                var data, _a;
                return __generator(this, function (_b) {
                    switch (_b.label) {
                        case 0:
                            _b.trys.push([0, 2, , 3]);
                            return [4 /*yield*/, res.json()];
                        case 1:
                            data = _b.sent();
                            return [3 /*break*/, 3];
                        case 2:
                            _a = _b.sent();
                            throw new Error("Hibás válasz érkezett a szervertől.");
                        case 3:
                            if (!data.ok) {
                                throw new Error(data.error || "Ismeretlen hiba történt az admin létrehozásakor.");
                            }
                            window.location.reload();
                            return [2 /*return*/];
                    }
                });
            }); })
                .catch(function (err) {
                console.error(err);
                showError((err === null || err === void 0 ? void 0 : err.message) || "Hálózati hiba történt a mentés során.");
            })
                .finally(function () {
                if (submitBtn)
                    submitBtn.disabled = false;
            });
        });
    }
    document.querySelectorAll(".visibility-toggle").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var id = btn.dataset.id;
            if (!id)
                return;
            var currentVisible = btn.dataset.visible === "1";
            var next = currentVisible ? 0 : 1;
            btn.disabled = true;
            var formData = new FormData();
            formData.append("action", "toggle_active");
            formData.append("id", id);
            formData.append("is_active", String(next));
            fetch("/admin/admins.php", {
                method: "POST",
                body: formData
            })
                .then(function (res) { return __awaiter(void 0, void 0, void 0, function () {
                var data, _a, tr;
                return __generator(this, function (_b) {
                    switch (_b.label) {
                        case 0:
                            _b.trys.push([0, 2, , 3]);
                            return [4 /*yield*/, res.json()];
                        case 1:
                            data = _b.sent();
                            return [3 /*break*/, 3];
                        case 2:
                            _a = _b.sent();
                            throw new Error("Hibás válasz érkezett a szervertől.");
                        case 3:
                            if (!data.ok) {
                                throw new Error(data.error || "Hiba az aktiválás/inaktiválás során.");
                            }
                            btn.dataset.visible = String(next);
                            btn.setAttribute("aria-pressed", next ? "true" : "false");
                            btn.classList.toggle("is-on", !!next);
                            btn.classList.toggle("is-off", !next);
                            btn.title = next
                                ? "Aktív – kattints az inaktiváláshoz"
                                : "Inaktív – kattints az aktiváláshoz";
                            tr = btn.closest("tr");
                            if (tr) {
                                tr.dataset.is_active = String(next);
                            }
                            return [2 /*return*/];
                    }
                });
            }); })
                .catch(function (err) {
                console.error(err);
                alert((err === null || err === void 0 ? void 0 : err.message) || "Hálózati hiba történt az állapot módosítása közben.");
            })
                .finally(function () {
                btn.disabled = false;
            });
        });
    });
    document.querySelectorAll(".btn-reset-pw").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var tr = btn.closest("tr");
            if (!tr)
                return;
            var id = tr.dataset.id;
            if (!id)
                return;
            var username = tr.dataset.username || id;
            var newPw = window.prompt("\u00DAj jelsz\u00F3 be\u00E1ll\u00EDt\u00E1sa ehhez az adminhoz: ".concat(username, "\n\n\u00CDrd be az \u00FAj jelsz\u00F3t:"));
            if (!newPw)
                return;
            if (newPw.length < 4) {
                alert("A jelszó legyen legalább 4 karakter.");
                return;
            }
            var formData = new FormData();
            formData.append("action", "reset_password");
            formData.append("id", id);
            formData.append("password", newPw);
            btn.disabled = true;
            fetch("/admin/admins.php", {
                method: "POST",
                body: formData
            })
                .then(function (res) { return __awaiter(void 0, void 0, void 0, function () {
                var data, _a;
                return __generator(this, function (_b) {
                    switch (_b.label) {
                        case 0:
                            _b.trys.push([0, 2, , 3]);
                            return [4 /*yield*/, res.json()];
                        case 1:
                            data = _b.sent();
                            return [3 /*break*/, 3];
                        case 2:
                            _a = _b.sent();
                            throw new Error("Hibás válasz érkezett a szervertől.");
                        case 3:
                            if (!data.ok) {
                                throw new Error(data.error || "Hiba a jelszó csere során.");
                            }
                            alert("Jelszó sikeresen módosítva ehhez az adminhoz: " + username);
                            return [2 /*return*/];
                    }
                });
            }); })
                .catch(function (err) {
                console.error(err);
                alert((err === null || err === void 0 ? void 0 : err.message) || "Hálózati hiba történt a jelszó módosítása közben.");
            })
                .finally(function () {
                btn.disabled = false;
            });
        });
    });
});
