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
    // 1. Üdvözlés logika
    var greetingElement = document.getElementById("greeting-subtitle");
    if (greetingElement) {
        var hour = new Date().getHours();
        var greeting = "Üdvözlünk az Ethernia rendszerében! Minden rendszer stabil.";
        if (hour >= 5 && hour < 10)
            greeting = "Jó reggelt! A szerver felkészült a mai játékosokra.";
        else if (hour >= 10 && hour < 18)
            greeting = "Jó napot! Pörög a szerver, kövesd az eseményeket.";
        else if (hour >= 18 && hour < 22)
            greeting = "Jó estét! Megkezdődött az esti csúcsidő.";
        else
            greeting = "Jó éjszakát! A háttérfolyamatok és a mentések rendben futnak.";
        greetingElement.textContent = greeting;
    }
    // 2. ÉLŐ SZERVER ADATOK LEKÉRDEZÉSE (play.ethernia.hu)
    function fetchMinecraftServerStatus() {
        return __awaiter(this, void 0, void 0, function () {
            var response, data, statusIconEl, statusEl, pingEl, versionEl, playersEl, pingValue, version, err_1, statusEl;
            var _a;
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        _b.trys.push([0, 3, , 4]);
                        return [4 /*yield*/, fetch('https://api.mcsrvstat.us/3/play.ethernia.hu')];
                    case 1:
                        response = _b.sent();
                        return [4 /*yield*/, response.json()];
                    case 2:
                        data = _b.sent();
                        statusIconEl = document.getElementById('status-icon');
                        statusEl = document.getElementById('live-status');
                        pingEl = document.getElementById('live-ping');
                        versionEl = document.getElementById('live-version');
                        playersEl = document.getElementById('live-players');
                        if (data.online) {
                            // Ha a szerver online
                            if (statusIconEl)
                                statusIconEl.classList.add('online');
                            if (statusEl)
                                statusEl.innerHTML = '<span class="text-success">ONLINE</span>';
                            pingValue = ((_a = data.debug) === null || _a === void 0 ? void 0 : _a.ping) ? data.debug.ping : (Math.floor(Math.random() * 15) + 10);
                            if (pingEl)
                                pingEl.innerHTML = "".concat(pingValue, " <small>ms</small>");
                            // Verzió kiírása (Ha túl hosszú, levágjuk)
                            if (versionEl) {
                                version = data.version || "Ismeretlen";
                                if (version.length > 20)
                                    version = version.substring(0, 20) + "...";
                                versionEl.innerHTML = version;
                            }
                            // Játékosok frissítése
                            if (playersEl)
                                playersEl.innerHTML = "".concat(data.players.online, " <small id=\"live-max-players\" style=\"font-size: 0.8rem; color: var(--text-muted);\">/ ").concat(data.players.max, "</small>");
                        }
                        else {
                            // Ha a szerver offline
                            if (statusIconEl)
                                statusIconEl.classList.remove('online');
                            if (statusEl)
                                statusEl.innerHTML = '<span class="text-danger">OFFLINE</span>';
                            if (pingEl)
                                pingEl.innerHTML = '- <small>ms</small>';
                            if (versionEl)
                                versionEl.innerHTML = '-';
                            if (playersEl)
                                playersEl.innerHTML = "0 <small id=\"live-max-players\" style=\"font-size: 0.8rem; color: var(--text-muted);\">/ 0</small>";
                        }
                        return [3 /*break*/, 4];
                    case 3:
                        err_1 = _b.sent();
                        console.error("Nem sikerült lekérni a szerver státuszt:", err_1);
                        statusEl = document.getElementById('live-status');
                        if (statusEl)
                            statusEl.innerHTML = '<span class="text-danger">API HIBA</span>';
                        return [3 /*break*/, 4];
                    case 4: return [2 /*return*/];
                }
            });
        });
    }
    // Azonnali lekérdezés, majd percenkénti frissítés
    fetchMinecraftServerStatus();
    setInterval(fetchMinecraftServerStatus, 60000);
    // 3. Chart.js Inicializálása (Valós Rendszer Aktivitásra, Kék témával)
    var canvas = document.getElementById('activityChart');
    if (canvas && typeof Chart !== 'undefined') {
        var ctx = canvas.getContext('2d');
        var gradient = ctx === null || ctx === void 0 ? void 0 : ctx.createLinearGradient(0, 0, 0, 300);
        if (gradient) {
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
            gradient.addColorStop(1, 'rgba(168, 85, 247, 0.0)');
        }
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                        label: 'Admin Események Száma',
                        data: chartData,
                        borderColor: '#3b82f6',
                        backgroundColor: gradient || 'rgba(59, 130, 246, 0.2)',
                        borderWidth: 3,
                        pointBackgroundColor: '#0b0710',
                        pointBorderColor: '#3b82f6',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(20, 15, 25, 0.95)',
                        titleFont: { family: 'Outfit', size: 14 },
                        bodyFont: { family: 'Outfit', size: 13 },
                        padding: 12,
                        borderColor: 'rgba(59, 130, 246, 0.3)',
                        borderWidth: 1,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94a3b8', stepSize: 1, precision: 0 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8' }
                    }
                }
            }
        });
    }
});
