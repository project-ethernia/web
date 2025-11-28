"use strict";
// public/admin/assets/ts/users.ts
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
    var rows = document.querySelectorAll(".users-table tbody tr");
    if (!rows.length)
        return;
    function openModal(id) {
        var m = document.getElementById(id);
        if (m)
            m.classList.add("open");
    }
    function closeModal(el) {
        var m = el.closest(".modal");
        if (m)
            m.classList.remove("open");
    }
    document.querySelectorAll("[data-modal-close]").forEach(function (btn) {
        btn.addEventListener("click", function () { return closeModal(btn); });
    });
    document.querySelectorAll(".modal-backdrop").forEach(function (bd) {
        bd.addEventListener("click", function () { return closeModal(bd); });
    });
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            document
                .querySelectorAll(".modal.open")
                .forEach(function (m) { return m.classList.remove("open"); });
        }
    });
    var emailForm = document.getElementById("form-change-email");
    var emailUserId = document.getElementById("email-user-id");
    var emailUsername = document.getElementById("email-username");
    var emailInput = document.getElementById("email-new");
    var emailError = document.getElementById("email-error");
    var pwForm = document.getElementById("form-change-password");
    var pwUserId = document.getElementById("pw-user-id");
    var pwUsername = document.getElementById("pw-username");
    var pwNew = document.getElementById("pw-new");
    var pwNew2 = document.getElementById("pw-new2");
    var pwError = document.getElementById("pw-error");
    var delForm = document.getElementById("form-delete-user");
    var delUserId = document.getElementById("del-user-id");
    var delUsername = document.getElementById("del-username");
    var delEmail = document.getElementById("del-email");
    var delError = document.getElementById("del-error");
    rows.forEach(function (row) {
        var id = row.dataset.id || "";
        var username = row.dataset.username || "";
        var email = row.dataset.email || "";
        var btnEmail = row.querySelector(".js-change-email");
        var btnPw = row.querySelector(".js-change-password");
        var btnDel = row.querySelector(".js-delete-user");
        if (btnEmail && emailUserId && emailUsername && emailInput && emailError) {
            btnEmail.addEventListener("click", function () {
                emailUserId.value = id;
                emailUsername.textContent = username;
                emailInput.value = email;
                emailError.hidden = true;
                emailError.textContent = "";
                openModal("modal-change-email");
                emailInput.focus();
            });
        }
        if (btnPw && pwUserId && pwUsername && pwNew && pwNew2 && pwError) {
            btnPw.addEventListener("click", function () {
                pwUserId.value = id;
                pwUsername.textContent = username;
                pwNew.value = "";
                pwNew2.value = "";
                pwError.hidden = true;
                pwError.textContent = "";
                openModal("modal-change-password");
                pwNew.focus();
            });
        }
        if (btnDel && delUserId && delUsername && delEmail && delError) {
            btnDel.addEventListener("click", function () {
                delUserId.value = id;
                delUsername.textContent = username;
                delEmail.textContent = email;
                delError.hidden = true;
                delError.textContent = "";
                openModal("modal-delete-user");
            });
        }
    });
    var postUsers = function (data) {
        return fetch("/admin/users.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams(data)
        }).then(function (res) { return __awaiter(void 0, void 0, void 0, function () {
            var json, msg;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0: return [4 /*yield*/, res.json().catch(function () { return ({}); })];
                    case 1:
                        json = _a.sent();
                        if (!res.ok || !json.ok) {
                            msg = (json && json.error) ||
                                "Ismeretlen hiba (HTTP ".concat(res.status, ")");
                            throw new Error(msg);
                        }
                        return [2 /*return*/, json];
                }
            });
        }); });
    };
    if (emailForm && emailUserId && emailInput && emailError) {
        emailForm.addEventListener("submit", function (e) {
            e.preventDefault();
            emailError.hidden = true;
            emailError.textContent = "";
            var id = emailUserId.value;
            var newEmail = emailInput.value.trim();
            if (!newEmail) {
                emailError.textContent = "Adj meg egy e‑mail címet.";
                emailError.hidden = false;
                return;
            }
            postUsers({
                action: "change_email",
                id: id,
                email: newEmail
            })
                .then(function (json) {
                var row = document.querySelector(".users-table tbody tr[data-id=\"".concat(json.id, "\"]"));
                if (row) {
                    row.dataset.email = json.email;
                    var cell = row.querySelector(".cell-email");
                    if (cell)
                        cell.textContent = json.email;
                }
                closeModal(emailForm);
            })
                .catch(function (err) {
                emailError.textContent = err.message;
                emailError.hidden = false;
            });
        });
    }
    if (pwForm && pwUserId && pwNew && pwNew2 && pwError) {
        pwForm.addEventListener("submit", function (e) {
            e.preventDefault();
            pwError.hidden = true;
            pwError.textContent = "";
            var id = pwUserId.value;
            var p1 = pwNew.value;
            var p2 = pwNew2.value;
            if (!p1 || !p2) {
                pwError.textContent = "Töltsd ki mindkét jelszó mezőt.";
                pwError.hidden = false;
                return;
            }
            if (p1 !== p2) {
                pwError.textContent = "A két jelszó nem egyezik.";
                pwError.hidden = false;
                return;
            }
            postUsers({
                action: "change_password",
                id: id,
                password: p1
            })
                .then(function () {
                closeModal(pwForm);
            })
                .catch(function (err) {
                pwError.textContent = err.message;
                pwError.hidden = false;
            });
        });
    }
    if (delForm && delUserId && delError) {
        delForm.addEventListener("submit", function (e) {
            e.preventDefault();
            delError.hidden = true;
            delError.textContent = "";
            var id = delUserId.value;
            postUsers({
                action: "delete_user",
                id: id
            })
                .then(function (json) {
                var row = document.querySelector(".users-table tbody tr[data-id=\"".concat(json.id, "\"]"));
                if (row && row.parentElement) {
                    row.parentElement.removeChild(row);
                }
                closeModal(delForm);
            })
                .catch(function (err) {
                delError.textContent = err.message;
                delError.hidden = false;
            });
        });
    }
});
