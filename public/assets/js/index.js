"use strict";
/// <reference lib="dom" />
const MC_SERVER = "play.ethernia.hu";
const DISCORD_GUILD_ID = "1322224781000577046"; // Ezt cseréld a valódira, ha más!
document.addEventListener("DOMContentLoaded", () => {
    // Aktuális év beállítása a láblécben
    const yearEl = document.getElementById("year");
    if (yearEl) {
        yearEl.textContent = String(new Date().getFullYear());
    }
    updateMinecraftStats();
    updateDiscordStats();
    initNewsModal();
    initClipboardCopy();
});
// Minecraft API lekérés
async function updateMinecraftStats() {
    var _a, _b;
    const onlineEl = document.getElementById("mc-online");
    const maxEl = document.getElementById("mc-max");
    if (!onlineEl || !maxEl)
        return;
    try {
        const res = await fetch(`https://api.mcsrvstat.us/2/${MC_SERVER}`, { cache: "no-store" });
        if (!res.ok)
            throw new Error("HTTP error: " + res.status);
        const data = await res.json();
        onlineEl.textContent = ((_a = data === null || data === void 0 ? void 0 : data.players) === null || _a === void 0 ? void 0 : _a.online) !== undefined ? String(data.players.online) : "0";
        maxEl.textContent = ((_b = data === null || data === void 0 ? void 0 : data.players) === null || _b === void 0 ? void 0 : _b.max) !== undefined ? String(data.players.max) : "?";
    }
    catch (e) {
        console.error("MC stat hiba:", e);
        onlineEl.textContent = "N/A";
        maxEl.textContent = "";
    }
}
// Discord Widget API lekérés
async function updateDiscordStats() {
    const onlineEl = document.getElementById("discord-online");
    if (!onlineEl)
        return;
    try {
        const url = `https://discord.com/api/guilds/${DISCORD_GUILD_ID}/widget.json`;
        const res = await fetch(url, { cache: "no-store" });
        if (!res.ok)
            throw new Error("HTTP error: " + res.status);
        const data = await res.json();
        const count = typeof data.presence_count === "number"
            ? data.presence_count
            : (Array.isArray(data.members) ? data.members.length : null);
        onlineEl.textContent = count !== null ? String(count) : "N/A";
    }
    catch (e) {
        console.error("Discord stat hiba:", e);
        onlineEl.textContent = "N/A";
    }
}
// Hírek Modal (Felugró ablak) logikája
function initNewsModal() {
    // Itt levettük a " | null"-t, így a TS tudja, hogy innentől ez egy HTMLElement
    const modal = document.getElementById("news-modal");
    if (!modal)
        return; // Futásidőben azért védve vagyunk, ha mégsem lenne a HTML-ben
    const contentInner = document.getElementById("modal-content-inner");
    const closeBtn = modal.querySelector(".modal-close");
    const readMoreButtons = document.querySelectorAll(".news-readmore");
    function openModal(card) {
        if (!contentInner)
            return;
        const tagEl = card.querySelector(".news-badge");
        const dateEl = card.querySelector(".news-date");
        const titleEl = card.querySelector(".news-title");
        const tag = (tagEl === null || tagEl === void 0 ? void 0 : tagEl.outerHTML) || "";
        const date = (dateEl === null || dateEl === void 0 ? void 0 : dateEl.textContent) || "";
        const title = (titleEl === null || titleEl === void 0 ? void 0 : titleEl.textContent) || "";
        const fullText = card.getAttribute("data-full") || "Nincs részletesebb leírás.";
        contentInner.innerHTML = `
      <div style="display:flex; gap:10px; align-items:center; margin-bottom:1rem;">
        ${tag}
        <span class="news-date">${date}</span>
      </div>
      <h2 class="modal-title">${title}</h2>
      <p class="modal-text">${fullText}</p>
    `;
        modal.classList.add("open");
    }
    function closeModal() {
        modal.classList.remove("open");
    }
    readMoreButtons.forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            const card = btn.closest(".news-card");
            if (card)
                openModal(card);
        });
    });
    closeBtn === null || closeBtn === void 0 ? void 0 : closeBtn.addEventListener("click", closeModal);
    // Most már a TypeScript nem fog panaszkodni a modal-ra a belső függvényben sem!
    modal.addEventListener("click", (e) => {
        if (e.target === modal)
            closeModal(); // Ha a sötét háttérre kattint
    });
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape")
            closeModal();
    });
}
// IP másolása vágólapra
function initClipboardCopy() {
    const copyElements = document.querySelectorAll('.copy-ip');
    copyElements.forEach(el => {
        el.addEventListener('click', () => {
            const ip = el.getAttribute('data-ip') || MC_SERVER;
            navigator.clipboard.writeText(ip).then(() => {
                showToast(`Szerver IP (${ip}) sikeresen másolva!`);
            }).catch(err => {
                console.error('Hiba a másolás során: ', err);
                showToast('Nem sikerült másolni az IP-t!', true);
            });
        });
    });
}
// Toast értesítő rendszer
function showToast(message, isError = false) {
    const container = document.getElementById('toast-container');
    if (!container)
        return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    if (isError) {
        toast.style.borderLeftColor = '#ef4444'; // Piros, ha hiba
    }
    toast.textContent = message;
    container.appendChild(toast);
    // Az animáció (fadeOut) 3mp múlva lefut a CSS miatt, utána a DOM-ból is töröljük
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}
